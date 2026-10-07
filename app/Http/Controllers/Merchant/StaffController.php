<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index()
    {
        $tenant = auth()->user()->tenant;
        
        $staff = User::where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->get();

        return Inertia::render('Merchant/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        // Only owner can add staff
        if (auth()->user()->tenant_role !== 'owner') {
            abort(403, 'Only the store owner can add staff members.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', Password::defaults()],
            'tenant_role' => 'required|in:manager,staff',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => $tenant->id,
            'tenant_role' => $validated['tenant_role'],
        ]);

        return back()->with('success', "Staff member {$validated['name']} added successfully.");
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(User $user)
    {
        $tenant = auth()->user()->tenant;
        
        if (auth()->user()->tenant_role !== 'owner') {
            abort(403, 'Only the store owner can remove staff members.');
        }

        if ($user->tenant_id !== $tenant->id || $user->id === auth()->id()) {
            abort(403, 'Cannot remove this user.');
        }

        $user->delete();

        return back()->with('success', 'Staff member removed.');
    }
}
