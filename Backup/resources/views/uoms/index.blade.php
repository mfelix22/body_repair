@extends('layouts.admin')

@section('title', 'UOMs')
@section('page_title', 'Unit of Measures')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">UOM List</h3>
                    <div class="card-tools">
                        <a href="{{ route('uoms.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add UOM
                        </a>
                        <a href="{{ route('uoms.conversions') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-exchange-alt"></i> Conversions
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Conversions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($uoms as $uom)
                                <tr>
                                    <td><strong>{{ $uom->code }}</strong></td>
                                    <td>{{ $uom->name }}</td>
                                    <td>{{ $uom->description ?? '-' }}</td>
                                    <td>
                                        @if ($uom->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            From: {{ $uom->conversions_from_count }} | To: {{ $uom->conversions_to_count }}
                                        </small>
                                    </td>
                                    <td>
                                        <a href="{{ route('uoms.show', $uom) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('uoms.edit', $uom) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
