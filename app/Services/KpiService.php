<?php

namespace App\Services;

use App\Models\KpiActivity;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class KpiService
{
    /**
     * Define the point structure based on the provided requirements
     */
    const POINTS = [
        'REG_NEW_POTENTIAL'     => 5,
        'COMPLETE_PROFILE'      => 3,
        'DAILY_FOLLOWUP'        => 2,
        'PHYSICAL_VISIT'        => 5,
        'QUOTATION_SENT'        => 5,
        'SALE_CLOSED_NEW'       => 10,
        'SALE_CLOSED_REPEAT'    => 10,
        'SALE_HIGH_VALUE'       => 10,
        'PAYMENT_COLLECTED'     => 5,
        'POSITIVE_FEEDBACK'     => 5,
        'NEW_ORG_LEAD'          => 5,
        'DAILY_UPDATE_BEFORE_6' => 2,
        'MEETING_ON_TIME'       => 2,
        'WEEKLY_REPORT'         => 3,
        'RECOVER_INACTIVE'      => 10,
        'UPSELLING'             => 5,
        'REFERRAL_EXISTING'     => 8,
        'SOCIAL_MEDIA_CONV'     => 5,
        
        // Discipline (Negatives) - Total -50
        'LATE_UPDATE'           => -10,
        'MISSED_FOLLOWUP'       => -10,
        'CUSTOMER_COMPLAINT'    => -10,
        'FAKE_DATA'             => -15,
        'MISSED_MEETING'        => -5,
    ];

    /**
     * Award or Deduct points for a user
     */
    public static function recordActivity($code, $userId = null, $customerId = null, $customDescription = null)
    {
        $userId = $userId ?? Auth::id();
        if (!$userId) return;

        // ── Anti-Spam / Duplicate Check ───────────────────────────────
        $today = now()->startOfDay();
        $query = KpiActivity::where('user_id', $userId)
            ->where('activity_code', $code);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        // 1. One-time only activities (per customer)
        $oneTimeCodes = ['REG_NEW_POTENTIAL', 'COMPLETE_PROFILE'];
        if (in_array($code, $oneTimeCodes) && $customerId) {
            if ($query->exists()) return;
        }

        // 2. Once per day activities (per customer)
        $oncePerDayCodes = ['DAILY_UPDATE_BEFORE_6', 'LATE_UPDATE', 'DAILY_FOLLOWUP', 'PHYSICAL_VISIT'];
        if (in_array($code, $oncePerDayCodes) && $customerId) {
            if ((clone $query)->where('created_at', '>=', $today)->exists()) return;
        }

        // 3. Prevent double penalty/reward for "Updates" on the same customer today
        // (If they updated before 6, and then update again after 6, don't penalize them if they already got the reward)
        if ($code === 'LATE_UPDATE' && $customerId) {
            if (KpiActivity::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->where('activity_code', 'DAILY_UPDATE_BEFORE_6')
                ->where('created_at', '>=', $today)
                ->exists()) {
                return; // Already rewarded for today, don't penalize now.
            }
        }
        // ───────────────────────────────────────────────────────────────

        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        
        // Map dynamic points from settings
        $pointMap = self::POINTS;
        if ($code === 'SALE_CLOSED_NEW') $pointMap[$code] = (int)($settings['kpi_sale_new'] ?? self::POINTS[$code]);
        if ($code === 'SALE_CLOSED_REPEAT') $pointMap[$code] = (int)($settings['kpi_sale_repeat'] ?? self::POINTS[$code]);
        if ($code === 'LATE_UPDATE') $pointMap[$code] = (int)($settings['kpi_late_update'] ?? self::POINTS[$code]);
        if ($code === 'SALE_HIGH_VALUE') $pointMap[$code] = (int)($settings['kpi_high_value_points'] ?? self::POINTS[$code]);
        if ($code === 'POSITIVE_FEEDBACK') $pointMap[$code] = (int)($settings['survey_kpi_points'] ?? self::POINTS[$code]);

        $points = $pointMap[$code] ?? 0;
        $description = $customDescription ?? self::getActivityName($code);

        $activity = KpiActivity::create([
            'user_id'       => $userId,
            'customer_id'   => $customerId,
            'performed_by'  => Auth::id(),
            'activity_type' => $points >= 0 ? 'achievement' : 'discipline',
            'activity_code' => $code,
            'points'        => $points,
            'description'   => $description
        ]);

        if ($code === 'CUSTOMER_COMPLAINT' && $activity) {
            $user = User::find($userId);
            $managers = User::whereIn('role', ['manager', 'super_admin'])
                ->where(function($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)
                      ->orWhere('role', 'super_admin');
                })->get();
            
            foreach ($managers as $manager) {
                $manager->notify(new \App\Notifications\ComplaintAlertNotification($activity));
            }
        }

        return $activity;
    }

    /**
     * Get performance level based on total points
     */
    public static function getLevel($totalPoints)
    {
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $champion = (int)($settings['kpi_level_champion'] ?? 35000);
        $excellent = (int)($settings['kpi_level_excellent'] ?? 25100);
        $good = (int)($settings['kpi_level_good'] ?? 15100);
        $fair = (int)($settings['kpi_level_fair'] ?? 8100);

        if ($totalPoints >= $champion) return ['name' => 'Sales Champion', 'color' => '#d4af37', 'icon' => 'fa-trophy'];
        if ($totalPoints >= $excellent) return ['name' => 'Excellent Performer', 'color' => '#28a745', 'icon' => 'fa-star'];
        if ($totalPoints >= $good) return ['name' => 'Good Performer', 'color' => '#007bff', 'icon' => 'fa-thumbs-up'];
        if ($totalPoints >= $fair)  return ['name' => 'Fair Performance', 'color' => '#17a2b8', 'icon' => 'fa-smile-o'];
        return ['name' => 'Needs Improvement', 'color' => '#dc3545', 'icon' => 'fa-exclamation-triangle'];
    }

    private static function getActivityName($code)
    {
        return ucwords(strtolower(str_replace('_', ' ', $code)));
    }
}
