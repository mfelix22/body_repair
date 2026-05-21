@extends('layouts.admin')

@section('title', 'BOM Import Result')

@section('content')
    <div class="container-fluid">

        {{-- Summary row --}}
        <div class="row mb-3">
            <div class="col-sm-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Added</span>
                        <span class="info-box-number">{{ count($results['added']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-sync-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Updated</span>
                        <span class="info-box-number">{{ count($results['updated']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Skipped</span>
                        <span class="info-box-number">{{ count($results['skipped']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Errors</span>
                        <span class="info-box-number">{{ count($results['errors']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Added --}}
            @if (!empty($results['added']))
                <div class="col-md-6">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle mr-1 text-success"></i>
                                Added ({{ count($results['added']) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                @foreach ($results['added'] as $msg)
                                    <li class="list-group-item py-1 small text-success">{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Updated --}}
            @if (!empty($results['updated']))
                <div class="col-md-6">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sync-alt mr-1 text-warning"></i>
                                Updated ({{ count($results['updated']) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                @foreach ($results['updated'] as $msg)
                                    <li class="list-group-item py-1 small text-warning">{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Errors --}}
            @if (!empty($results['errors']))
                <div class="col-md-6">
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-times-circle mr-1 text-danger"></i>
                                Errors ({{ count($results['errors']) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                @foreach ($results['errors'] as $msg)
                                    <li class="list-group-item py-1 small text-danger">{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Skipped --}}
            @if (!empty($results['skipped']))
                <div class="col-md-6">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-minus-circle mr-1"></i>
                                Skipped ({{ count($results['skipped']) }})</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;">
                                @foreach ($results['skipped'] as $msg)
                                    <li class="list-group-item py-1 small text-muted">{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-2">
            <a href="{{ route('packages.bom_import') }}" class="btn btn-success">
                <i class="fas fa-upload mr-1"></i> Import Another File
            </a>
            <a href="{{ route('packages.index') }}" class="btn btn-secondary ml-2">
                <i class="fas fa-list mr-1"></i> Back to Packages
            </a>
        </div>
    </div>
@endsection
