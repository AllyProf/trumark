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
        $checkboxKeys = ['survey_channels_sms', 'survey_channels_whatsapp', 'survey_channels_email'];
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

        return back()->with('success', 'System settings updated successfully!');
    }
}
