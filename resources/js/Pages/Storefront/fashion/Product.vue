<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
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
  }, { preserveScroll: true })
}
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" :title="product.name" :seoDescription="product.description?.replace(/(<([^>]+)>)/gi, '').substring(0,160) || 'Buy ' + product.name" :seoImage="product.images?.length ? productImageUrl(product.images[0]) : ''">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs mb-6 sm:mb-8" style="color: var(--color-muted)">
        <a href="./" class="hover:opacity-80 transition-opacity">Home</a>
        <span>/</span>
        <span class="truncate max-w-[200px]">{{ product.name }}</span>
      </div>

      <!-- Main product layout -->
      <div class="grid md:grid-cols-2 gap-8 md:gap-14">
        <!-- Images column -->
        <div class="space-y-3">
          <!-- Main image -->
          <div class="aspect-square rounded-[1.75rem] sm:rounded-[2.25rem] overflow-hidden"
            style="background: var(--color-surface); border: 1px solid var(--color-border);">
            <img v-if="product.images?.length"
              :src="productImageUrl(product.images[activeImage])"
              :alt="product.name"
              class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-6xl text-[var(--color-text)]">👗</div>
          </div>
          <!-- Thumbnails -->
          <div v-if="product.images?.length > 1" class="flex gap-2 overflow-x-auto pb-1">
            <button v-for="(img, i) in product.images" :key="i" @click="activeImage = i"
              class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0"
              :style="activeImage === i ? { borderColor: theme.accent } : { borderColor: 'var(--color-border)' }">
              <img :src="productImageUrl(img)" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Product Info -->
        <div class="space-y-5">
          <!-- Category badge -->
          <div v-if="product.category">
            <span class="text-xs font-semibold tracking-widest uppercase px-3 py-1 rounded-full"
              :style="{ color: theme.accent, background: theme.accent + '18' }">
              {{ product.category }}
            </span>
          </div>

          <h1 class="text-3xl sm:text-4xl font-bold leading-[1.05] tracking-tight" style="font-family: var(--font-heading); color: var(--color-text);">{{ product.name }}</h1>

          <!-- Price -->
          <div class="flex items-baseline flex-wrap gap-2 sm:gap-3">
            <span class="text-2xl sm:text-3xl font-extrabold" :style="{ color: theme.accent }">
              Rs. {{ Number(effectivePrice()).toLocaleString() }}
            </span>
            <span v-if="product.compare_at_price" class="text-lg line-through" style="color: var(--color-muted);">
              Rs. {{ Number(product.compare_at_price).toLocaleString() }}
            </span>
            <span v-if="product.compare_at_price"
              class="px-2 py-0.5 rounded-full text-xs font-bold text-black"
              :style="{ background: theme.accent }">
              {{ Math.round((1 - product.price / product.compare_at_price) * 100) }}% OFF
            </span>
          </div>

          <!-- Stock -->
          <div class="text-sm font-medium" :style="{ color: product.stock_quantity > 0 ? '#10b981' : '#ef4444' }">
            {{ product.stock_quantity > 0 ? `✓ In Stock (${product.stock_quantity} available)` : '✗ Out of Stock' }}
          </div>

          <!-- Description -->
          <div v-if="product.description" class="text-sm leading-relaxed" style="color: var(--color-muted);">
            {{ product.description }}
          </div>

          <!-- Variants -->
          <div v-if="product.variants?.length" class="space-y-2">
            <label class="block text-sm font-semibold" style="color: var(--color-text);">
              {{ product.variants[0].option_name }}
            </label>

            <!-- Color swatches -->
            <div v-if="product.variants[0].option_name?.toLowerCase() === 'color'" class="flex flex-wrap gap-2.5">
              <button v-for="variant in product.variants" :key="variant.id"
                @click="selectedVariant = variant"
                class="w-9 h-9 rounded-full border-2 transition-all flex items-center justify-center"
                :style="{ borderColor: selectedVariant?.id === variant.id ? theme.accent : 'var(--color-border)' }"
                :title="variant.option_value">
                <span class="w-6 h-6 rounded-full" :style="{ background: variant.option_value }"></span>
              </button>
            </div>

            <!-- Text/size options -->
            <div v-else class="flex flex-wrap gap-2">
              <button v-for="variant in product.variants" :key="variant.id"
                @click="selectedVariant = variant"
                class="px-3.5 py-2 rounded-xl text-sm border font-medium transition-all"
                :style="selectedVariant?.id === variant.id
                  ? { background: theme.accent, color: '#000', borderColor: theme.accent }
                  : { color: 'var(--color-text)', borderColor: 'var(--color-border)', background: 'transparent' }">
                {{ variant.option_value }}
                <span v-if="variant.price_override" class="ml-1 text-xs opacity-70">
                  Rs. {{ Number(variant.price_override).toLocaleString() }}
                </span>
              </button>
            </div>
          </div>

          <!-- Add to Cart CTA -->
          <div class="pt-2 flex items-center gap-3">
            <!-- Quantity -->
            <div class="flex items-center gap-3 border rounded-full px-1 py-1 shrink-0" style="border-color: var(--color-border);">
              <button @click="quantity > 1 && quantity--"
                class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/5 transition-colors font-semibold" style="color: var(--color-text)">−</button>
              <span class="w-4 text-center text-sm font-semibold" style="color: var(--color-text)">{{ quantity }}</span>
              <button @click="quantity++"
                class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-black/5 transition-colors font-semibold" style="color: var(--color-text)">+</button>
            </div>

            <button @click="addToCart"
              class="flex-1 flex items-center justify-center gap-2 py-3.5 rounded-full text-sm sm:text-base font-bold transition-all hover:scale-[1.01] active:scale-[0.99]"
              :style="{ background: theme.accent, color: '#000' }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H17M17 13v6M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
              </svg>
              Add to Cart
            </button>
          </div>
          <p class="text-xs text-center" style="color: var(--color-muted);">Orders confirmed via WhatsApp</p>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="related_products.length" class="mt-16 sm:mt-20">
        <h2 class="text-xl sm:text-2xl font-bold mb-5" style="font-family: var(--font-heading); color: var(--color-text);">
          You May Also Like
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
          <Link v-for="rel in related_products" :key="rel.id"
            :href="`${props.tenant.store_url}/product/${rel.id}`"
            class="block group">
            <div class="aspect-square overflow-hidden rounded-2xl sm:rounded-3xl" style="background: var(--color-surface);">
              <img v-if="rel.images?.length" :src="`/storage/${rel.images[0]}`" :alt="rel.name"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
              <div v-else class="flex items-center justify-center h-full text-3xl" style="background: var(--color-surface);">👗</div>
            </div>
            <div class="pt-3 px-0.5">
              <p class="text-sm font-medium truncate" style="color: var(--color-text);">{{ rel.name }}</p>
              <p class="text-sm font-bold mt-1" :style="{ color: theme.accent }">
                Rs. {{ Number(rel.price).toLocaleString() }}
              </p>
            </div>
          </Link>
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
