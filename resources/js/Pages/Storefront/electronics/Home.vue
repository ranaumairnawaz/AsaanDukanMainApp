<script setup>
/**
 * Electronics & Tech theme — dark navy tones, spec-focused cards, bold accents
 */
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import SectionRenderer from '@/Components/Storefront/Sections/SectionRenderer.vue'
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object,
  products: Array,
  categories: Array,
  theme: Object,
  sections: { type: Array, default: () => [] }
})

const activeCategory = ref(null)
const filtered = computed(() =>
  activeCategory.value ? props.products.filter(p => p.category === activeCategory.value) : props.products
)

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
  <div :style="{ background: theme.background || '#fdf8f6' }">
    <!-- Tech Hero -->
    <section class="border-b border-[var(--color-border)]" :style="{ backgroundColor: theme.background }">
      <div class="max-w-7xl mx-auto px-6 py-20 md:py-32 grid md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border"
            :style="{ color: theme.accent, backgroundColor: `color-mix(in srgb, ${theme.accent} 10%, transparent)`, borderColor: `color-mix(in srgb, ${theme.accent} 20%, transparent)` }">
            <span class="w-2 h-2 rounded-full animate-pulse" :style="{ backgroundColor: theme.accent }"></span>
            Tech Store
          </div>
          <h1 class="text-5xl md:text-6xl font-extrabold text-[var(--color-text)] tracking-tight leading-tight" :style="{ color: theme.accent, fontFamily: theme.heading }">
            {{ theme.banner_text }}
          </h1>
          <p class="text-lg text-[var(--color-text)] max-w-md" :style="{ color: theme.text }" >
            {{ theme.store_description }}
          </p>
          <div class="pt-4 flex gap-4">
            <a href="#products"
              class="px-8 py-3.5 rounded-lg text-sm font-bold text-white transition-all hover:opacity-90"
              :style="{ background: theme.accent }">
              View Catalog
            </a>
          </div>
        </div>
        <div class="relative rounded-2xl overflow-hidden aspect-video bg-[var(--color-surface)] border border-[var(--color-border)] shadow-2xl">
           <img v-if="theme.hero_image" :src="`/storage/${theme.hero_image}`" alt="Hero Dynamic Showcase" class="w-full h-full object-cover opacity-80" />
           <div v-else class="flex items-center justify-center h-full text-7xl">💻</div>
           <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section v-if="categories.length" class="max-w-7xl mx-auto px-6 py-8 border-b border-[var(--color-border)]">
      <div class="flex flex-wrap gap-2">
        <button @click="activeCategory = null"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all border"
          :class="!activeCategory ? '' : 'border'"
          :style="!activeCategory
            ? { backgroundColor: theme.accent, borderColor: theme.accent, color: 'var(--color-on-accent)' }
            : { background: 'var(--color-surface)', borderColor: 'var(--color-border)', color: 'var(--color-text)' }">
          All Devices
        </button>
        <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all border"
          :style="activeCategory === cat
            ? { backgroundColor: theme.accent, borderColor: theme.accent, color: 'var(--color-on-accent)' }
            : { background: 'var(--color-surface)', borderColor: 'var(--color-border)', color: 'var(--color-text)' }">
          {{ cat }}
        </button>
      </div>
    </section>

    <!-- Products Grid -->
    <section id="products" class="max-w-7xl mx-auto px-6 py-16 pb-24">
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Link v-for="product in filtered" :key="product.id" :href="`${tenant.store_url}/product/${product.id}`"
          class="group bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl overflow-hidden transition-all flex flex-col"
          :style="{ '--tw-border-opacity': '1' }"
          @mouseover="$event.currentTarget.style.borderColor = theme.accent"
          @mouseleave="$event.currentTarget.style.borderColor = 'var(--color-border)'">
          <div class="aspect-square bg-[var(--color-surface)] relative p-4">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
            <div v-else class="flex items-center justify-center h-full text-5xl">📱</div>
            <div v-if="product.compare_at_price" class="absolute top-3 left-3 px-2 py-1 text-[10px] font-bold uppercase rounded" :style="{ background: theme.accent, color: 'var(--color-on-accent)' }">Save Rs. {{ Number(product.compare_at_price) - Number(product.price) }}</div>
          </div>
          <div class="p-5 flex-1 flex flex-col">
            <div class="text-[10px] font-mono text-[var(--color-text)] mb-2 uppercase">{{ product.category ?? 'Device' }} • SKU: {{ product.sku || product.id }}</div>
            <h3 class="font-semibold text-[var(--color-text)] text-sm mb-3 flex-1">{{ product.name }}</h3>
            <div class="flex items-end justify-between mt-auto">
              <div>
                <div v-if="product.compare_at_price" class="text-xs line-through text-[var(--color-text)] mb-0.5">Rs. {{ Number(product.compare_at_price).toLocaleString() }}</div>
                <div class="font-bold text-lg" :style="{ color: theme.accent }">Rs. {{ Number(product.price).toLocaleString() }}</div>
              <div class="mt-4 flex gap-2"><button @click.stop.prevent="quickViewProduct = product" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg bg-gray-100" style="color: var(--color-text);">Quick View</button><button @click.stop.prevent="addToCart(product)" class="flex-1 py-2 text-xs font-bold uppercase rounded-lg" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">Add to Cart</button></div>
              </div>
              <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                :style="{ color: theme.accent, backgroundColor: `color-mix(in srgb, ${theme.accent} 10%, transparent)` }"
                @mouseover="$event.currentTarget.style.backgroundColor = theme.accent; $event.currentTarget.style.color = '#fff'"
                @mouseleave="$event.currentTarget.style.backgroundColor = `color-mix(in srgb, ${theme.accent} 10%, transparent)`; $event.currentTarget.style.color = theme.accent">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </div>
            </div>
          </div>
        </Link>
      </div>
      <div v-if="filtered.length === 0" class="text-center py-20 text-[var(--color-text)]">No devices match your criteria.</div>
    </section>

    <SectionRenderer :sections="sections" :tenant="tenant" />
    </div>
  </StorefrontLayout>
  <QuickViewModal :product="quickViewProduct" :tenant="tenant" :theme="theme" @close="quickViewProduct = null" />
</template>
