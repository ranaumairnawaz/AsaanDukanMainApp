<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'

defineProps({
  tenant: Object,
  theme: Object,
  order: Object,
})
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" title="Order Confirmed">
    <div class="max-w-3xl mx-auto px-4 py-16 text-center space-y-8">

      <!-- Success Icon -->
      <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center shadow-lg"
        :style="{ background: theme.accent + '20', color: theme.accent }">
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <div>
        <h1 class="text-4xl font-bold mb-3" style="font-family: var(--font-heading);">Order Confirmed! 🎉</h1>
        <p class="text-lg opacity-75 max-w-lg mx-auto">
          Your order <span class="font-bold bg-black/10 px-2 py-0.5 rounded">{{ order.order_number }}</span> has been placed successfully.
        </p>
      </div>

      <!-- Next Steps -->
      <div class="p-8 rounded-3xl border text-left space-y-4" style="background: var(--color-surface); border-color: var(--color-border);">
        <h2 class="font-bold text-xl">What's Next?</h2>
        <div class="flex gap-4 opacity-80">
          <span class="text-2xl">💳</span>
          <div>
            <div class="font-semibold">Payment</div>
            <div class="text-sm mt-0.5">
              {{ order.payment_method === 'cod' ? 'Please keep cash ready at the time of delivery.' : 'Send your bank transfer screenshot via WhatsApp.' }}
            </div>
          </div>
        </div>
        <div class="flex gap-4 opacity-80">
          <span class="text-2xl">🚚</span>
          <div>
            <div class="font-semibold">Delivery</div>
            <div class="text-sm mt-0.5">Your order will be processed within 24 hours and shipped to {{ order.delivery_address }}.</div>
          </div>
        </div>
      </div>

      <!-- Continue Shopping -->
      <div>
        <a :href="tenant.store_url"
          class="inline-block px-8 py-4 rounded-xl font-bold transition-transform hover:scale-[1.02]"
          :style="{ background: theme.accent, color: '#000' }">
          Continue Shopping
        </a>
      </div>

    </div>
  </StorefrontLayout>
</template>