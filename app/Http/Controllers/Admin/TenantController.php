<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with(['subscriptionPlan', 'users' => fn($q) => $q->where('tenant_role', 'owner')])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('store_slug', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['subscriptionPlan', 'themeSettings', 'users' => fn($q) => $q->where('tenant_role', 'owner')]);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            'plans'  => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'status'                 => 'sometimes|in:active,suspended,trial',
            'subscription_plan_id'   => 'sometimes|nullable|exists:subscription_plans,id',
            'subscription_expires_at'=> 'sometimes|nullable|date',
        ]);

        $tenant->update($request->only(['status', 'subscription_plan_id', 'subscription_expires_at']));

        return back()->with('success', 'Tenant updated successfully.');
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['status' => 'suspended']);
        return back()->with('success', "Tenant {$tenant->name} has been suspended.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active']);
        return back()->with('success', "Tenant {$tenant->name} has been activated.");
    }

    // -------------------------------------------------------------------------
    // USER MANAGEMENT
    // -------------------------------------------------------------------------

    /**
     * Change the primary owner's password from the admin panel.
     */
    public function changeOwnerPassword(Request $request, Tenant $tenant)
    {
        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $owner = $tenant->users()->where('tenant_role', 'owner')->first();
        if (!$owner) {
            return back()->withErrors(['error' => 'No owner account found for this store.']);
        }

        $owner->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', "Password for {$owner->name} has been updated.");
    }

    /**
     * Block (disable login) for the tenant owner.
     */
    public function blockOwner(Tenant $tenant)
    {
        $owner = $tenant->users()->where('tenant_role', 'owner')->first();
        if (!$owner) {
            return back()->withErrors(['error' => 'No owner account found.']);
        }

        $owner->update(['is_blocked' => true]);
        $tenant->update(['status' => 'suspended']);

        return back()->with('success', "Account for {$owner->name} has been blocked and store suspended.");
    }

    /**
     * Unblock (re-enable login) for the tenant owner.
     */
    public function unblockOwner(Tenant $tenant)
    {
        $owner = $tenant->users()->where('tenant_role', 'owner')->first();
        if (!$owner) {
            return back()->withErrors(['error' => 'No owner account found.']);
        }

        $owner->update(['is_blocked' => false]);
        $tenant->update(['status' => 'active']);

        return back()->with('success', "Account for {$owner->name} has been unblocked.");
    }

    // -------------------------------------------------------------------------
    // PLAN GIFTING
    // -------------------------------------------------------------------------

    /**
     * Gift a subscription plan to a tenant without requiring payment.
     */
    public function giftPlan(Request $request, Tenant $tenant)
    {
        $request->validate([
            'plan_id'    => 'required|exists:subscription_plans,id',
            'status'     => 'required|in:active,trial',
            'expires_at' => 'required|date|after:today',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        $tenant->update([
            'subscription_plan_id'    => $plan->id,
            'status'                  => $request->status,
            'subscription_expires_at' => $request->expires_at,
        ]);

        $expiryFormatted = Carbon::parse($request->expires_at)->format('d M Y');

        return back()->with('success', "Plan \"{$plan->name}\" has been gifted to {$tenant->name} until {$expiryFormatted}.");
    }

    // -------------------------------------------------------------------------
    // SUBDOMAIN MANAGEMENT
    // -------------------------------------------------------------------------

    public function subdomainQueue()
    {
        $pendingTenants = Tenant::where('subdomain_status', 'pending')
            ->with('subscriptionPlan')
            ->latest()
            ->get();

        return Inertia::render('Admin/Tenants/SubdomainQueue', [
            'pending_tenants' => $pendingTenants,
        ]);
    }

    public function approveSubdomain(Tenant $tenant)
    {
        $domainRequest = $tenant->domainRequests()->where('type', 'subdomain')->where('status', 'pending')->first();
        if ($domainRequest) {
            $domainRequest->update(['status' => 'active', 'linked_at' => now()]);
            $tenant->update([
                'subdomain_status' => 'active',
                'subdomain'        => $domainRequest->requested_value,
            ]);
        } else {
            $tenant->update(['subdomain_status' => 'active']);
        }

        return back()->with('success', "Subdomain for {$tenant->name} is now active.");
    }

    public function rejectSubdomain(Request $request, Tenant $tenant)
    {
        $req = $tenant->domainRequests()->where('type', 'subdomain')->where('status', 'pending')->first();
        if ($req) {
            $req->update(['status' => 'rejected', 'admin_note' => 'Rejected from Subdomain Queue']);
        }

        $tenant->update(['subdomain_status' => 'none', 'subdomain' => null]);

        return back()->with('success', "Subdomain request for {$tenant->name} has been rejected.");
    }

    // -------------------------------------------------------------------------
    // IMPERSONATION
    // -------------------------------------------------------------------------

    public function impersonate(Request $request, Tenant $tenant)
    {
        $owner = $tenant->users()->where('tenant_role', 'owner')->first();

        if (!$owner) {
            return back()->withErrors(['error' => 'No owner account found for this store.']);
        }

        session()->put('impersonating_as_tenant', $tenant->id);
        session()->put('impersonator_id', auth()->id());

        auth()->login($owner);

        return redirect()->route('merchant.dashboard')
            ->with('info', "You are now viewing the dashboard as {$tenant->name}. Click \"Return to Admin\" to exit.");
    }

    public function leaveImpersonation(Request $request)
    {
        $adminId = session()->pull('impersonator_id');
        session()->forget('impersonating_as_tenant');

        if (!$adminId) {
            return redirect()->route('admin.dashboard');
        }

        $admin = \App\Models\User::find($adminId);
        if ($admin) {
            auth()->login($admin);
        }

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Returned to admin panel.');
    }
}
