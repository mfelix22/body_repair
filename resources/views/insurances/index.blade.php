@extends('layouts.admin')

@section('title', 'Insurance Master')
@section('page_title', 'Insurance Master')

@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Insurance</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('insurances.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Insurance List</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insurances as $insurance)
                                <tr>
                                    <td>{{ $insurance->name }}</td>
                                    <td>{{ $insurance->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <a href="{{ route('insurances.edit', $insurance) }}"
                                            class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('insurances.destroy', $insurance) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Delete this insurance?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
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
