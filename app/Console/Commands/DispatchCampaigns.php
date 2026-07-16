<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically dispatch pending SMS campaigns scheduled for today';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');
        
        $campaigns = \App\Models\Campaign::where('status', 'Pending')
            ->where('event_date', '<=', $today)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info("No pending campaigns to dispatch for today.");
            return;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Processing campaign: {$campaign->name}");
            
            $campaign->update(['status' => 'Processing']);

            // Build query for target audience
            $query = \App\Models\Customer::query();

            if (!empty($campaign->target_service)) {
                $query->where('service', $campaign->target_service);
            }

            if (!empty($campaign->target_location)) {
                // Matching exactly on region, assuming users entered valid regions now
                $query->where('region', $campaign->target_location);
            }

            if (!empty($campaign->target_stage)) {
                $query->where('buying_stage', $campaign->target_stage);
            }

            $customerIds = $query->pluck('id')->toArray();

            if (empty($customerIds)) {
                $this->warn("No customers matched the targeting for campaign: {$campaign->name}");
                $campaign->update(['status' => 'Completed']); // Or Cancelled
                continue;
            }

            // Dispatch background job to send the campaign
            \App\Jobs\SendBulkBroadcastJob::dispatch(
                $customerIds,
                $campaign->message,
                $campaign->channels ?? ['sms'],
                null,
                'campaign'
            );

            $campaign->update(['status' => 'Completed']);
            $this->info("Dispatched {$campaign->name} to " . count($customerIds) . " recipients.");
        }
    }
}
