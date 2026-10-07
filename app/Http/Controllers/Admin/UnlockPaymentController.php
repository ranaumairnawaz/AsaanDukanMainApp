<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OneTimeUnlockPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnlockPaymentController extends Controller
{
    public function index()
    {
        $payments = OneTimeUnlockPayment::with('tenant')
            ->latest()
            ->paginate(30);

        return Inertia::render('Admin/UnlockPayments/Index', [
            'payments' => $payments,
        ]);
    }

    public function approve(OneTimeUnlockPayment $payment)
    {
        $payment->update(['status' => 'approved']);

        return back()->with('success', "Unlock approved for {$payment->tenant->name}. They can now use their unlocked feature once.");
    }

    public function reject(Request $request, OneTimeUnlockPayment $payment)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $payment->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', "Unlock request rejected for {$payment->tenant->name}.");
    }
}
