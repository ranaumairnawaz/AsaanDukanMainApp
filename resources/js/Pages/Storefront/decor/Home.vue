<script setup>
/**
 * Home Decor theme — "Curated Room" direction.
 * Warm linen palette, Cormorant display serif, an asymmetric masonry
 * catalog (rather than a uniform grid) that reads like a curated
 * showroom, and a lifestyle hero with an overlapping caption card.
 */
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'
import { productImageUrl } from '@/utils/productImage'
import SectionRenderer from '@/Components/Storefront/Sections/SectionRenderer.vue'

const props = defineProps({
  tenant: Object, products: Array, categories: Array, theme: Object, sections: Array,
})

const activeCategory = ref(null)
const filtered = computed(() =>
  activeCategory.value ? props.products.filter(p => p.category === activeCategory.value) : props.products
)

// alternate tall/short cards for a masonry rhythm — a real showroom
// doesn't line every piece up to the same height.
function spanClass(i) {
  return i % 5 === 0 || i % 5 === 3 ? 'sm:row-span-2' : ''
}
function aspectClass(i) {
  return i % 5 === 0 || i % 5 === 3 ? 'aspect-[4/5] sm:aspect-[4/6]' : 'aspect-[4/3]'
}

const quickViewProduct = ref(null)

function addToCart(product) {
  if (product.variants?.length) {
    quickViewProduct.value = product
  } else {
    import('@inertiajs/vue3').then(({ router }) => {
      router.post(`${props.tenant.store_url}/cart`, {
        product_id: product.id,
        quantity: 1,
      }, { preserveScroll: true })
    })
  }
}

</script>

<template>
  <StorefrontLayout :tenant="tenant" :theme="theme">
    <div style="background: var(--color-bg, #faf7f0);">
    <!-- Decor Hero — Split layout inspired by reference: text left, image right, desc below -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14">
      <div class="grid md:grid-cols-2 gap-0 border rounded-2xl overflow-hidden" style="border-color: var(--color-border);">
        
        <!-- LEFT: Headline + CTA -->
        <div class="flex flex-col justify-center p-8 sm:p-12 lg:p-14" style="background: var(--color-bg);">
          <h1 class="text-4xl sm:text-5xl lg:text-6xl leading-[1.08] mb-6" 
              style="font-family: 'Cormorant Garamond', var(--font-heading), serif; color: var(--color-text); font-weight: 600;">
            {{ theme.banner_text || 'Furniture That Defines Your Space' }}
          </h1>
          <a href="#collection"
            class="inline-flex items-center gap-2 px-6 py-3 self-start rounded text-sm font-semibold border-2 transition-all hover:opacity-90"
            :style="{ borderColor: theme.accent, color: theme.accent }">
            Explore Our Collection
          </a>
        </div>

        <!-- RIGHT: Hero Image -->
        <div class="relative min-h-[340px] md:min-h-[480px] flex flex-col" style="background: #f2ece0;">
          <!-- Image area -->
          <div class="relative flex-1 overflow-hidden flex items-center justify-center p-6">
            <img
              v-if="theme.hero_image"
              :src="`/storage/${theme.hero_image}`"
              :alt="tenant.name"
              class="max-h-[380px] w-full object-contain drop-shadow-xl transition-transform duration-700 hover:scale-[1.02]"
            />
            <img
              v-else-if="products.length && products[0].images?.length"
              :src="productImageUrl(products[0].images[0])"
              :alt="products[0].name"
              class="max-h-[380px] w-full object-contain drop-shadow-xl transition-transform duration-700 hover:scale-[1.02]"
            />
            <div v-else class="text-8xl opacity-20">🛋️</div>

            <!-- Decorative dot indicators (static) -->
            <div class="absolute bottom-5 right-5 flex gap-1.5">
              <span class="w-2 h-2 rounded-full" :style="{ background: theme.accent }"></span>
              <span class="w-2 h-2 rounded-full bg-black/20"></span>
              <span class="w-2 h-2 rounded-full bg-black/20"></span>
            </div>
          </div>

          <!-- Description bar below image -->
          <div class="px-6 py-4 border-t" style="border-color: var(--color-border); background: #f2ece0;">
            <p class="text-sm leading-relaxed" style="color: var(--color-text); opacity: 0.75; font-family: var(--font-body);">
              {{ theme.store_description || 'We create furniture that brings style and functionality together, helping you build spaces you\'ll love to live in.' }}
            </p>
          </div>
        </div>
      </div>
    </section>


    <!-- Categories -->
    <section v-if="categories.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-8 mt-6 flex justify-center gap-6 sm:gap-8 border-b overflow-x-auto" style="border-color: var(--color-text);">
      <button @click="activeCategory = null"
        class="text-sm font-semibold tracking-wider uppercase transition-all pb-2 border-b-2 shrink-0"
        :class="!activeCategory ? 'border-current text-[var(--color-text)]' : 'border-transparent text-[var(--color-text)] hover:text-[var(--color-text)]'"
        :style="!activeCategory ? { borderColor: theme.accent } : {}">
        All
      </button>
      <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
        class="text-sm font-semibold tracking-wider uppercase transition-all pb-2 border-b-2 shrink-0"
        :class="activeCategory === cat ? 'text-[var(--color-text)]' : 'border-transparent text-[var(--color-text)] hover:text-[var(--color-text)]'"
        :style="activeCategory === cat ? { borderColor: theme.accent } : {}">
        {{ cat }}
      </button>
    </section>

    <!-- Masonry Collection Grid -->
    <section id="collection" class="max-w-7xl mx-auto px-4 sm:px-6 py-16 pb-32">
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-14 sm:auto-rows-[1fr]">
        <Link v-for="(product, i) in filtered" :key="product.id" :href="`${tenant.store_url}/product/${product.id}`"
          class="group block" :class="spanClass(i)">
          <div class="bg-[var(--color-surface)] overflow-hidden mb-5 relative rounded-sm" :class="aspectClass(i)">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" :alt="product.name" class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-700" />
            <div v-else class="flex items-center justify-center h-full text-5xl opacity-40">🛋️</div>
            <div v-if="product.compare_at_price" class="absolute top-4 left-4 px-3 py-1 text-[11px] font-semibold tracking-wide uppercase text-white" :style="{ background: theme.accent }">
              Sale
            </div>
          </div>
          <div class="space-y-1.5">
            <p class="text-[11px] tracking-[0.2em] uppercase" style="color: var(--color-text);">{{ product.category ?? 'Decor' }}</p>
            <h3 class="text-xl" style="font-family: var(--font-body); color: var(--color-text);">{{ product.name }}</h3>
            <div class="text-sm tracking-wide" style="color: var(--color-text);">
              <span>Rs. {{ Number(product.price).toLocaleString() }}</span>
              <span v-if="product.compare_at_price" class="line-through ml-2 opacity-60">Rs. {{ Number(product.compare_at_price).toLocaleString() }}</span>
            </div>
          </div>
        </Link>
      </div>
      <div v-if="filtered.length === 0" class="text-center py-24" style="color: var(--color-text); font-family: 'Cormorant Garamond', serif; font-size: 1.25rem;">
        No pieces currently available in this collection.
      </div>
    </section>
    <SectionRenderer :sections="sections" :tenant="tenant" />
    </div>
  </StorefrontLayout>
  <QuickViewModal :product="quickViewProduct" :tenant="tenant" :theme="theme" @close="quickViewProduct = null" />
</template>
