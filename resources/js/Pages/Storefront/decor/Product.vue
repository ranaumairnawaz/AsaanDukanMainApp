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
    <div style="background: #faf7f0;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs mb-10 uppercase tracking-widest" style="color: var(--color-text);">
        <a :href="tenant.store_url" class="hover:text-[var(--color-text)] transition-colors">Collection</a>
        <span>/</span>
        <span>{{ product.category ?? 'Home Decor' }}</span>
      </div>

      <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        <!-- Image Gallery -->
        <div class="space-y-5">
          <div class="aspect-[4/5] bg-[var(--color-surface)] relative overflow-hidden">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[activeImage])" :alt="product.name" class="w-full h-full object-cover" />
            <div v-else class="flex items-center justify-center h-full text-7xl opacity-40">🪴</div>
          </div>
          <div v-if="product.images?.length > 1" class="flex gap-4 overflow-x-auto pb-2">
            <button v-for="(img, i) in product.images" :key="i" @click="activeImage = i"
              class="w-24 h-24 bg-[var(--color-surface)] transition-all shrink-0"
              :class="activeImage === i ? 'opacity-100 ring-2 ring-offset-2' : 'opacity-60 hover:opacity-100'"
              :style="activeImage === i ? { '--tw-ring-color': theme.accent } : {}">
              <img :src="productImageUrl(img)" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Details -->
        <div class="max-w-md mx-auto lg:mx-0 space-y-8">
          <div class="space-y-4 text-center lg:text-left">
            <p class="text-xs font-semibold tracking-[0.25em] uppercase" style="color: var(--color-text);">{{ product.category ?? 'Home Decor' }}</p>
            <h1 class="text-4xl md:text-5xl leading-tight" style="font-family: var(--font-body); color: var(--color-text); font-weight: 600;">{{ product.name }}</h1>
            <p class="text-2xl" style="color: var(--color-text);">Rs. {{ Number(effectivePrice()).toLocaleString() }}</p>
          </div>

          <div class="w-12 h-[1px] mx-auto lg:mx-0" :style="{ background: theme.accent }"></div>

          <div class="text-[15px] leading-loose" style="color: var(--color-text);">
            {{ product.description }}
          </div>

          <div v-if="product.variants?.length" class="space-y-4">
            <label class="block text-sm font-medium" style="color: var(--color-text);">{{ product.variants[0].option_name }}</label>
            <div class="flex flex-wrap gap-3">
              <button v-for="variant in product.variants" :key="variant.id" @click="selectedVariant = variant"
                class="px-6 py-3 text-sm transition-all border"
                :style="selectedVariant?.id === variant.id ? { background: '#292524', color: '#fff', borderColor: '#292524' } : { background: 'transparent', color: '#57534e', borderColor: '#d6d3d1' }">
                {{ variant.option_value }}
              </button>
            </div>
          </div>

          <div class="pt-8 flex items-center gap-4">
            <div class="flex items-center border" style="border-color: var(--color-text);">
              <button @click="quantity > 1 && quantity--" class="px-5 py-4 hover:bg-[var(--color-surface)] transition-colors" style="color: var(--color-text);">−</button>
              <span class="w-10 text-center text-[15px] font-medium" style="color: var(--color-text);">{{ quantity }}</span>
              <button @click="quantity++" class="px-5 py-4 hover:bg-[var(--color-surface)] transition-colors" style="color: var(--color-text);">+</button>
            </div>

            <button @click="addToCart"
              class="flex-1 py-5 text-center text-sm font-bold uppercase tracking-widest text-white transition-all hover:opacity-90"
              :style="{ background: theme.accent }">
              Add to Cart
            </button>
          </div>
          <p class="text-center text-xs mt-4" style="color: var(--color-text);">Delivery coordinated via WhatsApp</p>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="related_products?.length" class="mt-24">
        <h2 class="text-2xl mb-8 text-center" style="font-family: 'Cormorant Garamond', var(--font-heading), serif; color: var(--color-text); font-weight: 600;">Pairs Well With</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
          <Link v-for="rel in related_products" :key="rel.id" :href="`${tenant.store_url}/product/${rel.id}`" class="group block">
            <div class="aspect-[4/5] bg-[var(--color-surface)] overflow-hidden mb-3">
              <img v-if="rel.images?.length" :src="`/storage/${rel.images[0]}`" :alt="rel.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <div v-else class="flex items-center justify-center h-full text-3xl opacity-40">🪴</div>
            </div>
            <p class="text-sm truncate" style="color: var(--color-text);">{{ rel.name }}</p>
            <p class="text-sm mt-0.5" style="color: var(--color-text);">Rs. {{ Number(rel.price).toLocaleString() }}</p>
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
