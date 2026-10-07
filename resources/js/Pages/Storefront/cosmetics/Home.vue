<script setup>
/**
 * Cosmetics & Beauty theme — Modern Split Editorial direction.
 * Left: Sleek dark container with tech grid & organic copy curves.
 * Right: Accent surface hosting the brand imagery.
 */
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import SectionRenderer from '@/Components/Storefront/Sections/SectionRenderer.vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object, products: Array, categories: Array, theme: Object, sections: { type: Array, default: () => [] },
})

const activeCategory = ref(null)
const filtered = computed(() =>
  activeCategory.value ? props.products.filter(p => p.category === activeCategory.value) : props.products
)

const ritual = [
  { step: '01', label: 'Cleanse', desc: 'Remove the day, prep the skin' },
  { step: '02', label: 'Treat', desc: 'Targeted formulas that work while you rest' },
  { step: '03', label: 'Glow', desc: 'Seal it in, let it show' },
]

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
  <StorefrontLayout :tenant="tenant" :theme="theme" >
    <div :style="{ background: theme.background || '#fdf8f6' }">
    
    <!-- Hero — Split Screen Layout inspired by the design wireframe -->
    <section class="grid md:grid-cols-2 items-stretch min-h-[600px] bg-[var(--color-surface)] overflow-hidden rounded-b-[2.5rem]">
      
      <!-- Left Column: Content & Grid Lines -->
      <div class="relative p-8 sm:p-16 md:p-20 flex flex-col justify-center text-white space-y-8 z-10">
        <!-- Subtle decorative grid line overlay mimicking the design style -->
        <div class="absolute inset-0 opacity-5 pointer-events-none grid grid-cols-4 grid-rows-4">
          <div v-for="n in 16" :key="n" class="border-[0.5px] border-white"></div>
        </div>

        <div class="relative space-y-6 max-w-lg">
          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-medium tracking-tight leading-[1.1]" :style="{ fontFamily: theme.heading }">
            {{ theme.banner_text || 'We believe well-being is for everyone' }}
          </h1>
          <p class="text-sm sm:text-base text-[var(--color-text)] leading-relaxed">
            {{ theme.store_description || 'Your skin barrier may need some extra love. Look no further than formulas dedicated to supporting your skin.' }}
          </p>
        </div>

        <!-- Action Callbacks matching pill button geometry -->
        <div class="relative flex flex-wrap items-center gap-4 pt-4">
          <a href="#products"
            class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full text-sm font-semibold transition-all hover:opacity-90 bg-[var(--color-surface)] text-[var(--color-text)] shadow-lg"
            :style="{ backgroundColor: theme.accent || '#a78bfa', color: '#ffffff' }">
            Shop Now
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
          </a>
          <a href="#products" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-full text-sm font-medium text-[var(--color-text)] hover:text-white transition-colors">
            <span class="w-7 h-7 rounded-full bg-[var(--color-surface)]/10 flex items-center justify-center">
              <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </span>
            Explore All
          </a>
        </div>

        <!-- Floating Spinning badge placeholder icon -->
        <div class="absolute bottom-6 right-6 hidden lg:flex items-center justify-center w-16 h-16 rounded-full border border-white/20 bg-[var(--color-surface)]">
          <svg class="w-5 h-5 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7-7-7m14-6l-7 7-7-7"/>
          </svg>
        </div>
      </div>

      <!-- Right Column: Visual Stage with Accent solid fill -->
      <div class="relative min-h-[350px] md:min-h-full flex items-center justify-center p-8 overflow-hidden" :style="{ backgroundColor: theme.accent }">
        <!-- Ambient Background Glow Inside Picture Panel -->
        <div class="absolute -bottom-16 -left-16 w-64 h-64 rounded-full opacity-30 blur-2xl bg-[var(--color-surface)]"></div>
        
        <img v-if="theme.hero_image" :src="`/storage/${theme.hero_image}`" alt="Hero Dynamic Showcase" class="relative max-h-[85%] object-contain drop-shadow-2xl transition-transform duration-700 hover:scale-[1.02]" />
        
        <!-- Graphic placeholder layout mirroring design product blocks when custom hero image is omitted -->
        <div v-else class="relative flex items-end gap-4 h-full w-full max-w-md justify-center pt-12">
          <div class="w-24 h-52 bg-indigo-900/20 rounded-t-full border border-white/30 backdrop-blur-sm flex items-center justify-center text-2xl">🧴</div>
          <div class="w-28 h-64 bg-[var(--color-surface)]/80 rounded-xl shadow-xl flex items-center justify-center text-3xl transform translate-y-4">🧴</div>
          <div class="w-24 h-48 bg-indigo-950/40 rounded-t-lg border border-white/20 flex items-center justify-center text-2xl">🧪</div>
        </div>
      </div>
    </section>

    <!-- Ritual strip -->
    <section class="max-w-4xl mx-auto mt-16 px-6 grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-4">
      <div v-for="(r, i) in ritual" :key="r.step" class="relative text-center px-4">
        <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center text-sm font-semibold mb-3 relative z-10 bg-[var(--color-surface)] border border-[var(--color-border)]"
          style="color: var(--color-text); font-family: 'Fraunces', serif;">
          {{ r.step }}
        </div>
        <h3 class="text-sm font-semibold tracking-wide uppercase" style="color: var(--color-text);">{{ r.label }}</h3>
        <p class="text-xs mt-1 text-[var(--color-text)]">{{ r.desc }}</p>
      </div>
    </section>

    <!-- Category Tabs -->
    <section v-if="categories.length" class="max-w-6xl mx-auto px-6 pt-14 flex flex-wrap gap-3 justify-center">
      <button @click="activeCategory = null"
        class="px-5 py-2 rounded-full text-sm font-medium border transition-all"
        :style="!activeCategory ? { background: theme.accent, color: '#fff', borderColor: theme.accent } : { color: 'var(--color-muted)', borderColor: 'var(--color-border)' }">
        All Products
      </button>
      <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
        class="px-5 py-2 rounded-full text-sm font-medium border transition-all"
        :style="activeCategory === cat ? { background: theme.accent, color: '#fff', borderColor: theme.accent } : { color: 'var(--color-muted)', borderColor: 'var(--color-border)' }">
        {{ cat }}
      </button>
    </section>

    <!-- Product Grid -->
    <section id="products" class="max-w-6xl mx-auto px-6 py-12 pb-28">
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
        <div v-for="product in filtered" :key="product.id" @click="import('@inertiajs/vue3').then(({ router }) => router.visit(`${tenant.store_url}/product/${product.id}`))" class="group block cursor-pointer relative">
          <div class="rounded-[1.75rem] overflow-hidden mb-4 aspect-[3/4] relative"
            style="background: linear-gradient(160deg, #fbeae4, #f3d9d0);">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-[1.06] transition-transform duration-700 ease-out" />
            <div v-else class="flex items-center justify-center h-full text-5xl opacity-40">💄</div>
            <div v-if="product.compare_at_price"
              class="absolute top-4 right-4 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide text-white"
              :style="{ background: theme.accent }">
              SALE
            </div>
            <div class="absolute inset-x-4 bottom-4 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
              <div class="bg-[var(--color-surface)]/90 backdrop-blur-sm text-center py-2.5 rounded-full text-xs font-semibold tracking-wide uppercase" style="color: var(--color-text);">
                View Details
              </div>
            </div>
          </div>
          <div class="px-1">
            <p class="text-[11px] tracking-[0.2em] uppercase mb-1.5" style="color: var(--color-text);">{{ product.category ?? 'Beauty' }}</p>
            <h3 class="text-lg mb-2" style="font-family: 'Fraunces', var(--font-heading), serif; color: var(--color-text);">{{ product.name }}</h3>
            <div class="flex items-center gap-2">
              <span class="font-semibold" :style="{ color: theme.accent }">Rs. {{ Number(product.price).toLocaleString() }}</span>
              <span v-if="product.compare_at_price" class="text-xs line-through" style="color: var(--color-text);">
                Rs. {{ Number(product.compare_at_price).toLocaleString() }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="filtered.length === 0" class="text-center py-24" style="color: var(--color-text);">
        <p class="text-lg" style="font-family: 'Fraunces', serif;">No products found in this collection yet.</p>
      </div>
    </section>

    <!-- Merchant-configured sections (promo banners, testimonials, FAQ, etc.) -->
    <SectionRenderer :sections="sections" :tenant="tenant" />
    </div>
  </StorefrontLayout>
  <QuickViewModal :product="quickViewProduct" :tenant="tenant" :theme="theme" @close="quickViewProduct = null" />
</template>
