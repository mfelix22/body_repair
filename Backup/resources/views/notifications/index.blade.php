@extends('layouts.admin')

@section('title', 'Notifications')
@section('page_title', 'All Notifications')

@section('content')
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="far fa-bell mr-1"></i> Notifications</h3>
                    <small class="text-muted">Showing {{ $notifications->total() }} notifications — all marked as read on
                        this page</small>
                </div>

                <div class="card-body p-0">
                    @forelse ($notifications as $notification)
                        <div class="d-flex align-items-start px-3 py-3" style="border-bottom: 1px solid #f4f4f4;">
                            <div class="mr-3 pt-1">
                                <i class="{{ $notification->iconClass() }}" style="font-size:1.1rem; width:18px;"></i>
                            </div>
                            <div style="flex:1;">
                                <div class="font-weight-bold" style="font-size:0.9rem;">
                                    {{ $notification->title }}
                                </div>
                                <div class="text-muted" style="font-size:0.85rem;">
                                    {{ $notification->message }}
                                </div>
                                <small class="text-muted" style="font-size:0.75rem;">
                                    {{ $notification->created_at->format('d M Y H:i') }}
                                    &bull; {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                            @if ($notification->url)
                                <div class="ml-3">
                                    <a href="{{ $notification->url }}" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="far fa-bell-slash" style="font-size:2rem;"></i>
                            <p class="mt-2">No notifications yet.</p>
                        </div>
                    @endforelse
                </div>

                @if ($notifications->hasPages())
                    <div class="card-footer">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
