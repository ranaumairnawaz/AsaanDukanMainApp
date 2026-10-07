<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object,
  theme: Object,
  cart: Array,
  totals: Object,
  coupon: Object,
})

const couponForm = useForm({
  code: props.coupon?.code || '',
})

const form = useForm({
  customer_name: '',
  customer_phone: '',
  delivery_address: '',
  notes: '',
  payment_method: 'cod',
})

const basePath = window.location.pathname.replace(/\/checkout$/, '')

function submit() {
  form.post(window.location.pathname)
}

function applyCoupon() {
  couponForm.post(`${basePath}/cart/coupon`, {
    preserveScroll: true,
  })
}

function removeCoupon() {
  couponForm.delete(`${basePath}/cart/coupon/remove`, {
    preserveScroll: true,
    onSuccess: () => couponForm.reset('code')
  })
}
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" title="Checkout">
    <div class="max-w-5xl mx-auto px-6 py-12">
      <div class="mb-10 text-center">
        <h1 class="text-3xl font-bold" style="font-family: var(--font-heading);">Checkout</h1>
        <p class="text-sm mt-2 opacity-70">Complete your order securely</p>
      </div>

      <div class="grid lg:grid-cols-5 gap-12 items-start">
        <!-- Checkout Form -->
        <div class="lg:col-span-3 space-y-8">
          <form @submit.prevent="submit" class="space-y-8">
            <!-- Delivery Info -->
            <div class="space-y-5">
              <h2 class="text-xl font-semibold border-b pb-2" style="border-color: var(--color-border);">Delivery Information</h2>
              
              <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                  <label class="block text-sm font-medium opacity-80">Full Name *</label>
                  <input v-model="form.customer_name" required type="text"
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow"
                    style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text);" />
                  <p v-if="form.errors.customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.customer_name }}</p>
                </div>
                <div class="space-y-1.5">
                  <label class="block text-sm font-medium opacity-80">Phone Number *</label>
                  <input v-model="form.customer_phone" required type="tel" placeholder="03XXXXXXXXX"
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow"
                    style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text);" />
                  <p v-if="form.errors.customer_phone" class="text-red-500 text-xs mt-1">{{ form.errors.customer_phone }}</p>
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="block text-sm font-medium opacity-80">Complete Address *</label>
                <textarea v-model="form.delivery_address" required rows="3" placeholder="House #, Street, Block, Area, City"
                  class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow resize-none"
                  style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text);"></textarea>
                <p v-if="form.errors.delivery_address" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_address }}</p>
              </div>

              <div class="space-y-1.5">
                <label class="block text-sm font-medium opacity-80">Order Notes (Optional)</label>
                <textarea v-model="form.notes" rows="2" placeholder="Any special instructions for delivery..."
                  class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-1 transition-shadow resize-none"
                  style="background: var(--color-surface); border-color: var(--color-border); color: var(--color-text);"></textarea>
              </div>
            </div>

            <!-- Payment Method -->
            <div class="space-y-5">
              <h2 class="text-xl font-semibold border-b pb-2" style="border-color: var(--color-border);">Payment Method</h2>
              <div class="space-y-3">
                <label class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all hover:bg-black/5"
                  :style="form.payment_method === 'cod' ? { borderColor: theme.accent } : { borderColor: 'var(--color-border)', background: 'var(--color-surface)' }">
                  <input type="radio" v-model="form.payment_method" value="cod" class="w-5 h-5 accent-emerald-500" />
                  <div>
                    <div class="font-bold">Cash on Delivery (COD)</div>
                    <div class="text-xs opacity-70">Pay when your order arrives</div>
                  </div>
                </label>
                <label v-if="tenant.bank_name" class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all hover:bg-black/5"
                  :style="form.payment_method === 'bank_transfer' ? { borderColor: theme.accent } : { borderColor: 'var(--color-border)', background: 'var(--color-surface)' }">
                  <input type="radio" v-model="form.payment_method" value="bank_transfer" class="w-5 h-5 accent-emerald-500" />
                  <div>
                    <div class="font-bold">Bank Transfer</div>
                    <div class="text-xs opacity-70">We will send bank details via WhatsApp</div>
                  </div>
                </label>
              </div>
              <p v-if="form.errors.payment_method" class="text-red-500 text-xs mt-1">{{ form.errors.payment_method }}</p>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="form.processing"
              class="w-full py-5 rounded-xl font-bold text-lg text-black transition-transform hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50"
              :style="{ background: theme.accent }">
              {{ form.processing ? 'Processing...' : `Place Order (Rs. ${Number(totals.total).toLocaleString()})` }}
            </button>
          </form>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-2 p-6 rounded-2xl border sticky top-24" style="background: var(--color-surface); border-color: var(--color-border);">
          <h2 class="text-lg font-semibold mb-6">Order Summary</h2>
          
          <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
            <div v-for="item in cart" :key="item.product_id" class="flex gap-4">
              <div class="w-16 h-16 rounded-lg overflow-hidden shrink-0 border" style="border-color: var(--color-border);">
                <img v-if="item.image" :src="productImageUrl(item.image)" class="w-full h-full object-cover" />
                <div v-else class="flex items-center justify-center h-full opacity-50">📦</div>
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium truncate">{{ item.name }}</h4>
                <p v-if="item.variant_name" class="text-xs opacity-60">{{ item.variant_name }}</p>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-xs font-bold opacity-80">{{ item.quantity }} × Rs. {{ Number(item.price).toLocaleString() }}</span>
                  <span class="text-sm font-bold">Rs. {{ Number(item.subtotal).toLocaleString() }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-3 pt-6 border-t" style="border-color: var(--color-border);">
            
            <!-- Coupon Input -->
            <div class="pb-3 border-b border-dashed" style="border-color: var(--color-border);">
              <form v-if="!coupon" @submit.prevent="applyCoupon" class="flex gap-2">
                <input v-model="couponForm.code" type="text" placeholder="Discount code" required
                  class="w-full px-3 py-2 rounded-xl border text-sm focus:outline-none transition-shadow"
                  style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);" />
                <button type="submit" :disabled="couponForm.processing" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-black/20 hover:bg-black/30 backdrop-blur-sm transition-colors border" style="border-color: var(--color-border);">
                  Apply
                </button>
              </form>
              <div v-else class="flex items-center justify-between p-3 rounded-xl border" style="background: var(--color-bg); border-color: var(--color-border);">
                <div class="flex items-center gap-2">
                  <span class="text-lg">🎟️</span>
                  <div>
                    <div class="text-sm font-bold uppercase tracking-wider">{{ coupon.code }}</div>
                    <div class="text-[10px] opacity-70">{{ coupon.type === 'percentage' ? coupon.value + '% off' : 'Fixed amount off' }}</div>
                  </div>
                </div>
                <button @click="removeCoupon" type="button" class="text-xs text-red-500 font-bold hover:underline">Remove</button>
              </div>
              <p v-if="couponForm.errors.code" class="text-red-500 text-xs mt-1">{{ couponForm.errors.code }}</p>
            </div>

            <div class="flex items-center justify-between text-sm opacity-80 pt-2">
              <span>Subtotal</span>
              <span>Rs. {{ Number(totals.subtotal).toLocaleString() }}</span>
            </div>
            <div v-if="totals.discount > 0" class="flex items-center justify-between text-sm text-emerald-500 font-bold">
              <span>Discount</span>
              <span>- Rs. {{ Number(totals.discount).toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between text-sm opacity-80">
              <span>Delivery</span>
              <span>Rs. {{ Number(totals.delivery).toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t text-lg font-bold" style="border-color: var(--color-border);">
              <span>Total</span>
              <span :style="{ color: theme.accent }">Rs. {{ Number(totals.total).toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StorefrontLayout>
</template>
