<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Check if user can perform action on resource
     */
    public static function can(string $resource, string $action): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin and Admin can do everything
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $permissions = self::getPermissions();

        // Check if user has permission for this resource and action
        foreach ($user->roleList() as $role) {
            if (isset($permissions[$resource][$role])) {
                $rolePermission = $permissions[$resource][$role];

                // Business rule: warehouse can manage packages but cannot delete them.
                if ($resource === 'packages' && $action === 'delete' && $role === 'warehouse') {
                    continue;
                }

                if ($rolePermission === 'crud') {
                    return true;
                } elseif ($rolePermission === 'adjust') {
                    return true;
                } elseif ($rolePermission === 'read' && in_array($action, ['view', 'index', 'show'])) {
                    return true;
                } elseif ($rolePermission === 'read_cogs' && in_array($action, ['view', 'index', 'show'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can create resource
     */
    public static function canCreate(string $resource): bool
    {
        return self::can($resource, 'create');
    }

    /**
     * Check if user can update resource
     */
    public static function canUpdate(string $resource): bool
    {
        return self::can($resource, 'update');
    }

    /**
     * Check if user can delete resource
     */
    public static function canDelete(string $resource): bool
    {
        return self::can($resource, 'delete');
    }

    /**
     * Check if user can view resource
     */
    public static function canView(string $resource): bool
    {
        return self::can($resource, 'view');
    }

    /**
     * Check if user can adjust stock
     */
    public static function canAdjustStock(): bool
    {
        return self::can('stocks', 'adjust');
    }

    /**
     * Check if user can print a resource.
     * Only users with crud/adjust permission can print; read-only users cannot.
     */
    public static function canPrint(string $resource): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $permissions = self::getPermissions();

        foreach ($user->roleList() as $role) {
            if (isset($permissions[$resource][$role])) {
                $rolePermission = $permissions[$resource][$role];
                if (in_array($rolePermission, ['crud', 'adjust'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can view financial prices (unit prices, totals, invoice amounts).
     * Roles like warehouse, staff, and service_advisor should not see pricing data.
     */
    public static function canViewPrices(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            'super_admin',
            'admin',
            'director',
            'manager',
            'purchasing',
            'accounting',
            'finance',
            'audit',
        ]);
    }

    /**
     * Check if user can view COGS (Cost of Goods Sold)
     */
    public static function canViewCOGS(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin and Admin can do everything
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $permissions = self::getPermissions();

        // Check if user has read_cogs permission for invoices
        foreach ($user->roleList() as $role) {
            if (isset($permissions['invoices'][$role])) {
                $rolePermission = $permissions['invoices'][$role];

                if ($rolePermission === 'crud' || $rolePermission === 'read_cogs') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handle permission denial with user-friendly redirect
     */
    public static function denyAccess(string $resource, string $action = 'access')
    {
        $resourceNames = [
            'items' => 'Items',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'packages' => 'Packages',
            'labors' => 'Labor Master',
            'vehicles' => 'Vehicles',
            'uoms' => 'UOMs',
            'stocks' => 'Stock',
            'purchase_requests' => 'Purchase Requests',
            'purchase_orders' => 'Purchase Orders',
            'receivables' => 'Receivables',
            'work_orders' => 'Work Orders',
            'sales_orders' => 'Sales Orders',
            'proforma_invoices' => 'Proforma Invoices',
            'bon_outs' => 'Bon Out',
            'invoices' => 'Invoices',
            'audit_logs' => 'Audit Logs',
        ];

        $resourceName = $resourceNames[$resource] ?? ucfirst($resource);

        $actionText = match ($action) {
            'view' => 'view',
            'create' => 'create',
            'update' => 'edit',
            'delete' => 'delete',
            default => 'access'
        };

        return redirect()->route('dashboard')
            ->with('error', "You don't have permission to {$actionText} {$resourceName}. Please contact your administrator if you need access.");
    }

    /**
     * Check if user can edit a purchase request
     * Staff role can only edit their own purchase requests
     */
    public static function canEditPurchaseRequest($purchaseRequest): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admin and Admin can do everything
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $canEdit = false;

        foreach ($user->roleList() as $role) {
            $permissions = self::getPermissions();
            if (
                isset($permissions['purchase_requests'][$role]) &&
                $permissions['purchase_requests'][$role] === 'crud'
            ) {
                $canEdit = true;
                break;
            }
        }

        // If user has CRUD permission, check if they can edit
        if ($canEdit) {
            // Staff can only edit their own PR
            if ($user->hasRole('staff')) {
                return $purchaseRequest->requested_by === $user->id;
            }
            return true;
        }

        return false;
    }

    /**
     * Define permissions for each resource and role
     */
    private static function getPermissions(): array
    {
        return [
            // Master Data
            'items' => [
                'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'service_advisor' => 'read',
                'staff' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],
            'customers' => [
                'service_advisor' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],
            'labors' => [
                'service_advisor' => 'crud',
                'warehouse'       => 'crud',
                'director'        => 'crud',
                'manager'         => 'crud',
                'audit'           => 'read',
                'finance'         => 'read',
                'accounting'      => 'read',
                'viewer'          => 'read',
            ],
            'suppliers' => [
                'purchasing' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'warehouse' => 'read',
                'viewer' => 'read',
            ],
            'packages' => [
                'service_advisor' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],
            'vehicles' => [
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'service_advisor' => 'crud',
                'finance' => 'read',
                'accounting' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],
            'stocks' => [
                'warehouse' => 'adjust',
                'director' => 'adjust',
                'manager' => 'adjust',
                'audit' => 'read',
                'service_advisor' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],

            // Procurement & Inventory
            'purchase_requests' => [
                'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'staff' => 'crud',
                'accounting' => 'crud',
                'finance' => 'crud',
                'service_advisor' => 'read',
                'audit' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],
            'purchase_orders' => [
                'purchasing' => 'crud',
                'warehouse' => 'read',
                'director' => 'read',
                'manager' => 'read',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'viewer' => 'read',
            ],
            'receivables' => [
                'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'accounting' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'purchasing' => 'read',
                'viewer' => 'read',
            ],

            // Operations
            'work_orders' => [
                'service_advisor' => 'crud',
                // 'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'finance' => 'read',
                'accounting' => 'read',
                'viewer' => 'read',
            ],
            'sales_orders' => [
                'service_advisor' => 'crud',
                'director'        => 'read',
                'manager'         => 'read',
                'accounting'      => 'read',
                'finance'         => 'read',
                'audit'           => 'read',
                'viewer'          => 'read',
            ],
            'bon_outs' => [
                'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'accounting' => 'crud',
                'audit' => 'read',
                'service_advisor' => 'read',
                'finance' => 'read',
                'viewer' => 'read',
            ],

            // Finance
            'proforma_invoices' => [
                'service_advisor' => 'crud',
                'director'        => 'read',
                'manager'         => 'read',
                'accounting'      => 'read',
                'finance'         => 'read',
                'audit'           => 'read',
                'viewer'          => 'read',
            ],
            'invoices' => [
                'finance' => 'crud',
                'director' => 'read_cogs',
                'manager' => 'read_cogs',
                'accounting' => 'read_cogs',
                'audit' => 'read_cogs',
                'service_advisor' => 'read',
                'viewer' => 'read',
            ],

            // Master Data Review
            'master_data_review' => [
                'director' => 'read',
                'manager' => 'read',
                'audit' => 'read',
                'accounting' => 'read',
            ],

            // Audit
            'audit_logs' => [
                'director' => 'read',
                'manager' => 'read',
                'audit' => 'read',
                'accounting' => 'read',
                // 'finance' => 'read',
            ],

            // UOMs
            'uoms' => [
                'warehouse' => 'crud',
                'director' => 'crud',
                'manager' => 'crud',
                'audit' => 'read',
                'accounting' => 'read',
                'viewer' => 'read',
            ],
        ];
    }
}
