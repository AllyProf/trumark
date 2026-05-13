@forelse($notifications as $notification)
<li class="notification">
    <div class="media">
        <div class="media-body">
            <p>
                <strong>{{ $notification->data['message'] ?? 'New Notification' }}</strong>
                <span class="n-time text-muted"><i class="icon fa fa-clock m-r-10"></i>{{ $notification->created_at->diffForHumans() }}</span>
            </p>
            <p>
                <a href="{{ route('notifications.read', $notification->id) }}" class="btn btn-sm btn-link p-0" style="color: #940000;">View Details</a>
            </p>
        </div>
    </div>
</li>
@empty
<li class="notification">
    <div class="media">
        <div class="media-body">
            <p><strong>No new notifications</strong></p>
        </div>
    </div>
</li>
@endforelse
