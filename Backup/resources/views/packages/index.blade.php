@extends('layouts.admin')

@section('title', 'Product')
@section('page_title', 'Product Management')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Products</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('packages'))
                            <a href="{{ route('packages.bom_import') }}" class="btn btn-success btn-sm mr-1">
                                <i class="fas fa-file-excel"></i> Import BOM
                            </a>
                            <a href="{{ route('packages.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Product
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @foreach ($groupedPackages as $category => $packages)
                        <h5 class="text-primary mt-3">
                            <i class="fas fa-tags"></i> {{ $category }}
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 100px">Code</th>
                                        <th>Package Name</th>
                                        <th>Sizes & Prices</th>
                                        <th style="width: 80px">Status</th>
                                        <th style="width: 120px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($packages as $package)
                                        <tr>
                                            <td><strong>{{ $package->code }}</strong></td>
                                            <td>{{ $package->name }}</td>
                                            <td>
                                                @foreach ($package->sizes as $size)
                                                    <span
                                                        class="badge badge-{{ $size->is_active ? 'info' : 'secondary' }} mr-1">
                                                        {{ $size->size_name }}: Rp
                                                        {{ number_format($size->price, 0, ',', '.') }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">
                                                    {{ $package->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (\App\Helpers\PermissionHelper::canUpdate('packages'))
                                                    <a href="{{ route('packages.edit', $package) }}"
                                                        class="btn btn-warning btn-xs">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    @if ($groupedPackages->isEmpty())
                        <p class="text-muted text-center py-4">No products found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
