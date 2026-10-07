<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object,
  theme: Object,
  order: Object,
  error: String,
})

const form = useForm({
  order_number: '',
  phone: '',
})

const submit = () => {
  form.post(`${props.tenant.store_url}/track`)
}

const statusMap = {
  pending:    { label: 'Order Received',       icon: '📋', color: '#f59e0b', step: 1 },
  confirmed:  { label: 'Confirmed',             icon: '✅', color: '#3b82f6', step: 2 },
  processing: { label: 'Preparing Your Order',  icon: '📦', color: '#8b5cf6', step: 3 },
  shipped:    { label: 'Out for Delivery',      icon: '🚚', color: '#06b6d4', step: 4 },
  delivered:  { label: 'Delivered',             icon: '🎉', color: '#10b981', step: 5 },
  cancelled:  { label: 'Cancelled',             icon: '❌', color: '#ef4444', step: 0 },
}

const currentStatus = () => statusMap[props.order?.status] ?? statusMap.pending
const steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered']
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" title="Track Your Order">
    <div class="max-w-3xl mx-auto px-6 py-16">

      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-3" style="font-family: var(--font-heading);">Track Your Order</h1>
        <p class="text-base opacity-60">Enter your Order ID and phone number to check your delivery status.</p>
      </div>

      <!-- Search Form -->
      <div class="p-8 rounded-2xl border mb-10" style="background: var(--color-surface); border-color: var(--color-border);">
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-5">
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-80">Order ID</label>
              <input v-model="form.order_number" type="text" required placeholder="e.g. ORD-AB12CD34"
                class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow uppercase"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text); font-family: monospace;" />
              <p v-if="form.errors.order_number" class="text-red-500 text-xs">{{ form.errors.order_number }}</p>
            </div>
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-80">Phone Number</label>
              <input v-model="form.phone" type="tel" required placeholder="03XXXXXXXXX"
                class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);" />
              <p v-if="form.errors.phone" class="text-red-500 text-xs">{{ form.errors.phone }}</p>
            </div>
          </div>
          <button type="submit" :disabled="form.processing"
            class="w-full py-4 rounded-xl font-bold text-black transition-all hover:scale-[1.01] disabled:opacity-60"
            :style="{ background: theme.accent }">
            {{ form.processing ? 'Searching...' : 'Track Order' }}
          </button>
        </form>
      </div>

      <!-- Error -->
      <div v-if="error" class="p-5 rounded-2xl border border-red-500/30 bg-red-500/10 text-center mb-8">
        <p class="text-red-500 font-medium">{{ error }}</p>
        <p class="text-xs opacity-60 mt-1">Double-check your Order ID and phone number. Order IDs are case-insensitive.</p>
      </div>

      <!-- Order Found -->
      <div v-if="order" class="space-y-8">

        <!-- Status Banner -->
        <div class="p-6 rounded-2xl border text-center" :style="{ borderColor: currentStatus().color + '40', background: currentStatus().color + '10' }">
          <div class="text-5xl mb-3">{{ currentStatus().icon }}</div>
          <h2 class="text-2xl font-bold" :style="{ color: currentStatus().color }">{{ currentStatus().label }}</h2>
          <p class="text-sm opacity-70 mt-1">Order <span class="font-mono font-bold">{{ order.order_number }}</span></p>
        </div>

        <!-- Progress Stepper -->
        <div v-if="order.status !== 'cancelled'" class="flex items-start justify-between relative px-2">
          <div class="absolute top-4 left-0 right-0 h-0.5 mx-8 rounded" style="background: var(--color-border);">
            <div class="h-full rounded transition-all duration-700" :style="{ width: ((currentStatus().step - 1) / (steps.length - 1) * 100) + '%', background: theme.accent }"></div>
          </div>
          <div v-for="(step, i) in steps" :key="step" class="relative flex flex-col items-center z-10 flex-1">
            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition-all"
              :style="currentStatus().step > i
                ? { background: theme.accent, borderColor: theme.accent, color: '#000' }
                : { background: 'var(--color-bg)', borderColor: 'var(--color-border)', color: 'var(--color-muted)' }">
              <span v-if="currentStatus().step > i">✓</span>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <p class="text-[10px] text-center mt-2 capitalize font-medium opacity-60 leading-tight w-16">{{ step }}</p>
          </div>
        </div>

        <!-- Order Items -->
        <div class="p-6 rounded-2xl border space-y-4" style="background: var(--color-surface); border-color: var(--color-border);">
          <h3 class="font-bold text-lg">Items Ordered</h3>
          <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 border" style="border-color: var(--color-border);">
              <img v-if="item.product?.images?.length" :src="productImageUrl(item.product.images[0])" class="w-full h-full object-cover" />
              <div v-else class="flex items-center justify-center h-full opacity-40">📦</div>
            </div>
            <div class="flex-1">
              <p class="font-medium text-sm">{{ item.product?.name }}</p>
              <p v-if="item.variant" class="text-xs opacity-60">{{ item.variant.option_value }}</p>
              <p class="text-xs opacity-60 mt-0.5">Qty: {{ item.quantity }}</p>
            </div>
            <span class="font-bold text-sm">Rs. {{ Number(item.subtotal).toLocaleString() }}</span>
          </div>
          <div class="pt-4 border-t space-y-2 text-sm" style="border-color: var(--color-border);">
            <div class="flex justify-between opacity-70"><span>Subtotal</span><span>Rs. {{ Number(order.subtotal).toLocaleString() }}</span></div>
            <div class="flex justify-between opacity-70"><span>Delivery</span><span>Rs. {{ Number(order.delivery_fee).toLocaleString() }}</span></div>
            <div class="flex justify-between font-extrabold text-base pt-1"><span>Total</span><span :style="{ color: theme.accent }">Rs. {{ Number(order.total).toLocaleString() }}</span></div>
          </div>
        </div>

        <!-- Delivery Info -->
        <div class="p-6 rounded-2xl border" style="background: var(--color-surface); border-color: var(--color-border);">
          <h3 class="font-bold text-lg mb-4">Delivery Information</h3>
          <div class="space-y-2 text-sm opacity-80">
            <p><span class="font-semibold">Name:</span> {{ order.customer?.name }}</p>
            <p><span class="font-semibold">Address:</span> {{ order.delivery_address }}</p>
            <p><span class="font-semibold">Payment:</span> {{ order.payment_method === 'cod' ? 'Cash on Delivery' : 'Bank Transfer' }}</p>
            <p v-if="order.notes"><span class="font-semibold">Notes:</span> {{ order.notes }}</p>
          </div>
        </div>
      </div>

    </div>
  </StorefrontLayout>
</template>
