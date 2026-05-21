<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only)
     */
    public function index()
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to manage users. Please contact your administrator if you need access.");
        }

        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user (Admin only)
     */
    public function create()
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to create users. Please contact your administrator if you need access.");
        }

        $availableRoles = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'director' => 'Director',
            'manager' => 'Manager',
            'service_advisor' => 'Service Advisor',
            'purchasing' => 'Purchasing',
            'warehouse' => 'Warehouse',
            'staff' => 'Staff',
            'finance' => 'Finance',
            'accounting' => 'Accounting',
            'audit' => 'Audit',
        ];

        return view('users.create', compact('availableRoles'));
    }

    /**
     * Store a newly created user in database (Admin only)
     */
    public function store(Request $request)
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to create users. Please contact your administrator if you need access.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:super_admin,admin,director,manager,service_advisor,purchasing,warehouse,staff,finance,accounting,audit',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => implode('|', $validated['roles']),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Show the form for editing the specified user (Admin only)
     */
    public function edit(User $user)
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to edit users. Please contact your administrator if you need access.");
        }

        $availableRoles = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'director' => 'Director',
            'manager' => 'Manager',
            'service_advisor' => 'Service Advisor',
            'purchasing' => 'Purchasing',
            'warehouse' => 'Warehouse',
            'staff' => 'Staff',
            'finance' => 'Finance',
            'accounting' => 'Accounting',
            'audit' => 'Audit',
        ];

        return view('users.edit', compact('user', 'availableRoles'));
    }

    /**
     * Update the specified user in database (Admin only)
     */
    public function update(Request $request, User $user)
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to edit users. Please contact your administrator if you need access.");
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:super_admin,admin,director,manager,service_advisor,purchasing,warehouse,staff,finance,accounting,audit',
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => implode('|', $validated['roles']),
        ]);

        // Only update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user from database (Admin only)
     */
    public function destroy(User $user)
    {
        // Only super_admin and admin can access
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('error', "You don't have permission to delete users. Please contact your administrator if you need access.");
        }

        // Prevent deleting own account
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('users.profile', compact('user'));
    }

    public function updateSignature(Request $request)
    {
        $validated = $request->validate([
            'signature' => 'required|file|mimes:png,jpg,jpeg|max:5120', // 5MB max
        ]);

        $user = Auth::user();

        // Delete old signature if exists
        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        // Store new signature
        $path = $request->file('signature')->store('signatures', 'public');

        $user->update(['signature_path' => $path]);

        return redirect()->route('users.profile')
            ->with('success', 'Signature uploaded successfully!');
    }

    public function getSignature(User $user)
    {
        if (!$user->signature_path || !Storage::disk('public')->exists($user->signature_path)) {
            return response()->noContent();
        }

        return response()->file(Storage::disk('public')->path($user->signature_path));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('users.profile')
            ->with('success', 'Password changed successfully!');
    }
}
