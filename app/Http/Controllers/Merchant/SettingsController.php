<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\OneTimeUnlockPayment;
use App\Services\PlanFeatureService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(PlanFeatureService $featureService)
    {
        $tenant = app('tenant');

        $canChangeStoreInfo = $featureService->isEnabled($tenant, 'store_info_change_allowed');
        $storeInfoFee       = $featureService->getValue($tenant, 'store_info_change_fee', 1000);
        $themeChangeFee     = $featureService->getValue($tenant, 'theme_change_fee', 500);

        // Pending unlock requests for this tenant
        $pendingUnlocks = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->pluck('unlock_type')
            ->toArray();

        // Approved-but-not-yet-applied unlocks
        $approvedUnlocks = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->where('applied', false)
            ->pluck('unlock_type')
            ->toArray();

        return Inertia::render('Merchant/Settings/Index', [
            'can_change_store_info'  => $canChangeStoreInfo || in_array('store_info_change', $approvedUnlocks),
            'store_info_change_fee'  => $storeInfoFee,
            'theme_change_fee'       => $themeChangeFee,
            'pending_unlocks'        => $pendingUnlocks,
            'approved_unlocks'       => $approvedUnlocks,
            'tenant' => [
                'name'           => $tenant->name,
                'store_slug'     => $tenant->store_slug,
                'whatsapp_number'=> $tenant->whatsapp_number,
                'delivery_charge'=> $tenant->delivery_charge,
                'bank_name'      => $tenant->bank_name,
                'account_title'  => $tenant->account_title,
                'account_number' => $tenant->account_number,
            ]
        ]);
    }

    public function update(Request $request, PlanFeatureService $featureService)
    {
        $tenant = app('tenant');
        $canChangeStoreInfo = $featureService->isEnabled($tenant, 'store_info_change_allowed');

        // Also allow if an approved-but-not-yet-applied unlock exists
        $hasApprovedUnlock = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', 'store_info_change')
            ->where('status', 'approved')
            ->where('applied', false)
            ->exists();

        $allowStoreInfoChange = $canChangeStoreInfo || $hasApprovedUnlock;

        $rules = [
            'whatsapp_number' => 'nullable|string|max:20',
            'delivery_charge' => 'required|numeric|min:0',
            'bank_name'       => 'nullable|string|max:255',
            'account_title'   => 'nullable|string|max:255',
            'account_number'  => 'nullable|string|max:255',
        ];

        if ($allowStoreInfoChange) {
            $rules['name']       = 'required|string|max:255';
            $rules['store_slug'] = 'required|string|max:255|unique:tenants,store_slug,' . $tenant->id;
        }

        $validated = $request->validate($rules);

        if (isset($validated['store_slug'])) {
            $validated['store_slug'] = Str::slug($validated['store_slug']);
        }

        $tenant->update($validated);

        // Mark the unlock as applied so it can't be used again
        if ($hasApprovedUnlock) {
            OneTimeUnlockPayment::where('tenant_id', $tenant->id)
                ->where('unlock_type', 'store_info_change')
                ->where('status', 'approved')
                ->where('applied', false)
                ->update(['applied' => true]);
        }

        return back()->with('success', 'Store settings updated successfully.');
    }

    /**
     * Submit a one-time unlock payment request (proof screenshot upload).
     */
    public function requestUnlock(Request $request, PlanFeatureService $featureService)
    {
        $tenant = app('tenant');
        $unlockType = $request->input('unlock_type'); // 'theme_change' | 'store_info_change'

        if (!in_array($unlockType, ['theme_change', 'store_info_change'])) {
            return back()->withErrors(['unlock_type' => 'Invalid unlock type.']);
        }

        // Already has a pending request?
        $alreadyPending = OneTimeUnlockPayment::where('tenant_id', $tenant->id)
            ->where('unlock_type', $unlockType)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('info', 'You already have a pending unlock request for this feature. Please wait for admin approval.');
        }

        $request->validate([
            'payment_proof' => 'required|image|max:3072',
        ]);

        $fee = $unlockType === 'theme_change'
            ? $featureService->getValue($tenant, 'theme_change_fee', 500)
            : $featureService->getValue($tenant, 'store_info_change_fee', 1000);

        $proofPath = $request->file('payment_proof')->store("unlock_proofs/{$tenant->id}", 'public');

        OneTimeUnlockPayment::create([
            'tenant_id'          => $tenant->id,
            'unlock_type'        => $unlockType,
            'amount'             => $fee,
            'status'             => 'pending',
            'payment_proof_path' => $proofPath,
        ]);

        return back()->with('success', 'Your payment proof has been submitted! Admin will approve it shortly and your feature will be unlocked.');
    }
}

