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
const added = ref(false)

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
    preserveScroll: true,
    onSuccess: () => {
      added.value = true
      setTimeout(() => (added.value = false), 1800)
    }
  })
}
</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme" :title="product.name" :seoDescription="product.description?.replace(/(<([^>]+)>)/gi, '').substring(0,160) || 'Buy ' + product.name" :seoImage="product.images?.length ? productImageUrl(product.images[0]) : ''">
    <div>
    <div class="max-w-6xl mx-auto px-6 py-12 sm:py-16">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs mb-10 uppercase tracking-widest" style="color: var(--color-text);">
        <a :href="props.tenant.store_url" class="hover:opacity-70 transition-opacity" style="color: var(--color-text);">Home</a>
        <span>—</span>
        <span>{{ product.category ?? 'Beauty' }}</span>
        <span>—</span>
        <span class="truncate">{{ product.name }}</span>
      </div>

      <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-start">
        <!-- Images -->
        <div class="space-y-4 md:sticky md:top-24">
          <div class="aspect-[3/4] rounded-[1.75rem] overflow-hidden" style="background: linear-gradient(160deg, #fbeae4, #f3d9d0);">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[activeImage])" :alt="product.name" class="w-full h-full object-cover" />
            <div v-else class="flex items-center justify-center h-full text-6xl opacity-40">💄</div>
          </div>
          <div v-if="product.images?.length > 1" class="flex gap-3 overflow-x-auto pb-2">
            <button v-for="(img, i) in product.images" :key="i" @click="activeImage = i"
              class="w-20 h-24 rounded-xl overflow-hidden border-2 transition-all shrink-0"
              :style="activeImage === i ? { borderColor: theme.accent } : { borderColor: 'transparent' }">
              <img :src="productImageUrl(img)" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Details -->
        <div class="space-y-8 pt-2">
          <div>
            <p class="text-[11px] font-semibold tracking-[0.25em] uppercase mb-3" style="color: var(--color-text);">{{ product.category ?? 'Beauty' }}</p>
            <h1 class="text-4xl leading-tight mb-4" style="font-family: var(--font-body); color: var(--color-text);">{{ product.name }}</h1>
            <div class="flex items-center gap-4">
              <span class="text-2xl font-semibold" :style="{ color: theme.accent }">Rs. {{ Number(effectivePrice()).toLocaleString() }}</span>
              <span v-if="product.compare_at_price" class="text-lg line-through" style="color: var(--color-text);">Rs. {{ Number(product.compare_at_price).toLocaleString() }}</span>
            </div>
          </div>

          <div class="text-[15px] leading-relaxed" style="color: var(--color-text);">
            {{ product.description }}
          </div>

          <div v-if="product.variants?.length" class="space-y-3">
            <label class="block text-xs font-semibold tracking-[0.2em] uppercase" style="color: var(--color-text);">{{ product.variants[0].option_name }}</label>
            <div class="flex flex-wrap gap-3">
              <button v-for="variant in product.variants" :key="variant.id" @click="selectedVariant = variant"
                class="px-5 py-2.5 rounded-full text-sm font-medium transition-all border"
                :style="selectedVariant?.id === variant.id ? { background: theme.accent, color: '#fff', borderColor: theme.accent } : { background: '#fff', color: '#2b1f1c', borderColor: '#e8dcd8' }">
                {{ variant.option_value }}
              </button>
            </div>
          </div>

          <div class="pt-6 border-t flex items-center gap-4" style="border-color: var(--color-text);">
            <!-- Quantity -->
            <div class="flex items-center border rounded-full overflow-hidden" style="border-color: var(--color-text);">
              <button @click="quantity > 1 && quantity--" class="px-5 py-3.5 hover:bg-black/5 transition-colors" style="color: var(--color-text);">−</button>
              <span class="w-6 text-center text-sm font-semibold" style="color: var(--color-text);">{{ quantity }}</span>
              <button @click="quantity++" class="px-5 py-3.5 hover:bg-black/5 transition-colors" style="color: var(--color-text);">+</button>
            </div>

            <button @click="addToCart"
              class="flex-1 flex items-center justify-center gap-2 py-4 rounded-full text-sm font-bold uppercase tracking-widest text-white transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg"
              :style="{ background: added ? '#22c55e' : theme.accent, boxShadow: `0 12px 24px -12px ${theme.accent}80` }">
              <svg v-if="added" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              <span>{{ added ? 'Added to Cart' : 'Add to Cart' }}</span>
            </button>
          </div>
          <p class="text-xs text-center flex items-center justify-center gap-1.5" style="color: var(--color-text);">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Free shipping on all orders · Confirmed via WhatsApp
          </p>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="related_products?.length" class="mt-24">
        <h2 class="text-2xl mb-8 text-center" style="font-family: 'Fraunces', var(--font-heading), serif; color: var(--color-text);">You May Also Love</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
          <Link v-for="rel in related_products" :key="rel.id" :href="`${tenant.store_url}/product/${rel.id}`" class="group block">
            <div class="aspect-square rounded-2xl overflow-hidden mb-3" style="background: linear-gradient(160deg, #fbeae4, #f3d9d0);">
              <img v-if="rel.images?.length" :src="`/storage/${rel.images[0]}`" :alt="rel.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div v-else class="flex items-center justify-center h-full text-3xl opacity-40">💄</div>
            </div>
            <p class="text-sm font-medium truncate" style="color: var(--color-text);">{{ rel.name }}</p>
            <p class="text-sm font-semibold mt-0.5" :style="{ color: theme.accent }">Rs. {{ Number(rel.price).toLocaleString() }}</p>
          </Link>
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
