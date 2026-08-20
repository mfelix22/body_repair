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
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    @stack('styles')

    <style>
        .card-tools {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }
        .table .badge {
            white-space: normal;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            line-height: 1;
            padding: .2em .4em;
        }
        .table td {
            word-break: break-word;
        }
        @media (max-width: 767.98px) {
            .form-inline {
                display: block;
            }
            .form-inline .form-group {
                margin-bottom: .75rem;
            }
            .form-inline .form-control,
            .form-inline select,
            .form-inline .btn {
                width: 100%;
            }
            .dataTables_length,
            .dataTables_filter,
            .dataTables_paginate {
                float: none !important;
                text-align: left !important;
            }
            .dataTables_filter input {
                width: 100%;
                max-width: 100%;
            }
            .content-header h1 {
                font-size: 1.4rem;
            }
            .card-title {
                font-size: 1.1rem;
            }
            .main-header .navbar-nav.ml-auto {
                flex-wrap: wrap;
            }
            .main-header .user-profile {
                flex-wrap: wrap;
            }
            .main-header .nav-link {
                padding-left: .5rem;
                padding-right: .5rem;
            }
        }
    </style>
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
                    <a href="#" class="nav-link" id="notif-bell" data-toggle="dropdown" title="Notifications">
                        <i class="far fa-bell"></i>
                        <span id="notif-badge" class="badge badge-danger navbar-badge"
                            style="{{ $navUnreadCount > 0 ? '' : 'display:none;' }}">
                            {{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}
                        </span>
                    </a>
                    <div id="notif-dropdown" class="dropdown-menu dropdown-menu-right"
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
                                'viewer' => 'badge-info',
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

                        {{-- Dashboard --}}
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        {{-- ===== MASTER DATA ===== --}}
                        @php
                            $mdRoutes = [
                                'uoms.*',
                                'items.*',
                                'customers.*',
                                'suppliers.*',
                                'packages.*',
                                'vehicles.*',
                                'labors.*',
                            ];
                            $mdOpen = request()->routeIs($mdRoutes);
                            $mdVisible =
                                \App\Helpers\PermissionHelper::canView('uoms') ||
                                \App\Helpers\PermissionHelper::canView('items') ||
                                \App\Helpers\PermissionHelper::canView('customers') ||
                                \App\Helpers\PermissionHelper::canView('suppliers') ||
                                \App\Helpers\PermissionHelper::canView('packages') ||
                                \App\Helpers\PermissionHelper::canView('vehicles') ||
                                \App\Helpers\PermissionHelper::canView('labors');
                        @endphp
                        @if ($mdVisible)
                            <li class="nav-item has-treeview {{ $mdOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $mdOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-database"></i>
                                    <p>Master Data <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if (\App\Helpers\PermissionHelper::canView('customers'))
                                        <li class="nav-item">
                                            <a href="{{ route('customers.index') }}"
                                                class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                                                <i class="fas fa-users nav-icon"></i>
                                                <p>Customers</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('suppliers'))
                                        <li class="nav-item">
                                            <a href="{{ route('suppliers.index') }}"
                                                class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                                                <i class="fas fa-truck nav-icon"></i>
                                                <p>Suppliers</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('items'))
                                        <li class="nav-item">
                                            <a href="{{ route('items.index') }}"
                                                class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                                                <i class="fas fa-boxes nav-icon"></i>
                                                <p>Items</p>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- @if (\App\Helpers\PermissionHelper::canView('packages'))
                                        <li class="nav-item">
                                            <a href="{{ route('packages.index') }}"
                                                class="nav-link {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                                                <i class="fas fa-box-open nav-icon"></i>
                                                <p>Product</p>
                                            </a>
                                        </li>
                                    @endif --}}
                                    @if (\App\Helpers\PermissionHelper::canView('vehicles'))
                                        <li class="nav-item">
                                            <a href="{{ route('vehicles.index') }}"
                                                class="nav-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
                                                <i class="fas fa-car nav-icon"></i>
                                                <p>Vehicles</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('labors'))
                                        <li class="nav-item">
                                            <a href="{{ route('labors.index') }}"
                                                class="nav-link {{ request()->routeIs('labors.*') ? 'active' : '' }}">
                                                <i class="fas fa-wrench nav-icon"></i>
                                                <p>Panel</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('insurances'))
                                        <li class="nav-item">
                                            <a href="{{ route('insurances.index') }}"
                                                class="nav-link {{ request()->routeIs('insurances.*') ? 'active' : '' }}">
                                                <i class="fas fa-shield-alt nav-icon"></i>
                                                <p>Insurances</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('uoms'))
                                        <li class="nav-item">
                                            <a href="{{ route('uoms.index') }}"
                                                class="nav-link {{ request()->routeIs('uoms.*') ? 'active' : '' }}">
                                                <i class="fas fa-ruler nav-icon"></i>
                                                <p>UOMs</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        {{-- ===== INVENTORY ===== --}}
                        @if (\App\Helpers\PermissionHelper::canView('stocks'))
                            @php $invOpen = request()->routeIs('stocks.*'); @endphp
                            <li class="nav-item has-treeview {{ $invOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $invOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-warehouse"></i>
                                    <p>Inventory <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('stocks.index') }}"
                                            class="nav-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                                            <i class="fas fa-boxes nav-icon"></i>
                                            <p>Stock</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- ===== PROCUREMENT ===== --}}
                        @php
                            $procRoutes = [
                                'purchase_requests.*',
                                'purchase_orders.*',
                                'vendor_comparisons.*',
                                'receivables.*',
                            ];
                            $procOpen = request()->routeIs($procRoutes);
                            $procVisible =
                                \App\Helpers\PermissionHelper::canView('purchase_requests') ||
                                \App\Helpers\PermissionHelper::canView('purchase_orders') ||
                                \App\Helpers\PermissionHelper::canView('receivables') ||
                                auth()
                                    ->user()
                                    ->hasAnyRole(['purchasing', 'director', 'admin', 'super_admin']);
                        @endphp
                        @if ($procVisible)
                            <li class="nav-item has-treeview {{ $procOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $procOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-truck-loading"></i>
                                    <p>Procurement <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if (\App\Helpers\PermissionHelper::canView('purchase_requests'))
                                        <li class="nav-item">
                                            <a href="{{ route('purchase_requests.index') }}"
                                                class="nav-link {{ request()->routeIs('purchase_requests.*') ? 'active' : '' }}">
                                                <i class="fas fa-file-alt nav-icon"></i>
                                                <p>PPB &amp; PPJ</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('purchase_orders'))
                                        <li class="nav-item">
                                            <a href="{{ route('purchase_orders.index') }}"
                                                class="nav-link {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}">
                                                <i class="fas fa-clipboard-list nav-icon"></i>
                                                <p>Purchase &amp; Service Orders</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (auth()->user()->hasAnyRole(['purchasing', 'director', 'admin', 'super_admin']))
                                        <li class="nav-item">
                                            <a href="{{ route('vendor_comparisons.index') }}"
                                                class="nav-link {{ request()->routeIs('vendor_comparisons.*') ? 'active' : '' }}">
                                                <i class="fas fa-balance-scale nav-icon"></i>
                                                <p>Perbandingan Vendor</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('receivables'))
                                        <li class="nav-item">
                                            <a href="{{ route('receivables.index') }}"
                                                class="nav-link {{ request()->routeIs('receivables.*') ? 'active' : '' }}">
                                                <i class="fas fa-dolly nav-icon"></i>
                                                <p>Bon In</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        {{-- ===== OPERATIONS ===== --}}
                        @php
                            $opsRoutes = [
                                'work_orders.*',
                                'sales_orders.*',
                                'bon_outs.*',
                                'invoices.*',
                                'credit_notes.*',
                                'proforma_invoices.*',
                                'estimasis.*',
                            ];
                            $opsOpen = request()->routeIs($opsRoutes);
                            $opsVisible =
                                \App\Helpers\PermissionHelper::canView('work_orders') ||
                                \App\Helpers\PermissionHelper::canView('sales_orders') ||
                                \App\Helpers\PermissionHelper::canView('bon_outs') ||
                                \App\Helpers\PermissionHelper::canView('invoices') ||
                                \App\Helpers\PermissionHelper::canView('proforma_invoices') ||
                                \App\Helpers\PermissionHelper::canView('estimasis') ||
                                auth()
                                    ->user()
                                    ->hasAnyRole([
                                        'super_admin',
                                        'admin',
                                        'finance',
                                        'director',
                                        'manager',
                                        'accounting',
                                        'audit',
                                    ]);
                        @endphp
                        @if ($opsVisible)
                            <li class="nav-item has-treeview {{ $opsOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $opsOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>Operations <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @if (\App\Helpers\PermissionHelper::canView('work_orders'))
                                        <li class="nav-item">
                                            <a href="{{ route('work_orders.index') }}"
                                                class="nav-link {{ request()->routeIs('work_orders.*') ? 'active' : '' }}">
                                                <i class="fas fa-tools nav-icon"></i>
                                                <p>Work Orders</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('sales_orders'))
                                        <li class="nav-item">
                                            <a href="{{ route('sales_orders.index') }}"
                                                class="nav-link {{ request()->routeIs('sales_orders.*') ? 'active' : '' }}">
                                                <i class="fas fa-shopping-cart nav-icon"></i>
                                                <p>Sales Orders</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('bon_outs'))
                                        <li class="nav-item">
                                            <a href="{{ route('bon_outs.index') }}"
                                                class="nav-link {{ request()->routeIs('bon_outs.*') ? 'active' : '' }}">
                                                <i class="fas fa-dolly-flatbed nav-icon"></i>
                                                <p>Bon Out</p>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- @if (\App\Helpers\PermissionHelper::canView('proforma_invoices'))
                                        <li class="nav-item">
                                            <a href="{{ route('proforma_invoices.index') }}"
                                                class="nav-link {{ request()->routeIs('proforma_invoices.*') ? 'active' : '' }}">
                                                <i class="fas fa-file-alt nav-icon"></i>
                                                <p>Proforma Invoice</p>
                                            </a>
                                        </li>
                                    @endif --}}
                                    @if (\App\Helpers\PermissionHelper::canView('estimasis'))
                                        <li class="nav-item">
                                            <a href="{{ route('estimasis.index') }}"
                                                class="nav-link {{ request()->routeIs('estimasis.*') ? 'active' : '' }}">
                                                <i class="fas fa-file-invoice nav-icon"></i>
                                                <p>Estimasi</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (\App\Helpers\PermissionHelper::canView('invoices'))
                                        <li class="nav-item">
                                            <a href="{{ route('invoices.index') }}"
                                                class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                                <i class="fas fa-file-invoice-dollar nav-icon"></i>
                                                <p>Invoices</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'finance', 'director', 'manager', 'accounting', 'audit']))
                                        <li class="nav-item">
                                            <a href="{{ route('credit_notes.index') }}"
                                                class="nav-link {{ request()->routeIs('credit_notes.*') ? 'active' : '' }}">
                                                <i class="fas fa-file-excel nav-icon"></i>
                                                <p>Credit Notes</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        {{-- ===== ACCOUNTING & CONTROL ===== --}}
                        @if (Auth::user()->hasAnyRole(['director', 'audit', 'accounting', 'admin', 'super_admin']))
                            @php
                                $acctRoutes = ['audit-logs.review', 'audit-logs.*'];
                                $acctOpen = request()->routeIs($acctRoutes);
                            @endphp
                            <li class="nav-item has-treeview {{ $acctOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $acctOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Audit <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('audit-logs.review') }}"
                                            class="nav-link {{ request()->routeIs('audit-logs.review') ? 'active' : '' }}">
                                            <i class="fas fa-clipboard-check nav-icon"></i>
                                            <p>Master Data Review
                                                <span class="badge badge-warning ml-1">Watch</span>
                                            </p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('audit-logs.index') }}"
                                            class="nav-link {{ request()->routeIs('audit-logs.index') ? 'active' : '' }}">
                                            <i class="fas fa-history nav-icon"></i>
                                            <p>Audit Logs</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- ===== REPORTS ===== --}}
                        @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit']))
                            @php $rptOpen = request()->routeIs('reports.*'); @endphp
                            <li class="nav-item has-treeview {{ $rptOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $rptOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chart-bar"></i>
                                    <p>Reports <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('reports.sparepart') }}"
                                            class="nav-link {{ request()->routeIs('reports.sparepart*') ? 'active' : '' }}">
                                            <i class="fas fa-boxes nav-icon"></i>
                                            <p>Sparepart Usage</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- ===== SYSTEM ===== --}}
                        @if (Auth::user()->hasAnyRole(['super_admin', 'admin']))
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}"
                                    class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users-cog"></i>
                                    <p>User Management</p>
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
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('info') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/dist/js/adminlte.min.js') }}"></script>

    <script>
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true
        });
    </script>

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

    {{-- ===== REAL-TIME NOTIFICATION POLLING ===== --}}
    <script>
        (function() {
            const POLL_URL = '{{ route('notifications.poll') }}';
            const POLL_EVERY = 30000; // 30 seconds
            const $badge = $('#notif-badge');
            const $dropdown = $('#notif-dropdown');
            const $bell = $('#notif-bell');

            let lastCount = parseInt($badge.text()) || 0;

            function iconHtml(iconClass) {
                return `<i class="${iconClass} mr-2 mt-1" style="width:14px;"></i>`;
            }

            function renderDropdown(data) {
                let html = `<span class="dropdown-header border-bottom pb-2">
                    <strong><i class="far fa-bell mr-1"></i> Notifications</strong>
                    ${data.unread_count > 0 ? `<span class="badge badge-danger ml-1">${data.unread_count} new</span>` : ''}
                </span>`;

                if (data.items.length === 0) {
                    html += `<span class="dropdown-item-text text-muted py-3 text-center">
                        <i class="far fa-bell-slash mr-1"></i> No notifications yet.
                    </span>`;
                } else {
                    data.items.forEach(function(n) {
                        const boldClass = n.read ? 'text-muted' : 'font-weight-bold';
                        const smallClass = n.read ? 'text-muted' : 'text-secondary';
                        html += `<a href="${n.url}" class="dropdown-item py-2 ${boldClass}"
                            style="white-space:normal; border-bottom:1px solid #f0f0f0;">
                            <div class="d-flex align-items-start">
                                ${iconHtml(n.icon)}
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:0.85rem;">${n.title}</div>
                                    <small class="${smallClass}" style="font-size:0.75rem; white-space:normal;">${n.message}</small><br>
                                    <small class="text-muted" style="font-size:0.7rem;">${n.ago}</small>
                                </div>
                            </div>
                        </a>`;
                    });
                }

                html += `<div class="dropdown-divider mb-0"></div>
                    <a href="{{ route('notifications.index') }}" class="dropdown-item text-center text-primary py-2">
                        View all notifications
                    </a>`;

                return html;
            }

            function doPoll() {
                $.getJSON(POLL_URL)
                    .done(function(data) {
                        const count = data.unread_count;

                        // Update badge
                        if (count > 0) {
                            $badge.text(count > 99 ? '99+' : count).show();
                        } else {
                            $badge.hide();
                        }

                        // If new notifications arrived, ring & update dropdown
                        if (count > lastCount) {
                            $bell.addClass('animate__animated animate__tada');
                            setTimeout(() => $bell.removeClass('animate__animated animate__tada'), 1500);

                            // Only re-render dropdown if it is NOT currently open
                            if (!$dropdown.hasClass('show')) {
                                $dropdown.html(renderDropdown(data));
                            }
                        }

                        lastCount = count;
                    })
                    .fail(function() {
                        // Silently ignore network errors — will retry on next interval
                    });
            }

            // Start polling after an initial delay so it doesn't fire right on load
            setTimeout(function() {
                doPoll();
                setInterval(doPoll, POLL_EVERY);
            }, POLL_EVERY);
        })();
    </script>

    <script>
        $(document).ready(function () {
            $('table.table:not([id])').each(function () {
                var $table = $(this);
                if ($table.closest('.table-responsive').length) {
                    return;
                }
                $table.wrap('<div class="table-responsive"></div>');
            });
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>

</html>
