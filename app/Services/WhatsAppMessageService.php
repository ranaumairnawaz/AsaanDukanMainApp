<?php

namespace App\Services;

use App\Models\DomainRequest;
use App\Models\GlobalSetting;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\WhatsAppMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageService
{
    /**
     * Normalize a local Pakistani number (03xxxxxxxxx) to international format (923xxxxxxxxx).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '03')) {
            return '92' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Dispatch a WhatsApp message job to the queue.
     */
    public function send(string $recipientPhone, string $message, array $context = []): bool
    {
        $phone = $this->normalizePhone($recipientPhone);

        \App\Jobs\SendWhatsAppMessage::dispatch($phone, $message, $context);

        return true;
    }

    protected function logAttempt(string $phone, array $context, string $status, ?string $errorMessage): void
    {
        try {
            WhatsAppMessageLog::create([
                'tenant_id' => $context['tenant_id'] ?? null,
                'order_id' => $context['order_id'] ?? null,
                'direction' => 'outbound',
                'recipient_phone' => $phone,
                'template_used' => $context['type'] ?? null,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to write whatsapp_message_log: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────
    //  Helper: send to admin
    // ─────────────────────────────────────────────

    protected function getAdminPhone(): ?string
    {
        return GlobalSetting::getVal('admin_whatsapp_number');
    }

    protected function sendToAdmin(string $message, array $context = []): bool
    {
        $phone = $this->getAdminPhone();
        if (!$phone) {
            Log::warning('Admin WhatsApp number not configured (admin_whatsapp_number).');
            return false;
        }
        return $this->send($phone, $message, $context);
    }

    // ─────────────────────────────────────────────
    //  Admin Notifications
    // ─────────────────────────────────────────────

    public function notifyAdminSubdomainRequest(Tenant $tenant, string $requestedSubdomain): bool
    {
        $message = "🌐 *New Subdomain Request*\n\n";
        $message .= "Store: *{$tenant->name}*\n";
        $message .= "Requested: *{$requestedSubdomain}.assandukan.online*\n";
        $message .= "Plan: " . ($tenant->subscriptionPlan?->name ?? 'Unknown') . "\n\n";
        $message .= "Review in Admin Panel: " . url('/admin/domains');

        return $this->sendToAdmin($message, ['tenant_id' => $tenant->id, 'type' => 'admin_subdomain_request']);
    }

    public function notifyAdminCustomDomainRequest(Tenant $tenant, string $domain): bool
    {
        $message = "🔗 *New Custom Domain Request*\n\n";
        $message .= "Store: *{$tenant->name}*\n";
        $message .= "Requested Domain: *{$domain}*\n";
        $message .= "Plan: " . ($tenant->subscriptionPlan?->name ?? 'Unknown') . "\n\n";
        $message .= "Set a price and review: " . url('/admin/domains');

        return $this->sendToAdmin($message, ['tenant_id' => $tenant->id, 'type' => 'admin_custom_domain_request']);
    }

    public function notifyAdminNewPayment(SubscriptionPayment $payment): bool
    {
        $tenant = $payment->tenant;
        $plan = $payment->plan;
        $message = "💳 *New Subscription Payment*\n\n";
        $message .= "Store: *{$tenant->name}*\n";
        $message .= "Plan: *{$plan->name}*\n";
        $message .= "Amount: Rs. " . number_format($payment->amount, 0) . "\n";
        $message .= "Method: {$payment->payment_method}\n\n";
        $message .= "Review: " . url('/admin/payments');

        return $this->sendToAdmin($message, ['tenant_id' => $tenant->id, 'type' => 'admin_new_payment']);
    }

    public function notifyAdminDomainPaymentSubmitted(DomainRequest $domainRequest): bool
    {
        $tenant = $domainRequest->tenant;
        $message = "💰 *Domain Payment Submitted*\n\n";
        $message .= "Store: *{$tenant->name}*\n";
        $message .= "Domain: *{$domainRequest->requested_value}*\n";
        $message .= "Quoted: Rs. " . number_format($domainRequest->quoted_price, 0) . "\n\n";
        $message .= "Approve and link domain: " . url('/admin/domains');

        return $this->sendToAdmin($message, ['tenant_id' => $tenant->id, 'type' => 'admin_domain_payment']);
    }

    // ─────────────────────────────────────────────
    //  Merchant Domain Notifications
    // ─────────────────────────────────────────────

    public function notifyMerchantCustomDomainQuote(DomainRequest $domainRequest): bool
    {
        $tenant = $domainRequest->tenant;
        if (!$tenant->whatsapp_number)
            return false;

        $message = "🏷️ *Custom Domain Quote Ready*\n\n";
        $message .= "Hi *{$tenant->name}*! Your custom domain request for *{$domainRequest->requested_value}* has been reviewed.\n\n";
        $message .= "Price: *Rs. " . number_format($domainRequest->quoted_price, 0) . "*\n\n";
        $message .= "Login to pay: " . url('/merchant/domain');

        return $this->send($tenant->whatsapp_number, $message, ['tenant_id' => $tenant->id, 'type' => 'merchant_domain_quote']);
    }

    public function notifyMerchantDomainApproved(DomainRequest $domainRequest): bool
    {
        $tenant = $domainRequest->tenant;
        if (!$tenant->whatsapp_number)
            return false;

        $typeLabel = $domainRequest->type === 'subdomain' ? 'Subdomain' : 'Custom Domain';
        $storeUrl = $tenant->fresh()->store_url;
        $message = "✅ *{$typeLabel} Approved!*\n\n";
        $message .= "Hi *{$tenant->name}*! Your {$typeLabel} *{$domainRequest->requested_value}* is now live.\n\n";
        $message .= "Your store: *{$storeUrl}*";

        return $this->send($tenant->whatsapp_number, $message, ['tenant_id' => $tenant->id, 'type' => 'merchant_domain_approved']);
    }

    public function notifyMerchantDomainRejected(DomainRequest $domainRequest): bool
    {
        $tenant = $domainRequest->tenant;
        if (!$tenant->whatsapp_number)
            return false;

        $typeLabel = $domainRequest->type === 'subdomain' ? 'Subdomain' : 'Custom Domain';
        $reason = $domainRequest->admin_note ? "\n\nReason: {$domainRequest->admin_note}" : '';
        $message = "❌ *{$typeLabel} Request Rejected*\n\n";
        $message .= "Hi *{$tenant->name}*! Your {$typeLabel} request for *{$domainRequest->requested_value}* was not approved.{$reason}\n\n";
        $message .= "Contact support or reapply: " . url('/merchant/domain');

        return $this->send($tenant->whatsapp_number, $message, ['tenant_id' => $tenant->id, 'type' => 'merchant_domain_rejected']);
    }

    // ─────────────────────────────────────────────
    //  Subscription Expiry Alerts
    // ─────────────────────────────────────────────

    public function notifyMerchantSubscriptionExpiringSoon(Tenant $tenant, int $daysLeft): bool
    {
        if (!$tenant->whatsapp_number)
            return false;

        if ($daysLeft <= 0) {
            $message = "🚨 *Subscription Expired*\n\n";
            $message .= "Hi *{$tenant->name}*! Your AssanDukan subscription has *expired today*.\n\n";
            $message .= "Your store is at risk of suspension. Renew immediately:\n" . url('/merchant/billing');
        } elseif ($daysLeft === 1) {
            $message = "⚠️ *Subscription Expiring Tomorrow*\n\n";
            $message .= "Hi *{$tenant->name}*! Your subscription expires *tomorrow*. Renew now to avoid any disruption:\n" . url('/merchant/billing');
        } else {
            $message = "⏰ *Subscription Expiring in {$daysLeft} Days*\n\n";
            $message .= "Hi *{$tenant->name}*! Your AssanDukan plan expires in *{$daysLeft} days*.\n\n";
            $message .= "Renew to keep your store running:\n" . url('/merchant/billing');
        }

        return $this->send($tenant->whatsapp_number, $message, ['tenant_id' => $tenant->id, 'type' => 'subscription_expiry_alert']);
    }

    // ─────────────────────────────────────────────
    //  Order Notifications
    // ─────────────────────────────────────────────

    public function sendOrderConfirmedNotification(Order $order): bool
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return false;
        }

        $tenantName = $order->tenant->name;
        $message = "Hi {$customer->name}! 👋\n\n";
        $message .= "Great news — your order *{$order->order_number}* from *{$tenantName}* has been confirmed! ✅\n\n";
        $message .= "Total: Rs. " . number_format($order->total, 2) . "\n\n";
        $message .= "We'll notify you again once it ships. Thanks for shopping with us!";

        return $this->send($customer->phone, $message, [
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'type' => 'order_confirmed',
        ]);
    }

    public function sendMerchantOrderConfirmedAlert(Order $order): bool
    {
        $tenant = $order->tenant;
        if (!$tenant || !$tenant->whatsapp_number) {
            return false;
        }

        $customerName = $order->customer->name ?? 'A customer';
        $message = "✅ Order Confirmed\n\n";
        $message .= "Order *{$order->order_number}* for *{$customerName}* has been marked as confirmed.\n\n";
        $message .= "Total: Rs. " . number_format($order->total, 2) . "\n";
        $message .= "View it in your Merchant Panel to manage fulfillment.";

        return $this->send($tenant->whatsapp_number, $message, [
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'type' => 'merchant_order_confirmed',
        ]);
    }

    public function sendMerchantNewOrderAlert(Order $order, string $message): bool
    {
        $tenant = $order->tenant;
        if (!$tenant || !$tenant->whatsapp_number) {
            return false;
        }

        return $this->send($tenant->whatsapp_number, $message, [
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'type' => 'new_order',
        ]);
    }

    public function notifyMerchantWelcome(Tenant $tenant, \App\Models\User $user): bool
    {
        if (!$tenant->whatsapp_number && !$user->phone)
            return false;
        $phone = $tenant->whatsapp_number ?? $user->phone;

        $storeUrl = config('app.url') . "/store/" . $tenant->store_slug;

        $message = "🎉 *Welcome to Asaan Dukan, {$user->name}!*\n\n";
        $message .= "Your new online store *{$tenant->name}* has been successfully created.\n\n";
        $message .= "You are currently on a *7-Day Free Trial*. Take this time to explore the premium features.\n\n";
        $message .= "Visit your storefront here:\n{$storeUrl}\n\n";
        $message .= "Need help? Just reply to this message!";

        return $this->send($phone, $message, [
            'tenant_id' => $tenant->id,
            'type' => 'merchant_welcome',
        ]);
    }
    public function broadcastCommunityPost(string $title, string $bodyHtml, string $type): void
    {
        \App\Jobs\BroadcastCommunityNotification::dispatch($title, strip_tags($bodyHtml), $type);
    }
}
