<script setup>
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
  sections: Array,
})

const activeCategory = ref(null)

const filtered = computed(() =>
  activeCategory.value
    ? props.products.filter(p => p.category === activeCategory.value)
    : props.products
)

const bannerText = computed(() => props.theme?.settings?.custom_settings?.banner_text ?? props.tenant?.name ?? 'Welcome')
const storeDesc = computed(() => props.theme?.settings?.custom_settings?.store_description ?? 'Browse our exclusive collection and order on WhatsApp!')

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
    <!-- Hero Section -->
    <section class="relative overflow-hidden px-4 sm:px-6 pt-10 sm:pt-14 pb-10 sm:pb-14" :style="{ background: theme.background + '14' }">

      <!-- decorative wavy lines -->
      <svg class="absolute left-4 sm:left-10 top-20 sm:top-24 w-24 sm:w-32 opacity-60 hidden sm:block" viewBox="0 0 160 30" fill="none" stroke="var(--color-text)" stroke-width="1.5">
        <path d="M0 15 Q10 0 20 15 T40 15 T60 15 T80 15 T100 15 T120 15 T140 15 T160 15" />
      </svg>
      <svg class="absolute right-4 sm:right-10 bottom-16 sm:bottom-20 w-24 sm:w-32 opacity-60 hidden sm:block" viewBox="0 0 160 30" fill="none" stroke="var(--color-text)" stroke-width="1.5">
        <path d="M0 15 Q10 0 20 15 T40 15 T60 15 T80 15 T100 15 T120 15 T140 15 T160 15" />
      </svg>

      <!-- Headline -->
      <div class="relative max-w-3xl mx-auto text-center px-2">
        
        <h1 class="font-black leading-[1.05] tracking-tight text-4xl sm:text-5xl md:text-6xl inline-flex items-center gap-1"
          :style="{ color: theme.accent, fontFamily: 'var(--font-heading)' }">
          {{ bannerText }}
        </h1>

        <p class="mt-6 text-sm sm:text-base max-w-md mx-auto leading-relaxed" style="color: var(--color-muted);">
          {{ storeDesc }}
        </p>

        <a href="#products"
          class="inline-block mt-7 px-8 py-3.5 rounded-full text-sm font-bold text-white transition-transform hover:scale-105 active:scale-95"
          :style="{background: theme.accent}">
          Explore
        </a>
      </div>

      <!-- Single hero image -->
      <div class="relative mx-auto mt-10 sm:mt-14 w-[80%] aspect-[16/9] rounded-[1.75rem] sm:rounded-[2.25rem] overflow-hidden" style="background: var(--color-surface);">
        <img v-if="theme.hero_image" :src="`/storage/${theme.hero_image}`" :alt="tenant.name" class="w-full h-full object-cover" />
        <img v-else-if="products.length && products[0].images?.length" :src="`/storage/${products[0].images[0]}`" :alt="products[0].name" class="w-full h-full object-cover" />
        <div v-else class="flex items-center justify-center h-full text-6xl text-[var(--color-text)]">👗</div>
      </div>
    </section>

    <!-- Category Filter -->
    <section v-if="categories.length > 0" class="px-4 sm:px-6 mb-8 max-w-7xl mx-auto">
      <div class="flex flex-wrap gap-2">
        <button @click="activeCategory = null"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-sm font-medium border transition-all"
          :style="!activeCategory
            ? { background: theme.accent, color: 'var(--color-on-accent)', borderColor: theme.accent }
            : { color: 'var(--color-muted)', borderColor: 'var(--color-border)' }">
          All
          <span class="text-[11px] opacity-70">{{ products.length }}</span>
        </button>
        <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-sm font-medium border transition-all"
          :style="activeCategory === cat
            ? { background: theme.accent, color: 'var(--color-on-accent)', borderColor: theme.accent }
            : { color: 'var(--color-muted)', borderColor: 'var(--color-border)' }">
          {{ cat }}
          <span class="text-[11px] opacity-70">{{ products.filter(p => p.category === cat).length }}</span>
        </button>
      </div>
    </section>

    <!-- Products Grid -->
    <section id="products" class="px-4 sm:px-6 pb-20 max-w-7xl mx-auto">
      <div v-if="filtered.length === 0" class="text-center py-20" style="color: var(--color-muted);">
        <p class="text-lg">No products in this category yet.</p>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <div v-for="product in filtered" :key="product.id" @click="import('@inertiajs/vue3').then(({ router }) => router.visit(`${tenant.store_url}/product/${product.id}`))" class="group block cursor-pointer relative">
          <!-- Image -->
          <div class="aspect-square overflow-hidden relative rounded-2xl sm:rounded-3xl" style="background: var(--color-surface);">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
            <div v-else class="w-full h-full flex items-center justify-center text-4xl" style="background: var(--color-surface);">👗</div>

            <div v-if="product.compare_at_price"
              class="absolute top-2.5 left-2.5 px-2.5 py-1 rounded-full text-[10px] font-bold"
              :style="{ background: theme.accent, color: 'var(--color-on-accent)' }">
              Hot
            </div>

            <!-- Quick add -->
            <button
              class="absolute bottom-2.5 right-2.5 w-9 h-9 rounded-full flex items-center justify-center shadow-md opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 transition-all"
              :style="{ background: theme.accent }">
              <svg class="w-4 h-4" fill="none" :stroke="'var(--color-on-accent)'" stroke-width="2.3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
              </svg>
            </button>
          </div>

          <!-- Info -->
          <div class="pt-3 px-0.5">
            <h3 class="font-semibold text-sm leading-snug mb-1.5 line-clamp-2" style="color: var(--color-text);">
              {{ product.name }}
            </h3>

            <div v-if="product.variants?.some(v => v.option_name?.toLowerCase() === 'color')" class="flex items-center gap-1 mb-1.5">
              <span v-for="v in product.variants.filter(v => v.option_name?.toLowerCase() === 'color')" :key="v.id"
                class="w-3.5 h-3.5 rounded-full border" style="border-color: var(--color-border);"
                :style="{ background: v.option_value }" :title="v.option_value"></span>
            </div>

            <div class="flex items-center flex-wrap gap-1.5">
              <span class="font-bold text-sm" :style="{ color: theme.accent }">
                Rs. {{ Number(product.price).toLocaleString() }}
              </span>
              <span v-if="product.compare_at_price" class="text-xs line-through" style="color: var(--color-muted);">
                Rs. {{ Number(product.compare_at_price).toLocaleString() }}
              </span>
            </div>
            <div class="mt-1 text-xs" style="color: var(--color-muted);">
              {{ product.stock_quantity > 0 ? 'In Stock' : '⚠️ Out of Stock' }}
            </div>
          </div>
        </div>
      </div>
    </section>

  <SectionRenderer :sections="sections" :tenant="tenant" />
  </StorefrontLayout>
  <QuickViewModal :product="quickViewProduct" :tenant="tenant" :theme="theme" @close="quickViewProduct = null" />
</template>
