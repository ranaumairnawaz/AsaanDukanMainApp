<script setup>
import { Head } from "@inertiajs/vue3"
import { useForm } from "@inertiajs/vue3"
import { onMounted } from "vue"
import { productImageUrl } from "@/utils/productImage"

const props = defineProps({
  tenant: Object,
  theme: Object,
  cart: Array,
  totals: Object,
  coupon: Object,
})

const couponForm = useForm({
  code: props.coupon?.code || "",
})

const form = useForm({
  customer_name: "",
  customer_phone: "",
  delivery_address: "",
  notes: "",
  payment_method: "cod",
})

function applyTheme() {
  if (!props.theme?.css_vars) return
  const root = document.documentElement
  for (const [key, value] of Object.entries(props.theme.css_vars)) {
    root.style.setProperty(key, value)
  }
}
onMounted(applyTheme)

const basePath = window.location.pathname.replace(/\/checkout$/, '')

function submit() {
  form.post(window.location.pathname)
}

function applyCoupon() {
  couponForm.post(`${basePath}/cart/coupon`, { preserveScroll: true })
}

function removeCoupon() {
  couponForm.delete(`${basePath}/cart/coupon/remove`, {
    preserveScroll: true,
    onSuccess: () => couponForm.reset("code"),
  })
}
</script>

