<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object, product: Object, related_products: Array, theme: Object,
})

const selectedVariant = ref(props.product.variants?.[0] ?? null)
const activeImage = ref(0)
const quantity = ref(1)

const effectivePrice = () => {
  if (selectedVariant.value?.price_override) return Number(selectedVariant.value.price_override)
  return Number(props.product.price)
}

function addToCart() {
  router.post(`${props.tenant.store_url}/cart`, {
    product_id: props.product.id,
    variant_id: selectedVariant.value?.id ?? null,
    quantity: quantity.value
  }, {
    preserveScroll: true
  })
}
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" :title="product.name" :seoDescription="product.description?.replace(/(<([^>]+)>)/gi, '').substring(0,160) || 'Buy ' + product.name" :seoImage="product.images?.length ? productImageUrl(product.images[0]) : ''">
    <div class="max-w-7xl mx-auto px-6 py-12">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs font-mono text-[var(--color-text)] mb-8 uppercase">
        <a href="./" class="transition-colors" :style="{ color: theme.accent }"
          @mouseover="$event.currentTarget.style.opacity = 0.8"
          @mouseleave="$event.currentTarget.style.opacity = 1">Catalog</a>
        <span>/</span>
        <span>{{ product.category ?? 'Device' }}</span>
        <span>/</span>
        <span class="text-[var(--color-text)] truncate">{{ product.sku || product.id }}</span>
      </div>

      <div class="grid md:grid-cols-2 gap-12 lg:gap-20">
        <!-- Images -->
        <div class="space-y-4">
          <div class="aspect-square bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl overflow-hidden p-8 flex items-center justify-center">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[activeImage])" :alt="product.name" class="w-full h-full object-cover drop-shadow-2xl" />
            <div v-else class="text-8xl">💻</div>
          </div>
          <div v-if="product.images?.length > 1" class="flex gap-3">
            <button v-for="(img, i) in product.images" :key="i" @click="activeImage = i"
              class="w-20 h-20 bg-[var(--color-surface)] rounded-xl overflow-hidden border-2 transition-all p-2"
              :style="activeImage === i ? { borderColor: theme.accent } : { borderColor: 'var(--color-border)' }">
              <img :src="productImageUrl(img)" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Details -->
        <div class="space-y-8">
          <div>
            <h1 class="text-3xl font-bold text-[var(--color-text)] mb-4">{{ product.name }}</h1>
            <div class="flex items-center gap-4">
              <span class="text-4xl font-extrabold" :style="{ color: theme.accent }">Rs. {{ Number(effectivePrice()).toLocaleString() }}</span>
              <span v-if="product.compare_at_price" class="text-xl line-through text-[var(--color-text)]">Rs. {{ Number(product.compare_at_price).toLocaleString() }}</span>
            </div>
            <div v-if="product.stock_quantity > 0" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded border border-emerald-500/20">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              In Stock ({{ product.stock_quantity }})
            </div>
            <div v-else class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-red-400 bg-red-500/10 px-2.5 py-1 rounded border border-red-500/20">
              Out of Stock
            </div>
          </div>

          <!-- Specs / Description -->
          <div class="border-t border-b border-[var(--color-border)] py-6">
            <h3 class="text-sm font-bold text-[var(--color-text)] uppercase tracking-wider mb-4">Specifications</h3>
            <div class="text-sm leading-relaxed text-[var(--color-text)] whitespace-pre-wrap font-mono">{{ product.description }}</div>
          </div>

          <!-- Variants -->
          <div v-if="product.variants?.length" class="space-y-4">
            <label class="block text-sm font-bold text-[var(--color-text)] uppercase">{{ product.variants[0].option_name }} Configuration</label>
            <div class="grid grid-cols-2 gap-3">
              <button v-for="variant in product.variants" :key="variant.id" @click="selectedVariant = variant"
                class="px-4 py-3 rounded-lg text-sm font-bold transition-all border text-left"
                :class="selectedVariant?.id === variant.id ? '' : 'bg-[var(--color-surface)] border-[var(--color-border)] text-[var(--color-text)] hover:border-[var(--color-border)]'"
                :style="selectedVariant?.id === variant.id ? { color: theme.accent, borderColor: theme.accent, backgroundColor: `color-mix(in srgb, ${theme.accent} 10%, transparent)` } : {}">
                <div>{{ variant.option_value }}</div>
                <div v-if="variant.price_override" class="text-xs font-normal mt-1 opacity-80 font-mono">
                  Rs. {{ Number(variant.price_override).toLocaleString() }}
                </div>
              </button>
            </div>
          </div>

          <!-- CTA -->
          <div class="pt-4 flex items-center gap-4">
            <div class="flex items-center bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl">
              <button @click="quantity > 1 && quantity--" class="px-4 py-3 text-white hover:text-white hover:bg-[var(--color-surface)] transition-colors rounded-l-xl">−</button>
              <span class="w-8 text-center text-sm font-bold text-white">{{ quantity }}</span>
              <button @click="quantity++" class="px-4 py-3 text-white hover:text-white hover:bg-[var(--color-surface)] transition-colors rounded-r-xl">+</button>
            </div>

            <button @click="addToCart"
              class="flex-1 flex items-center justify-center gap-3 w-full py-4 rounded-xl text-base font-bold text-white transition-all active:scale-95 hover:opacity-90"
              :style="{ backgroundColor: theme.accent }">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  </StorefrontLayout>

    <!-- Related Products -->
    <section v-if="related_products?.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-12 border-t mt-12" style="border-color: var(--color-border);">
      <h2 class="text-2xl font-bold mb-8" style="font-family: var(--font-heading); color: var(--color-text);">You Might Also Like</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <Link v-for="rel in related_products" :key="rel.id" :href="`${tenant.store_url}/product/${rel.id}`"
          class="group rounded-xl overflow-hidden block border transition-all hover:-translate-y-1" style="background: var(--color-surface); border-color: var(--color-border);">
          <div class="aspect-square bg-black/5 p-2">
            <img v-if="rel.images?.length" :src="productImageUrl(rel.images[0])" class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition-transform" />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl opacity-30">📦</div>
          </div>
          <div class="p-3">
            <h3 class="text-sm font-semibold truncate" style="color: var(--color-text);">{{ rel.name }}</h3>
            <p class="text-sm font-bold mt-1" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">Rs. {{ Number(rel.price).toLocaleString() }}</p>
          </div>
        </Link>
      </div>
    </section>

</template>
