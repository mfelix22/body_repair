@extends('layouts.admin')

@section('title', 'Import Result')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10">

                <div
                    class="card card-outline
                @if (count($results['errors']) > 0) card-warning
                @elseif(count($results['created']) > 0 || count($results['updated']) > 0) card-success
                @else card-secondary @endif">

                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Import Result</h3>
                        <div class="card-tools">
                            <a href="{{ route('items.import') }}" class="btn btn-sm btn-primary mr-1">
                                <i class="fas fa-upload"></i> Import Again
                            </a>
                            <a href="{{ route('items.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list"></i> View Items
                            </a>
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- Debug Info Collapsible --}}
                        @if (isset($results['debug']))
                            <div class="alert alert-secondary">
                                <div class="mb-0">
                                    <strong><i class="fas fa-bug mr-1"></i> Column Detection Debug Info</strong>
                                    <small class="ml-2">(If numbers are wrong, check here)</small>
                                </div>
                                <div class="mt-2" style="font-size:0.9em;">
                                    <strong>Detected Columns:</strong>
                                    {{ implode(', ', $results['debug']['columns_found']) }}<br>
                                    <strong>Int_Qty Column Index:</strong> {{ $results['debug']['int_qty_index'] }}<br>
                                    <strong>Conv_Qty Column Index:</strong> {{ $results['debug']['conv_qty_index'] }}<br>
                                    <strong>Int_Unit Column Index:</strong> {{ $results['debug']['int_unit_index'] }}<br>
                                    <strong>Conv_Unit Column Index:</strong> {{ $results['debug']['conv_unit_index'] }}<br>
                                    @if (!empty($results['debug']['first_row_raw']))
                                        <strong>First Data Row (raw):</strong>
                                        <code>{{ json_encode(array_values($results['debug']['first_row_raw'])) }}</code>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Summary Pills --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Created</span>
                                        <span class="info-box-number">{{ count($results['created']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-sync-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Updated</span>
                                        <span class="info-box-number">{{ count($results['updated']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-secondary">
                                    <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Skipped</span>
                                        <span class="info-box-number">{{ count($results['skipped']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Errors</span>
                                        <span class="info-box-number">{{ count($results['errors']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Error List --}}
                        @if (count($results['errors']) > 0)
                            <div class="card card-danger card-outline mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title text-danger"><i class="fas fa-times-circle mr-1"></i> Errors</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        @foreach ($results['errors'] as $e)
                                            <li class="list-group-item list-group-item-danger py-1">{{ $e }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Skipped List --}}
                        @if (count($results['skipped']) > 0)
                            <div class="card card-secondary card-outline mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title"><i class="fas fa-ban mr-1"></i> Skipped Rows</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        @foreach ($results['skipped'] as $s)
                                            <li class="list-group-item py-1">{{ $s }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Created Items --}}
                        @if (count($results['created']) > 0)
                            <div class="card card-success card-outline mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title text-success"><i class="fas fa-check-circle mr-1"></i> Created
                                        ({{ count($results['created']) }})</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                        @foreach ($results['created'] as $item)
                                            <li class="list-group-item list-group-item-success py-1">{{ $item }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Updated Items --}}
                        @if (count($results['updated']) > 0)
                            <div class="card card-info card-outline mb-0">
                                <div class="card-header py-2">
                                    <h3 class="card-title text-info"><i class="fas fa-edit mr-1"></i> Updated
                                        ({{ count($results['updated']) }})</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                        @foreach ($results['updated'] as $item)
                                            <li class="list-group-item list-group-item-info py-1">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if (count($results['created']) === 0 && count($results['updated']) === 0 && count($results['errors']) === 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> No data rows were processed. Check that the file has a
                                valid header row with <strong>SKU</strong> and <strong>Nama Barang</strong> columns.
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
