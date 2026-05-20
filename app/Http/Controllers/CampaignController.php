<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campaigns = Campaign::orderBy('event_date', 'asc')->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    public function calendar()
    {
        return view('campaigns.calendar');
    }

    public function events()
    {
        $campaigns = Campaign::all();
        $events = $campaigns->map(function ($campaign) {
            return [
                'id' => $campaign->id,
                'title' => $campaign->name,
                'start' => $campaign->event_date->format('Y-m-d'),
                'color' => $campaign->status === 'Completed' ? '#28a745' : '#007bff',
                'extendedProps' => [
                    'message' => $campaign->message,
                    'status' => $campaign->status,
                    'target_service' => $campaign->target_service ?? 'All',
                    'target_location' => $campaign->target_location ?? 'All',
                ]
            ];
        });
        return response()->json($events);
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'message' => 'required|string|max:1000',
            'channels' => 'required|array|min:1'
        ]);

        Campaign::create([
            'name' => $request->name,
            'event_date' => $request->event_date,
            'message' => $request->message,
            'target_service' => $request->target_service,
            'target_location' => $request->target_location,
            'target_stage' => $request->target_stage,
            'channels' => $request->channels,
            'status' => 'Pending',
            'created_by' => auth()->id()
        ]);

        return redirect()->route('campaigns.index')->with('success', 'Campaign scheduled successfully.');
    }

    public function show(string $id)
    {
        // Not implemented
    }

    public function edit(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        return view('campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'message' => 'required|string|max:1000',
            'channels' => 'required|array|min:1'
        ]);

        $campaign = Campaign::findOrFail($id);
        $campaign->update([
            'name' => $request->name,
            'event_date' => $request->event_date,
            'message' => $request->message,
            'target_service' => $request->target_service,
            'target_location' => $request->target_location,
            'target_stage' => $request->target_stage,
            'channels' => $request->channels,
        ]);

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function destroy(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
