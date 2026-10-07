<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainRequest;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DomainRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = DomainRequest::with('tenant')
            ->orderByRaw("CASE WHEN status IN ('pending', 'payment_submitted') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Domains/Index', [
            'requests' => $requests,
        ]);
    }

    public function setQuote(Request $request, DomainRequest $domainRequest, WhatsAppMessageService $waService)
    {
        if ($domainRequest->type !== 'custom_domain' || $domainRequest->status !== 'pending') {
            return back()->with('error', 'Invalid request state for setting a quote.');
        }

        $validated = $request->validate([
            'quoted_price' => 'required|numeric|min:0',
        ]);

        $domainRequest->update([
            'status' => 'quote_sent',
            'quoted_price' => $validated['quoted_price'],
        ]);

        $waService->notifyMerchantCustomDomainQuote($domainRequest);

        return back()->with('success', 'Quote sent to merchant via WhatsApp.');
    }

    public function approve(Request $request, DomainRequest $domainRequest, WhatsAppMessageService $waService)
    {
        // For subdomains, we can approve directly from 'pending'.
        // For custom domains, we expect 'payment_submitted' before approval.
        if ($domainRequest->type === 'custom_domain' && $domainRequest->status !== 'payment_submitted') {
            return back()->with('error', 'Cannot approve custom domain until payment is submitted.');
        }

        $domainRequest->update([
            'status' => 'active',
            'linked_at' => now(),
        ]);

        // Update tenant
        $tenant = $domainRequest->tenant;
        if ($domainRequest->type === 'subdomain') {
            $tenant->update([
                'subdomain' => $domainRequest->requested_value,
                'subdomain_status' => 'active',
            ]);
        } else {
            $tenant->update([
                'custom_domain' => $domainRequest->requested_value,
            ]);
        }

        $waService->notifyMerchantDomainApproved($domainRequest);

        return back()->with('success', 'Domain request approved and linked to tenant.');
    }

    public function reject(Request $request, DomainRequest $domainRequest, WhatsAppMessageService $waService)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $domainRequest->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note'] ?? 'Rejected by admin.',
        ]);

        // If it was a subdomain, reset tenant's status
        if ($domainRequest->type === 'subdomain') {
            $domainRequest->tenant->update([
                'subdomain_status' => 'none',
                'subdomain' => null,
            ]);
        }

        $waService->notifyMerchantDomainRejected($domainRequest);

        return back()->with('success', 'Domain request rejected.');
    }
}
