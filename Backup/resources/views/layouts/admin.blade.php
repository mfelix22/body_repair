<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Body Repair - @yield('title', 'Dashboard')</title>

    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                {{-- Notification Bell --}}
                <li class="nav-item dropdown mr-2">
                    @php
                        $navUnreadCount = \App\Models\UserNotification::where('user_id', auth()->id())
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    <a href="#" class="nav-link" data-toggle="dropdown" title="Notifications">
                        <i class="far fa-bell"></i>
                        @if ($navUnreadCount > 0)
                            <span
                                class="badge badge-danger navbar-badge">{{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-right"
                        style="min-width:320px; max-height:420px; overflow-y:auto; padding:0;">
                        @include('partials.notifications_dropdown')
                    </div>
                </li>

                <li class="nav-item mr-3 d-flex align-items-center">
                    <div class="user-profile">
                        {{-- <i class="fas fa-user-circle" style="font-size: 1.5rem; margin-right: 8px;"></i> --}}
                        <a href="{{ route('users.profile') }}" class="text-dark" title="View Profile">
                            <span class="font-weight-bold">{{ Auth::user()->name }}</span>
                        </a>
                        @php
                            $roles = Auth::user()->roleList();
                            $roleStyles = [
                                'super_admin' => 'badge-dark',
                                'admin' => 'badge-danger',
                                'director' => 'badge-dark',
                                'manager' => 'badge-warning',
                                'purchasing' => 'badge-info',
                                'warehouse' => 'badge-warning',
                                'staff' => 'badge-primary',
                                'audit' => 'badge-success',
                            ];
                        @endphp
                        @if (empty($roles))
                            <span class="badge badge-secondary ml-2">No Role</span>
                        @else
                            @foreach ($roles as $role)
                                <span class="badge {{ $roleStyles[$role] ?? 'badge-secondary' }} ml-2">
                                    {{ ucwords(str_replace('_', ' ', $role)) }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                </li>
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link"><i class="fas fa-sign-out-alt"></i>
                            Logout</button>
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <span class="brand-text font-weight-light">Body Repair</span>
            </a>

            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-header">MASTER DATA</li>

                        @if (\App\Helpers\PermissionHelper::canView('uoms'))
                            <li class="nav-item">
                                <a href="{{ route('uoms.index') }}"
                                    class="nav-link {{ request()->routeIs('uoms.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-ruler"></i>
                                    <p>UOMs</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('items'))
                            <li class="nav-item">
                                <a href="{{ route('items.index') }}"
                                    class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>Items</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('customers'))
                            <li class="nav-item">
                                <a href="{{ route('customers.index') }}"
                                    class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Customers</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('suppliers'))
                            <li class="nav-item">
                                <a href="{{ route('suppliers.index') }}"
                                    class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-truck"></i>
                                    <p>Suppliers</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('packages'))
                            <li class="nav-item">
                                <a href="{{ route('packages.index') }}"
                                    class="nav-link {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-box-open"></i>
                                    <p>Product</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('vehicles'))
                            <li class="nav-item">
                                <a href="{{ route('vehicles.index') }}"
                                    class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-car"></i>
                                    <p>Vehicles</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('labors'))
                            <li class="nav-item">
                                <a href="{{ route('labors.index') }}"
                                    class="nav-link {{ request()->routeIs('labors.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-brush"></i>
                                    <p>Labor</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-header">INVENTORY</li>

                        @if (\App\Helpers\PermissionHelper::canView('stocks'))
                            <li class="nav-item">
                                <a href="{{ route('stocks.index') }}"
                                    class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-warehouse"></i>
                                    <p>Stock</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-header">PROCUREMENT</li>

                        @if (\App\Helpers\PermissionHelper::canView('purchase_requests'))
                            <li class="nav-item">
                                <a href="{{ route('purchase_requests.index') }}"
                                    class="nav-link {{ request()->routeIs('purchase_requests.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>PPB & PPJ</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('purchase_orders'))
                            <li class="nav-item">
                                <a href="{{ route('purchase_orders.index') }}"
                                    class="nav-link {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clipboard-list"></i>
                                    <p>Purchase Orders & Service Orders</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('receivables'))
                            <li class="nav-item">
                                <a href="{{ route('receivables.index') }}"
                                    class="nav-link {{ request()->routeIs('receivables.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-dolly"></i>
                                    <p>Bon In</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-header">OPERATIONS</li>

                        @if (\App\Helpers\PermissionHelper::canView('work_orders'))
                            <li class="nav-item">
                                <a href="{{ route('work_orders.index') }}"
                                    class="nav-link {{ request()->routeIs('work_orders.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>Work Orders</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('sales_orders'))
                            <li class="nav-item">
                                <a href="{{ route('sales_orders.index') }}"
                                    class="nav-link {{ request()->routeIs('sales_orders.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-shopping-cart"></i>
                                    <p>Sales Orders</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('bon_outs'))
                            <li class="nav-item">
                                <a href="{{ route('bon_outs.index') }}"
                                    class="nav-link {{ request()->routeIs('bon_outs.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-dolly-flatbed"></i>
                                    <p>Bon Out</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('invoices'))
                            <li class="nav-item">
                                <a href="{{ route('invoices.index') }}"
                                    class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>Invoices</p>
                                </a>
                            </li>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canView('proforma_invoices'))
                            <li class="nav-item">
                                <a href="{{ route('proforma_invoices.index') }}"
                                    class="nav-link {{ request()->routeIs('proforma_invoices.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>Proforma Invoice</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->hasAnyRole(['director', 'manager', 'audit', 'accounting', 'admin', 'super_admin']))
                            <li class="nav-header">ACCOUNTING & CONTROL</li>

                            <li class="nav-item">
                                <a href="{{ route('audit-logs.review') }}"
                                    class="nav-link {{ request()->routeIs('audit-logs.review') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clipboard-check"></i>
                                    <p>
                                        Master Data Review
                                        <span class="badge badge-warning ml-2">Watch</span>
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->hasAnyRole(['super_admin', 'admin']))
                            @if (!request()->routeIs('audit-logs.*') && !request()->routeIs('audit-logs.review'))
                                <li class="nav-header">SYSTEM</li>
                            @endif

                            <li class="nav-item">
                                <a href="{{ route('users.index') }}"
                                    class="nav-link {{ request()->routeIs('users.index') || request()->routeIs('users.create') || request()->routeIs('users.edit') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users-cog"></i>
                                    <p>User Management</p>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'audit', 'director', 'manager', 'accounting']))
                            @if (!Auth::user()->hasAnyRole(['super_admin', 'admin']))
                                <li class="nav-header">SYSTEM</li>
                            @endif

                            <li class="nav-item">
                                <a href="{{ route('audit-logs.index') }}"
                                    class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>Audit Logs</p>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('page_title', 'Dashboard')</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>Copyright &copy; {{ date('Y') }} Body Repair.</strong>
            All rights reserved.
        </footer>
    </div>

    <script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin/dist/js/adminlte.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 on all selects with class 'select2'
            $('.select2').select2({
                theme: 'bootstrap4',
                allowClear: true,
                placeholder: '-- Select an option --',
                width: '100%'
            });
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>

</html>
