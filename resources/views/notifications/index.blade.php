@extends('layouts.vali')

@section('title', 'Notifications Center')

@section('page_icon', 'fa-bell')

@section('subtitle')
Manage all your system alerts and performance notifications
@endsection

@section('content')
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-envelope-o text-primary mr-2"></i> ALL NOTIFICATIONS</h5>
                
                @if(auth()->user()->unreadNotifications->count() > 0)
                <form action="{{ route('notifications.read_all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-check-all"></i> Mark All as Read
                    </button>
                </form>
                @endif
            </div>

            <div class="p-0 bg-white">
                <div class="list-group list-group-flush">
                    @forelse($notifications as $notification)
                        <div class="list-group-item list-group-item-action p-4 {{ $notification->unread() ? 'bg-light border-left-primary' : '' }}" style="{{ $notification->unread() ? 'border-left: 5px solid #940000;' : '' }}">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <span class="fa-stack fa-lg">
                                            <i class="fa fa-circle fa-stack-2x {{ $notification->data['color'] ?? 'text-primary' }}"></i>
                                            <i class="fa {{ $notification->data['icon'] ?? 'fa-info' }} fa-stack-1x fa-inverse"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 font-weight-bold text-dark">{{ $notification->data['title'] ?? 'System Notification' }}</h5>
                                        <p class="mb-1 text-secondary">{{ $notification->data['message'] ?? 'Activity recorded' }}</p>
                                        <small class="text-muted"><i class="fa fa-clock-o mr-1"></i> {{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <div>
                                    @if($notification->unread())
                                        <a href="{{ route('notifications.read', $notification->id) }}" class="btn btn-sm btn-primary">
                                            View Action
                                        </a>
                                    @else
                                        <a href="{{ $notification->data['link'] ?? '#' }}" class="btn btn-sm btn-outline-secondary">
                                            Revisit
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fa fa-bell-slash-o fa-4x d-block mb-3"></i>
                            <h4 class="font-weight-bold">No Notifications Found</h4>
                            <p>You are all caught up for now!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($notifications->hasPages())
            <div class="p-3 bg-light border-top d-flex justify-content-center">
                {{ $notifications->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .border-left-primary {
        border-left: 5px solid #940000;
    }
    .list-group-item {
        transition: background-color 0.3s;
    }
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
