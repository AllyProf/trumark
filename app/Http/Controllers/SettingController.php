<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        
        $smsBalance = null;
        try {
            $smsService = new \App\Services\SmsService();
            $smsBalance = $smsService->getBalance();
        } catch (\Exception $e) {
            // Silently fail if API is down
        }

        return view('settings.index', compact('settings', 'smsBalance'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        // Explicitly handle checkboxes that do not submit a value when unchecked
        $checkboxKeys = [
            'survey_channels_sms',
            'survey_channels_whatsapp',
            'survey_channels_email',
            'welcome_channels_sms',
            'welcome_channels_whatsapp',
            'welcome_channels_email',
            'followup_channels_sms',
            'followup_channels_whatsapp',
            'followup_channels_email',
            'sms_allow_manual',
            'sms_allow_bulk',
            'sms_allow_campaign',
            'sms_allow_lead_assignment',
            'sms_allow_staff',
        ];
        foreach ($checkboxKeys as $key) {
            if (!$request->has($key)) {
                $data[$key] = '0';
            }
        }

        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        \App\Models\AuditLog::record('Updated CRM system settings', 'Settings');

        return back()->with('success', 'System settings updated successfully!');
    }
}
