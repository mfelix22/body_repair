@php
    $recentNotifications = \App\Models\UserNotification::where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->limit(8)
        ->get();
    $unreadDropdownCount = $recentNotifications->whereNull('read_at')->count();
@endphp

<span class="dropdown-header border-bottom pb-2">
    <strong><i class="far fa-bell mr-1"></i> Notifications</strong>
    @if ($unreadDropdownCount > 0)
        <span class="badge badge-danger ml-1">{{ $unreadDropdownCount }} new</span>
    @endif
</span>

@forelse ($recentNotifications as $notification)
    <a href="{{ route('notifications.read', $notification) }}"
        class="dropdown-item py-2 {{ is_null($notification->read_at) ? 'font-weight-bold' : 'text-muted' }}"
        style="white-space: normal; border-bottom: 1px solid #f0f0f0;">
        <div class="d-flex align-items-start">
            <i class="{{ $notification->iconClass() }} mr-2 mt-1" style="width:14px;"></i>
            <div style="flex:1; min-width:0;">
                <div style="font-size:0.85rem;">{{ $notification->title }}</div>
                <small class="{{ is_null($notification->read_at) ? 'text-secondary' : 'text-muted' }}"
                    style="font-size:0.75rem; white-space:normal;">
                    {{ Str::limit($notification->message, 60) }}
                </small><br>
                <small class="text-muted" style="font-size:0.7rem;">
                    {{ $notification->created_at->diffForHumans() }}
                </small>
            </div>
        </div>
    </a>
@empty
    <span class="dropdown-item-text text-muted py-3 text-center">
        <i class="far fa-bell-slash mr-1"></i> No notifications yet.
    </span>
@endforelse

<div class="dropdown-divider mb-0"></div>
<div class="d-flex">
    <a href="{{ route('notifications.index') }}" class="dropdown-item text-center text-primary py-2"
        style="flex:1; font-size:0.85rem;">
        View All
    </a>
    @if ($unreadDropdownCount > 0)
        <form action="{{ route('notifications.markAllRead') }}" method="POST" style="flex:1;">
            @csrf
            <button type="submit" class="dropdown-item text-center text-secondary py-2 w-100"
                style="font-size:0.85rem; border:none; background:none;">
                Mark All Read
            </button>
        </form>
    @endif
</div>
