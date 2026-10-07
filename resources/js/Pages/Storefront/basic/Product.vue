<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object,
  product: Object,
  related_products: Array,
  theme: Object,
})

const selectedVariant = ref(props.product.variants?.[0] ?? null)
const activeImage = ref(0)
const quantity = ref(1)

function applyTheme() {
  if (!props.theme?.css_vars) return
  const root = document.documentElement
  for (const [key, value] of Object.entries(props.theme.css_vars)) {
    root.style.setProperty(key, value)
  }
}
onMounted(applyTheme)

function effectivePrice() {
  if (selectedVariant.value?.price_override) return Number(selectedVariant.value.price_override)
  return Number(props.product.price)
}

function addToCart() {
  router.post(`${props.tenant.store_url}/cart`, {
    product_id: props.product.id,
    variant_id: selectedVariant.value?.id ?? null,
    quantity: quantity.value,
  }, { preserveScroll: true })
}
</script>

<template>
  <Head>
    <title>{{ product.name }} | {{ tenant?.name }}</title>
    <meta property="og:title" :content="`${product.name} | ${tenant?.name}`" />
    <meta property="og:description" :content="product.description?.replace(/(<([^>]+)>)/gi, '').substring(0,160) || 'Buy ' + product.name" />
    <meta property="og:image" :content="product.images?.length ? productImageUrl(product.images[0]) : ''" />
  </Head>

  <div class="min-h-screen" style="background: var(--color-bg, #FBF6EC); color: var(--color-text, #141B2E); font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);">
    <header class="sticky top-0 z-40 flex items-center gap-3 px-4 py-3.5" style="background: var(--color-bg); border-bottom: 1px solid var(--color-border);">
      <a href="./" class="p-2 -ml-2 rounded-lg" style="color: var(--color-text);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </a>
      <span class="font-semibold text-sm truncate">{{ tenant?.name }}</span>
    </header>

    <div class="max-w-md mx-auto px-4 py-6">
      <div class="aspect-square rounded-2xl overflow-hidden" style="background: var(--color-surface); border: 1px solid var(--color-border);">
        <img v-if="product.images?.length" :src="productImageUrl(product.images[activeImage])" :alt="product.name" class="w-full h-full object-cover" />
        <div v-else class="w-full h-full flex items-center justify-center text-5xl opacity-40">🛍️</div>
      </div>
      <div v-if="product.images?.length > 1" class="flex gap-2 overflow-x-auto pt-3">
        <button v-for="(img, i) in product.images" :key="i" @click="activeImage = i"
          class="w-14 h-14 rounded-xl overflow-hidden border-2 shrink-0"
          :style="activeImage === i ? { borderColor: theme.accent } : { borderColor: 'var(--color-border)' }">
          <img :src="productImageUrl(img)" class="w-full h-full object-cover" />
        </button>
      </div>

      <div class="pt-5 space-y-3">
        <span v-if="product.category" class="inline-block text-xs font-semibold px-3 py-1 rounded-full"
          :style="{ color: theme.accent, background: theme.accent + '15' }">
          {{ product.category }}
        </span>

        <h1 class="text-xl font-bold" style="font-family: var(--font-heading);">{{ product.name }}</h1>

        <div class="flex items-baseline gap-2">
          <span class="text-lg font-extrabold" :style="{ color: theme.accent }">Rs. {{ Number(effectivePrice()).toLocaleString() }}</span>
          <span v-if="product.compare_at_price" class="text-sm line-through" style="color: var(--color-muted);">
            Rs. {{ Number(product.compare_at_price).toLocaleString() }}
          </span>
        </div>

        <p v-if="product.description" class="text-sm leading-relaxed" style="color: var(--color-muted);">{{ product.description }}</p>

        <div v-if="product.variants?.length" class="space-y-2 pt-1">
          <div class="flex flex-wrap gap-2">
            <button v-for="variant in product.variants" :key="variant.id" @click="selectedVariant = variant"
              class="px-3.5 py-2 rounded-xl text-sm border font-medium"
              :style="selectedVariant?.id === variant.id
                ? { background: theme.accent, color: '#fff', borderColor: theme.accent }
                : { color: 'var(--color-text)', borderColor: 'var(--color-border)' }">
              {{ variant.option_value }}
            </button>
          </div>
        </div>

        <div class="pt-3 flex items-center gap-3">
          <div class="flex items-center gap-3 border rounded-full px-1 py-1 shrink-0" style="border-color: var(--color-border);">
            <button @click="quantity > 1 && quantity--" class="w-8 h-8 rounded-full flex items-center justify-center font-semibold">−</button>
            <span class="w-4 text-center text-sm font-semibold">{{ quantity }}</span>
            <button @click="quantity++" class="w-8 h-8 rounded-full flex items-center justify-center font-semibold">+</button>
          </div>
          <button @click="addToCart" class="flex-1 py-3.5 rounded-full text-sm font-bold" :style="{ background: theme.accent, color: '#fff' }">
            Add to Cart
          </button>
        </div>
        <p class="text-xs text-center" style="color: var(--color-muted);">Orders confirmed via WhatsApp</p>
      </div>

      <div v-if="related_products?.length" class="mt-10">
        <h2 class="text-base font-bold mb-4" style="font-family: var(--font-heading);">You May Also Like</h2>
        <div class="grid grid-cols-2 gap-4">
          <Link v-for="rel in related_products" :key="rel.id" :href="`${tenant.store_url}/product/${rel.id}`" class="block">
            <div class="aspect-square rounded-2xl overflow-hidden" style="background: var(--color-surface); border: 1px solid var(--color-border);">
              <img v-if="rel.images?.length" :src="productImageUrl(rel.images[0])" :alt="rel.name" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-2xl opacity-40">🛍️</div>
            </div>
            <p class="text-sm font-medium truncate pt-2">{{ rel.name }}</p>
            <p class="text-sm font-bold" :style="{ color: theme.accent }">Rs. {{ Number(rel.price).toLocaleString() }}</p>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