<template>
  <Head>
    <title>Checkout | {{ tenant?.name }}</title>
    <link v-if="theme?.settings?.favicon_path" rel="icon" :href="`/storage/${theme.settings.favicon_path}`" />
  </Head>

  <div
    class="min-h-screen flex flex-col pb-10"
    style="background: var(--color-bg, #FBF6EC); color: var(--color-text, #141B2E); font-family: var(--font-body, sans-serif);"
  >
    <!-- Minimal Header -->
    <header
      class="sticky top-0 z-40 flex items-center gap-3 px-4 py-3.5"
      style="background: var(--color-bg); border-bottom: 1px solid var(--color-border, #E7DFCF);"
    >
      <a :href="tenant?.store_url" class="p-2 -ml-2 rounded-lg" style="color: var(--color-text);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </a>
      <div class="flex items-center gap-2 flex-1 min-w-0">
        <img v-if="theme?.logo_path" :src="`/storage/${theme.logo_path}`" :alt="tenant?.name" class="h-8 w-auto object-contain" />
        <span v-else class="font-bold text-sm truncate" style="font-family: var(--font-heading);">{{ tenant?.name }}</span>
      </div>
      <span class="text-sm font-semibold opacity-50">Checkout</span>
    </header>

    <main class="flex-1 max-w-md mx-auto w-full px-4 py-6 space-y-5">

      <!-- Order Summary -->
      <div class="rounded-2xl" style="background: var(--color-surface, #fff); border: 1px solid var(--color-border);">
        <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color: var(--color-border);">
          <h2 class="font-bold text-base" style="font-family: var(--font-heading);">Order Summary</h2>
          <span class="text-xs opacity-50">{{ cart?.length }} item(s)</span>
        </div>
        <div class="p-5 space-y-4 max-h-52 overflow-y-auto">
          <div v-for="item in cart" :key="item.product_id" class="flex gap-3">
            <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0" style="background: var(--color-bg); border: 1px solid var(--color-border);">
              <img v-if="item.image" :src="productImageUrl(item.image)" class="w-full h-full object-cover" />
              <div v-else class="flex items-center justify-center h-full opacity-30 text-lg">??</div>
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
              <p class="text-sm font-semibold truncate">{{ item.name }}</p>
              <p v-if="item.variant_name" class="text-xs opacity-50">{{ item.variant_name }}</p>
              <div class="flex justify-between mt-1">
                <span class="text-xs opacity-60">x{{ item.quantity }}</span>
                <span class="text-sm font-bold" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">
                  Rs. {{ Number(item.subtotal).toLocaleString() }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Coupon -->
        <div class="px-5 pb-4 pt-4 border-t space-y-2" style="border-color: var(--color-border);">
          <form v-if="!coupon" @submit.prevent="applyCoupon" class="flex gap-2">
            <input v-model="couponForm.code" type="text" placeholder="Discount code" required
              class="flex-1 px-3 py-2 rounded-xl border text-sm focus:outline-none"
              style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);" />
            <button type="submit" :disabled="couponForm.processing"
              class="px-4 py-2 rounded-xl text-sm font-bold"
              style="background: var(--color-text, #141B2E); color: var(--color-bg, #FBF6EC);">Apply</button>
          </form>
          <div v-else class="flex items-center justify-between px-3 py-2 rounded-xl" style="background: var(--color-bg); border: 1px solid var(--color-border);">
            <span class="text-sm font-bold uppercase">?? {{ coupon.code }}</span>
            <button @click="removeCoupon" type="button" class="text-xs text-red-500 font-semibold">Remove</button>
          </div>
          <p v-if="couponForm.errors.code" class="text-red-500 text-xs">{{ couponForm.errors.code }}</p>
        </div>

        <!-- Totals -->
        <div class="px-5 py-4 border-t space-y-2" style="border-color: var(--color-border);">
          <div class="flex justify-between text-sm opacity-70">
            <span>Subtotal</span><span>Rs. {{ Number(totals.subtotal).toLocaleString() }}</span>
          </div>
          <div v-if="totals.discount > 0" class="flex justify-between text-sm font-semibold text-emerald-600">
            <span>Discount</span><span>- Rs. {{ Number(totals.discount).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between text-sm opacity-70">
            <span>Delivery</span><span>Rs. {{ Number(totals.delivery).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between font-bold pt-2 border-t" style="border-color: var(--color-border);">
            <span>Total</span>
            <span :style="{ color: theme?.accent ?? 'var(--color-accent)' }">Rs. {{ Number(totals.total).toLocaleString() }}</span>
          </div>
        </div>
      </div>

      <!-- Delivery Form -->
      <form @submit.prevent="submit" class="space-y-5">
        <div class="rounded-2xl" style="background: var(--color-surface, #fff); border: 1px solid var(--color-border);">
          <div class="px-5 py-4 border-b" style="border-color: var(--color-border);">
            <h2 class="font-bold text-base" style="font-family: var(--font-heading);">Delivery Details</h2>
          </div>
          <div class="p-5 space-y-4">
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-75">Full Name *</label>
              <input v-model="form.customer_name" required type="text" placeholder="Your full name"
                class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);" />
              <p v-if="form.errors.customer_name" class="text-red-500 text-xs">{{ form.errors.customer_name }}</p>
            </div>
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-75">Phone Number *</label>
              <input v-model="form.customer_phone" required type="tel" placeholder="03XXXXXXXXX"
                class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);" />
              <p v-if="form.errors.customer_phone" class="text-red-500 text-xs">{{ form.errors.customer_phone }}</p>
            </div>
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-75">Delivery Address *</label>
              <textarea v-model="form.delivery_address" required rows="3" placeholder="House #, Street, Area, City"
                class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none resize-none"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);"></textarea>
              <p v-if="form.errors.delivery_address" class="text-red-500 text-xs">{{ form.errors.delivery_address }}</p>
            </div>
            <div class="space-y-1.5">
              <label class="block text-sm font-medium opacity-75">Order Notes (Optional)</label>
              <textarea v-model="form.notes" rows="2" placeholder="Special instructions..."
                class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none resize-none"
                style="background: var(--color-bg); border-color: var(--color-border); color: var(--color-text);"></textarea>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="rounded-2xl" style="background: var(--color-surface, #fff); border: 1px solid var(--color-border);">
          <div class="px-5 py-4 border-b" style="border-color: var(--color-border);">
            <h2 class="font-bold text-base" style="font-family: var(--font-heading);">Payment Method</h2>
          </div>
          <div class="p-5 space-y-3">
            <label class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all"
              :style="form.payment_method === 'cod' ? { borderColor: theme?.accent, background: (theme?.accent ?? '#1F7A4D') + '0f' } : { borderColor: 'var(--color-border)' }">
              <input type="radio" v-model="form.payment_method" value="cod" class="w-4 h-4 shrink-0" />
              <div>
                <div class="font-semibold text-sm">Cash on Delivery</div>
                <div class="text-xs opacity-60">Pay when your order arrives</div>
              </div>
            </label>
            <label v-if="tenant.bank_name" class="flex items-center gap-4 p-4 rounded-xl border cursor-pointer transition-all"
              :style="form.payment_method === 'bank_transfer' ? { borderColor: theme?.accent, background: (theme?.accent ?? '#1F7A4D') + '0f' } : { borderColor: 'var(--color-border)' }">
              <input type="radio" v-model="form.payment_method" value="bank_transfer" class="w-4 h-4 shrink-0" />
              <div>
                <div class="font-semibold text-sm">Bank Transfer</div>
                <div class="text-xs opacity-60">{{ tenant.bank_name }} – {{ tenant.account_number }}</div>
              </div>
            </label>
          </div>
        </div>

        <button type="submit" :disabled="form.processing"
          class="w-full py-4 rounded-2xl font-bold text-base shadow-lg active:scale-[0.98] transition-all disabled:opacity-50"
          :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
          {{ form.processing ? "Processing..." : `Place Order – Rs. ${Number(totals.total).toLocaleString()}` }}
        </button>
      </form>
    </main>

    <footer class="text-center text-xs py-5 px-4 opacity-50">
      Orders are confirmed on WhatsApp. Powered by AssanDukan.online
    </footer>
  </div>
</template>

