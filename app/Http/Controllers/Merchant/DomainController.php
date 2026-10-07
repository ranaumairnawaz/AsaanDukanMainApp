<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\DomainRequest;
use App\Services\PlanFeatureService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DomainController extends Controller
{
    public function index(PlanFeatureService $featureService)
    {
        $tenant = auth()->user()->tenant;

        $subdomainAllowed = $featureService->isEnabled($tenant, 'subdomain_allowed');
        $customDomainAllowed = $featureService->isEnabled($tenant, 'custom_domain_allowed');

        $activeSubdomainRequest = $tenant->activeDomainRequest('subdomain');
        $activeCustomDomainRequest = $tenant->activeDomainRequest('custom_domain');

        return Inertia::render('Merchant/Domain/Index', [
            'subdomain_allowed' => $subdomainAllowed,
            'custom_domain_allowed' => $customDomainAllowed,
            'current_subdomain' => $tenant->subdomain,
            'subdomain_status' => $tenant->subdomain_status,
            'current_custom_domain' => $tenant->custom_domain,
            'subdomain_request' => $activeSubdomainRequest,
            'custom_domain_request' => $activeCustomDomainRequest,
            'bank_accounts' => \App\Models\GlobalSetting::getVal('payment_accounts', []),
        ]);
    }

    public function applySubdomain(Request $request, PlanFeatureService $featureService, WhatsAppMessageService $waService)
    {
        $tenant = auth()->user()->tenant;

        if (!$featureService->isEnabled($tenant, 'subdomain_allowed')) {
            return back()->with('error', 'Your current plan does not allow subdomains.');
        }

        $request->validate([
            'requested_value' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9\-]+$/',
        ]);

        $value = strtolower(trim($request->requested_value));

        // Check if requested subdomain is already taken by an active store
        $taken = \App\Models\Tenant::where('subdomain', $value)->where('id', '!=', $tenant->id)->exists();
        if ($taken) {
            return back()->withErrors(['requested_value' => 'This subdomain is already taken.']);
        }

        // Cancel previous pending requests
        $tenant->domainRequests()->where('type', 'subdomain')->whereNotIn('status', ['active', 'rejected'])->update(['status' => 'rejected']);

        $domainRequest = DomainRequest::create([
            'tenant_id' => $tenant->id,
            'type' => 'subdomain',
            'requested_value' => $value,
            'status' => 'pending',
        ]);

        $tenant->update(['subdomain_status' => 'pending']);

        // Notify admin via WA
        $waService->notifyAdminSubdomainRequest($tenant, $value);

        return back()->with('success', 'Subdomain request submitted for approval.');
    }

    public function applyCustomDomain(Request $request, PlanFeatureService $featureService, WhatsAppMessageService $waService)
    {
        $tenant = auth()->user()->tenant;

        if (!$featureService->isEnabled($tenant, 'custom_domain_allowed')) {
            return back()->with('error', 'Your current plan does not allow custom domains.');
        }

        $request->validate([
            'requested_value' => 'required|string|regex:/^[a-zA-Z0-9\-\.]+$/', // e.g. mystore.com
            'merchant_owns_domain' => 'nullable|boolean',
        ]);

        $value = strtolower(trim($request->requested_value));
        $ownsDomain = $request->boolean('merchant_owns_domain');

        // Cancel previous pending requests
        $tenant->domainRequests()->where('type', 'custom_domain')->whereNotIn('status', ['active', 'rejected'])->update(['status' => 'rejected']);

        if ($ownsDomain) {
            $fee = \App\Models\GlobalSetting::getVal('custom_domain_connection_fee', 0);
            
            $domainRequest = DomainRequest::create([
                'tenant_id' => $tenant->id,
                'type' => 'custom_domain',
                'merchant_owns_domain' => true,
                'requested_value' => $value,
                'status' => 'quote_sent', // directly to quote sent
                'quoted_price' => $fee,
            ]);

            // Notify admin via WA
            $waService->notifyAdminCustomDomainRequest($tenant, $value);

            return back()->with('success', 'Custom domain connection request submitted. Please complete the payment.');
        } else {
            $domainRequest = DomainRequest::create([
                'tenant_id' => $tenant->id,
                'type' => 'custom_domain',
                'merchant_owns_domain' => false,
                'requested_value' => $value,
                'status' => 'pending',
            ]);

            // Notify admin via WA
            $waService->notifyAdminCustomDomainRequest($tenant, $value);

            return back()->with('success', 'Custom domain request submitted. You will receive a price quote shortly.');
        }
    }

    public function submitPayment(Request $request, WhatsAppMessageService $waService)
    {
        $tenant = auth()->user()->tenant;

        $request->validate([
            'domain_request_id' => 'required|exists:domain_requests,id',
            'proof_image' => 'required|image|max:5120',
        ]);

        $domainRequest = DomainRequest::where('tenant_id', $tenant->id)
            ->findOrFail($request->domain_request_id);

        if ($domainRequest->status !== 'quote_sent') {
            return back()->with('error', 'Invalid request state for payment.');
        }

        $proofPath = $request->file('proof_image')->store("domain_payments/{$tenant->id}", 'public');

        $domainRequest->update([
            'status' => 'payment_submitted',
            'payment_proof_path' => $proofPath,
        ]);

        // Notify admin via WA
        $waService->notifyAdminDomainPaymentSubmitted($domainRequest);

        return back()->with('success', 'Payment proof submitted. Admin will link your domain shortly.');
    }
}
