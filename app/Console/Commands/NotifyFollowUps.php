<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Notifications\FollowUpReminderNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class NotifyFollowUps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-follow-ups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify sales officers about follow-ups due today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        $customers = Customer::whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', $today)
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->with('salesOfficer')
            ->get();

        $count = 0;
        foreach ($customers as $customer) {
            if ($customer->salesOfficer) {
                $customer->salesOfficer->notify(new FollowUpReminderNotification($customer));
                $count++;
            }
        }

        $this->info("Successfully sent {$count} follow-up notifications.");
    }
}
