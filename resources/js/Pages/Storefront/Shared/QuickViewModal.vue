<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  product: Object,
  theme: Object,
  tenant: Object,
})

const emit = defineEmits(['close'])

const quantity = ref(1)
const variant = ref(null)

watch(() => props.product, (newVal) => {
  quantity.value = 1
  if (newVal?.variants?.length) {
    variant.value = newVal.variants[0]
  } else {
    variant.value = null
  }
}, { immediate: true })

function effectivePrice() {
  if (!props.product) return 0;
  if (variant.value?.price_override) return Number(variant.value.price_override)
  return Number(props.product.price)
}

function addToCart() {
  if (!props.product) return;
  router.post(`${props.tenant.store_url}/cart`, {
    product_id: props.product.id,
    variant_id: variant.value?.id ?? null,
    quantity: quantity.value,
  }, { 
    preserveScroll: true,
    onSuccess: () => {
      emit('close', true); // true implies added to cart
    }
  })
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
    enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="product" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="$emit('close')">
      <div class="w-full max-w-md rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl animate-in slide-in-from-bottom-8 sm:slide-in-from-bottom-0 sm:zoom-in-95 max-h-[90vh] flex flex-col"
        style="background: var(--color-bg, #FBF6EC); border: 1px solid var(--color-border, #E7DFCF);">
        <div class="p-4 flex items-center justify-between border-b" style="border-color: var(--color-border, #E7DFCF);">
          <h3 class="font-bold text-lg truncate pr-4" style="font-family: var(--font-heading, sans-serif); color: var(--color-text, #141B2E);">{{ product.name }}</h3>
          <button @click="$emit('close')" class="p-2 -mr-2 rounded-full hover:bg-black/5 shrink-0" style="color: var(--color-text, #141B2E);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        
        <div class="overflow-y-auto p-5 space-y-5">
          <div class="aspect-square rounded-2xl overflow-hidden bg-black/5">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl opacity-30">📦</div>
          </div>

          <div>
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-extrabold" :style="{ color: theme?.accent ?? 'var(--color-accent, #1F7A4D)' }">Rs. {{ Number(effectivePrice()).toLocaleString() }}</span>
              <span v-if="product.compare_at_price" class="text-sm line-through" style="color: var(--color-muted, #737887);">
                Rs. {{ Number(product.compare_at_price).toLocaleString() }}
              </span>
            </div>
            <p v-if="product.description" class="text-sm mt-2 leading-relaxed" style="color: var(--color-muted, #737887);">{{ product.description }}</p>
          </div>

          <div v-if="product.variants?.length" class="space-y-2">
            <label class="block text-sm font-medium" style="color: var(--color-text, #141B2E);">Select Option</label>
            <div class="flex flex-wrap gap-2">
              <button v-for="v in product.variants" :key="v.id" @click="variant = v"
                class="px-3.5 py-2 rounded-xl text-sm border font-medium transition-colors"
                :style="variant?.id === v.id
                  ? { background: theme?.accent ?? 'var(--color-accent, #1F7A4D)', color: '#fff', borderColor: theme?.accent ?? 'var(--color-accent, #1F7A4D)' }
                  : { color: 'var(--color-text, #141B2E)', borderColor: 'var(--color-border, #E7DFCF)' }">
                {{ v.option_value }}
              </button>
            </div>
          </div>
        </div>

        <div class="p-4 border-t flex items-center gap-3 bg-black/5" style="border-color: var(--color-border, #E7DFCF);">
          <div class="flex items-center gap-3 border rounded-xl px-1 py-1 shrink-0 bg-white/50" style="border-color: var(--color-border, #E7DFCF);">
            <button @click="quantity > 1 && quantity--" class="w-10 h-10 rounded-lg flex items-center justify-center font-semibold" style="color: var(--color-text, #141B2E);">-</button>
            <span class="w-4 text-center text-sm font-semibold" style="color: var(--color-text, #141B2E);">{{ quantity }}</span>
            <button @click="quantity++" class="w-10 h-10 rounded-lg flex items-center justify-center font-semibold" style="color: var(--color-text, #141B2E);">+</button>
          </div>
          <button @click="addToCart" class="flex-1 py-3.5 rounded-xl text-sm font-bold shadow-lg active:scale-95 transition-all" :style="{ background: theme?.accent ?? 'var(--color-accent, #1F7A4D)', color: '#fff' }">
            Add to Cart
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
