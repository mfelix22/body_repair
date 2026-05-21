@extends('layouts.admin')

@section('title', $item->name)
@section('page_title', 'Item: ' . $item->name)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $item->name }} ({{ $item->code }})</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canUpdate('items'))
                            <a href="{{ route('items.edit', $item) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <h5>Basic Information</h5>
                    <table class="table">
                        <tr>
                            <th width="25%">Item Type:</th>
                            <td>
                                <span class="badge badge-primary">{{ $item->item_type }}</span> -
                                {{ $item->item_type_name }}
                            </td>
                        </tr>
                        <tr>
                            <th>Code:</th>
                            <td>{{ $item->code }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $item->name }}</td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>{{ $item->category ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $item->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Smallest UOM:</th>
                            <td>{{ $item->smallestUom->name }} ({{ $item->smallestUom->code }})</td>
                        </tr>
                        <tr>
                            <th>Reorder Level:</th>
                            <td>{{ number_format($item->reorder_level, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <hr>
                    <h5>Current Stock</h5>
                    @if ($item->stocks->isNotEmpty())
                        @foreach ($item->stocks as $stock)
                            <p><strong>{{ $stock->location }}:</strong> {{ number_format($stock->quantity, 2) }}
                                {{ $item->smallestUom->code }}</p>
                            @if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting', 'audit', 'director', 'manager']))
                                <p><strong>Avg. Cost:</strong> Rp {{ number_format($stock->avg_cost, 2) }}
                                    <small class="text-muted">/ {{ $item->smallestUom->code }}</small>
                                </p>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No stock record yet.</p>
                    @endif

                    <hr>
                    <h5>Available UOMs</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>UOM</th>
                                <th>Conversion Factor</th>
                                <th>Default</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($item->itemUoms as $itemUom)
                                <tr>
                                    <td>{{ $itemUom->uom->name }} ({{ $itemUom->uom->code }})</td>
                                    <td>{{ number_format($itemUom->conversion_to_smallest, 2) }}</td>
                                    <td>
                                        @if ($itemUom->is_default)
                                            <span class="badge badge-info">Yes</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Cost Adjustment Section (accounting + super_admin only to edit; wider audience to view) --}}
    @if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting', 'audit', 'director', 'manager']))
        <div class="row">

            {{-- Adjust Form --}}
            @if (Auth::user()->hasAnyRole(['super_admin', 'accounting']))
                <div class="col-md-5">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i> Adjust Average Cost</h3>
                        </div>
                        <div class="card-body">
                            @if ($item->stocks->where('location', 'default')->isNotEmpty())
                                @php $defaultStock = $item->stocks->where('location', 'default')->first(); @endphp
                                <div class="callout callout-info py-2 mb-3">
                                    <strong>Current Avg. Cost:</strong>
                                    Rp {{ number_format($defaultStock->avg_cost, 2) }}
                                    <small class="text-muted">/ {{ $item->smallestUom->code }}</small>
                                </div>

                                <form action="{{ route('items.adjustCost', $item) }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="new_avg_cost">New Average Cost <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="new_avg_cost" id="new_avg_cost" step="0.01"
                                                min="0.01"
                                                class="form-control @error('new_avg_cost') is-invalid @enderror"
                                                value="{{ old('new_avg_cost') }}" placeholder="0.00" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">/ {{ $item->smallestUom->code }}</span>
                                            </div>
                                        </div>
                                        @error('new_avg_cost')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="reason">Reason / Justification <span
                                                class="text-danger">*</span></label>
                                        <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror"
                                            placeholder="Explain why the cost is being adjusted (min 10 characters)" required>{{ old('reason') }}</textarea>
                                        <small class="form-text text-muted">This reason will be permanently recorded in the
                                            audit log.</small>
                                        @error('reason')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('Are you sure you want to adjust the average cost? This action will be logged.')">
                                        <i class="fas fa-save mr-1"></i> Save Adjustment
                                    </button>
                                </form>
                            @else
                                <p class="text-muted"><i class="fas fa-exclamation-circle mr-1"></i> No default stock record
                                    exists for this item yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Adjustment Audit Log --}}
            <div class="{{ Auth::user()->hasAnyRole(['super_admin', 'accounting']) ? 'col-md-7' : 'col-md-12' }}">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Cost Adjustment History</h3>
                        <div class="card-tools">
                            <span class="badge badge-secondary">{{ $costAdjustments->count() }} record(s)</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @forelse ($costAdjustments as $adj)
                            <div class="px-3 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div style="font-size:0.85rem;">
                                            <span class="text-danger font-weight-bold">
                                                Rp {{ number_format($adj->old_avg_cost, 2) }}
                                            </span>
                                            <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:0.75rem;"></i>
                                            <span class="text-success font-weight-bold">
                                                Rp {{ number_format($adj->new_avg_cost, 2) }}
                                            </span>
                                            <small class="text-muted ml-1">/ {{ $item->smallestUom->code }}</small>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:0.8rem;">
                                            <i class="fas fa-user mr-1"></i>{{ $adj->adjustedBy->name ?? '-' }}
                                            &bull;
                                            <i class="fas fa-clock mr-1"></i>{{ $adj->created_at->format('d M Y H:i') }}
                                            <span class="text-muted">({{ $adj->created_at->diffForHumans() }})</span>
                                        </div>
                                        <div class="mt-1" style="font-size:0.82rem;">
                                            <i class="fas fa-comment-alt mr-1 text-secondary"></i>
                                            <em>{{ $adj->reason }}</em>
                                        </div>
                                    </div>
                                    <div class="ml-3 text-right" style="min-width:80px;">
                                        @php
                                            $diff = $adj->new_avg_cost - $adj->old_avg_cost;
                                        @endphp
                                        @if ($diff > 0)
                                            <span class="badge badge-success">+{{ number_format($diff, 2) }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ number_format($diff, 2) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history" style="font-size:1.5rem;"></i>
                                <p class="mt-2 mb-0">No cost adjustments have been made yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    @endif
@endsection
