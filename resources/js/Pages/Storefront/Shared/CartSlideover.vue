<script setup>
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  isOpen: Boolean,
  tenant: Object,
  theme: Object,
})

const emit = defineEmits(['close'])

const cart = ref([])
const subtotal = ref(0)
const loading = ref(true)

async function fetchCart() {
  loading.value = true
  try {
    const res = await fetch(`${props.tenant.store_url}/cart`, {
      headers: { 'Accept': 'application/json' }
    })
    const data = await res.json()
    cart.value = Object.entries(data.cart).map(([key, item]) => ({ key, ...item }))
    subtotal.value = data.subtotal
  } catch (e) {
    console.error('Failed to fetch cart', e)
  } finally {
    loading.value = false
  }
}

function removeItem(key) {
  router.delete(`${props.tenant.store_url}/cart/${key}`, {
    preserveScroll: true,
    onSuccess: () => fetchCart()
  })
}

// When opened, fetch latest cart
onMounted(() => {
  if (props.isOpen) fetchCart()
})

import { watch } from 'vue'
watch(() => props.isOpen, (newVal) => {
  if (newVal) fetchCart()
})
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-[100] flex justify-end">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

    <!-- Drawer -->
    <div class="relative w-full max-w-md h-full flex flex-col shadow-2xl transition-transform transform translate-x-0"
      style="background: var(--color-surface); color: var(--color-text);">
      
      <!-- Header -->
      <div class="px-6 py-5 border-b flex items-center justify-between" style="border-color: var(--color-border);">
        <h2 class="text-xl font-bold" style="font-family: var(--font-heading);">Your Cart</h2>
        <button @click="emit('close')" class="p-2 hover:opacity-70 transition-opacity">
          ✕
        </button>
      </div>

      <!-- Items -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6">
        <div v-if="loading" class="text-center opacity-50 py-10">Loading cart...</div>
        <div v-else-if="cart.length === 0" class="text-center py-20 opacity-50">
          <div class="text-6xl mb-4">🛒</div>
          <p>Your cart is empty.</p>
          <button @click="emit('close')" class="mt-4 underline">Continue Shopping</button>
        </div>
        
        <div v-else class="space-y-6">
          <div v-for="item in cart" :key="item.key" class="flex gap-4">
            <!-- Image -->
            <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0 border" style="border-color: var(--color-border); background: var(--color-bg);">
              <img v-if="item.image" :src="productImageUrl(item.image)" class="w-full h-full object-cover" />
              <div v-else class="flex items-center justify-center h-full text-2xl">📦</div>
            </div>
            
            <!-- Details -->
            <div class="flex-1 min-w-0">
              <h3 class="font-medium text-sm truncate pr-6">{{ item.name }}</h3>
              <p v-if="item.variant_name" class="text-xs mt-0.5 opacity-70">{{ item.variant_name }}</p>
              <div class="flex items-center justify-between mt-2">
                <span class="text-sm font-bold">Rs. {{ Number(item.price).toLocaleString() }}</span>
                <span class="text-xs opacity-70">Qty: {{ item.quantity }}</span>
              </div>
            </div>
            
            <!-- Remove -->
            <button @click="removeItem(item.key)" class="shrink-0 text-red-500 hover:bg-red-500/10 p-2 rounded-lg transition-colors h-fit text-sm">
              🗑
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="!loading && cart.length > 0" class="p-6 border-t" style="border-color: var(--color-border); background: var(--color-bg);">
        <div class="flex items-center justify-between mb-4">
          <span class="font-semibold">Subtotal</span>
          <span class="font-bold text-lg">Rs. {{ Number(subtotal).toLocaleString() }}</span>
        </div>
        <p class="text-xs opacity-60 mb-4">Delivery and taxes calculated at checkout.</p>
        <a :href="`${tenant.store_url}/checkout`" 
          class="block w-full py-4 text-center rounded-xl font-bold text-black transition-transform hover:scale-[1.02]"
          :style="{ background: theme.accent }">
          Proceed to Checkout
        </a>
      </div>
    </div>
  </div>
</template>
