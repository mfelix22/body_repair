@extends('layouts.admin')

@section('title', $uom->name)
@section('page_title', 'UOM: ' . $uom->name)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $uom->name }} ({{ $uom->code }})</h3>
                    <div class="card-tools">
                        <a href="{{ route('uoms.edit', $uom) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="30%">Code:</th>
                            <td>{{ $uom->code }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $uom->name }}</td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $uom->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if ($uom->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
