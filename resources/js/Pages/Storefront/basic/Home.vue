<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import CartSlideover from '@/Pages/Storefront/Shared/CartSlideover.vue'
import { productImageUrl } from '@/utils/productImage'

const props = defineProps({
  tenant: Object,
  products: Array,
  categories: Array,
  theme: Object,
})

const isCartOpen = ref(false)
const isSearchOpen = ref(false)
const isMenuOpen = ref(false)
const searchQuery = ref('')
const activeCategory = ref(null)

// Quick View State
const quickViewProduct = ref(null)
const quickViewVariant = ref(null)
const quickViewQuantity = ref(1)

function openQuickView(product) {
  quickViewProduct.value = product
  quickViewVariant.value = product.variants?.[0] ?? null
  quickViewQuantity.value = 1
}

function closeQuickView() {
  quickViewProduct.value = null
}

function effectivePrice() {
  if (!quickViewProduct.value) return 0;
  if (quickViewVariant.value?.price_override) return Number(quickViewVariant.value.price_override)
  return Number(quickViewProduct.value.price)
}

function addToCart() {
  if (!quickViewProduct.value) return;
  router.post(`${props.tenant.store_url}/cart`, {
    product_id: quickViewProduct.value.id,
    variant_id: quickViewVariant.value?.id ?? null,
    quantity: quickViewQuantity.value,
  }, { 
    preserveScroll: true,
    onSuccess: () => {
      closeQuickView();
      isCartOpen.value = true;
    }
  })
}

function applyTheme() {
  if (!props.theme?.css_vars) return
  const root = document.documentElement
  for (const [key, value] of Object.entries(props.theme.css_vars)) {
    root.style.setProperty(key, value)
  }
}

onMounted(() => {
  applyTheme()
  window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

function onKeydown(e) {
  if (e.key === 'Escape') {
    isCartOpen.value = false
    isMenuOpen.value = false
    isSearchOpen.value = false
    if (quickViewProduct.value) closeQuickView()
  }
}

const filtered = computed(() => {
  let list = props.products ?? []
  if (activeCategory.value) {
    list = list.filter(p => p.category === activeCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(p => p.name?.toLowerCase().includes(q))
  }
  return list
})

const initials = computed(() => (props.tenant?.name || 'S').trim().charAt(0).toUpperCase())
</script>

<template>
  <Head>
    <title>{{ tenant?.name ?? 'Store' }}</title>
    <link v-if="theme?.settings?.favicon_path" rel="icon" :href="`/storage/${theme.settings.favicon_path}`" />
  </Head>

  <div
    class="min-h-screen flex flex-col pb-20"
    style="background: var(--color-bg, #FBF6EC); color: var(--color-text, #141B2E); font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);"
  >
    <!-- Top bar: hamburger / search / cart -->
    <header class="sticky top-0 z-40 flex items-center justify-between px-4 py-3.5"
      style="background: var(--color-bg, #FBF6EC); border-bottom: 1px solid var(--color-border, #E7DFCF);">
      <button @click="isMenuOpen = !isMenuOpen" aria-label="Menu"
        class="p-2 -ml-2 rounded-lg active:scale-95 transition-transform" style="color: var(--color-text);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div class="flex items-center gap-1">
        <button @click="isSearchOpen = !isSearchOpen" aria-label="Search"
          class="p-2 rounded-lg active:scale-95 transition-transform" style="color: var(--color-text);">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <circle cx="11" cy="11" r="7" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
        </button>
        <button @click="isCartOpen = true" aria-label="Cart"
          class="p-2 rounded-lg active:scale-95 transition-transform" style="color: var(--color-text);">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </button>
      </div>
    </header>

    <!-- Slide-down menu -->
    <Transition
      enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
      <div v-if="isMenuOpen" class="px-4 py-3 flex flex-col gap-1" style="border-bottom: 1px solid var(--color-border);">
        <a :href="`/store/${tenant?.store_slug}/track`" class="text-sm font-medium py-2 px-3 rounded-lg"
          style="color: var(--color-muted);">Track My Order</a>
      </div>
    </Transition>

    <!-- Search bar -->
    <Transition
      enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
      <div v-if="isSearchOpen" class="px-4 py-3" style="border-bottom: 1px solid var(--color-border);">
        <input v-model="searchQuery" type="text" placeholder="Search products..." autofocus
          class="w-full rounded-xl px-4 py-2.5 text-sm outline-none"
          style="background: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text);" />
      </div>
    </Transition>

    <!-- Store identity block -->
    <section class="flex flex-col items-center text-center px-4 pt-8 pb-6">
      <div class="w-24 h-24 rounded-full overflow-hidden flex items-center justify-center shrink-0 shadow-sm"
        style="background: var(--color-surface); border: 1px solid var(--color-border);">
        <img v-if="theme?.logo_path"
          :src="`/storage/${theme?.logo_path}`"
          :alt="tenant?.name" class="w-full h-full object-cover" />
        <span v-else class="text-3xl font-bold" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">{{ initials }}</span>
      </div>

      <h1 class="mt-4 text-2xl font-bold" style="font-family: var(--font-heading); color: var(--color-text);">
        {{ tenant?.name }}
      </h1>

      <p v-if="tenant?.category" class="mt-1.5 inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1 rounded-full"
        :style="{ color: theme?.accent ?? 'var(--color-accent)', background: (theme?.accent ?? '#1F7A4D') + '15' }">
        {{ tenant.category }}
      </p>

      <p v-if="theme?.store_description" class="mt-3 max-w-sm text-sm" style="color: var(--color-muted);">
        {{ theme.store_description }}
      </p>
    </section>

    <!-- Category chips -->
    <div v-if="categories?.length" class="flex gap-2 overflow-x-auto px-4 pb-4 no-scrollbar">
      <button @click="activeCategory = null"
        class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :style="!activeCategory
          ? { background: theme?.accent ?? 'var(--color-accent)', color: 'var(--color-on-accent, #fff)' }
          : { background: 'var(--color-surface)', color: 'var(--color-text)', border: '1px solid var(--color-border)' }">
        All
      </button>
      <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
        class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
        :style="activeCategory === cat
          ? { background: theme?.accent ?? 'var(--color-accent)', color: 'var(--color-on-accent, #fff)' }
          : { background: 'var(--color-surface)', color: 'var(--color-text)', border: '1px solid var(--color-border)' }">
        {{ cat }}
      </button>
    </div>

    <!-- Product grid -->
    <main class="flex-1 px-4">
      <div v-if="filtered.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="product in filtered" :key="product.id"
          @click="openQuickView(product)" class="block group text-left w-full cursor-pointer relative">
          <div class="aspect-square rounded-2xl overflow-hidden"
            style="background: var(--color-surface); border: 1px solid var(--color-border);">
            <img v-if="product.images?.length" :src="productImageUrl(product.images[0])" :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
            <div v-else class="w-full h-full flex items-center justify-center text-3xl opacity-40">🛍️</div>
          </div>
          <p class="mt-2 text-sm font-medium truncate" style="color: var(--color-text);">{{ product.name }}</p>
          <p class="text-sm font-bold" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">
            Rs. {{ Number(product.price).toLocaleString() }}
          </p>
          <button @click.stop.prevent="openQuickView(product)" class="mt-3 w-full py-2 rounded-lg text-sm font-bold shadow-sm active:scale-95 transition-all" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
            Add to Cart
          </button>
        </div>
      </div>
      <div v-else class="text-center py-16 text-sm" style="color: var(--color-muted);">
        No products found.
      </div>
    </main>

    <!-- Footer -->
    <footer class="mt-10 px-4 py-6 text-center text-xs" style="color: var(--color-muted);">
      Orders placed via this site are confirmed on WhatsApp.<br />
      <span class="opacity-60">Powered by AssanDukan.online</span>
    </footer>

    <!-- Bottom tab bar -->
    <nav class="fixed bottom-0 inset-x-0 z-40 flex md:hidden items-center justify-around py-2.5"
      style="background: var(--color-surface); border-top: 1px solid var(--color-border);">
      <a href="./" class="flex flex-col items-center gap-0.5 px-6 py-1" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5L12 3l9 6.5V21a1 1 0 01-1 1h-5a1 1 0 01-1-1v-5H10v5a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" />
        </svg>
        <span class="text-[10px] font-semibold">Home</span>
      </a>
      <button @click="isSearchOpen = !isSearchOpen" class="flex flex-col items-center gap-0.5 px-6 py-1" style="color: var(--color-muted);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <circle cx="11" cy="11" r="7" />
          <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
        </svg>
        <span class="text-[10px] font-semibold">Search</span>
      </button>
      <button @click="isCartOpen = true" class="flex flex-col items-center gap-0.5 px-6 py-1" style="color: var(--color-muted);">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span class="text-[10px] font-semibold">Cart</span>
      </button>
    </nav>

    <CartSlideover :isOpen="isCartOpen" @close="isCartOpen = false" :tenant="tenant" :theme="theme" />

    <!-- Quick View Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="quickViewProduct" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-sm" @click.self="closeQuickView">
        <div class="w-full max-w-md rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl animate-in slide-in-from-bottom-8 sm:slide-in-from-bottom-0 sm:zoom-in-95 max-h-[90vh] flex flex-col"
          style="background: var(--color-bg); border: 1px solid var(--color-border);">
          <div class="p-4 flex items-center justify-between border-b" style="border-color: var(--color-border);">
            <h3 class="font-bold text-lg truncate pr-4" style="font-family: var(--font-heading); color: var(--color-text);">{{ quickViewProduct.name }}</h3>
            <button @click="closeQuickView" class="p-2 -mr-2 rounded-full hover:bg-black/5 shrink-0" style="color: var(--color-text);">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          
          <div class="overflow-y-auto p-5 space-y-5">
            <div class="aspect-square rounded-2xl overflow-hidden bg-black/5">
              <img v-if="quickViewProduct.images?.length" :src="productImageUrl(quickViewProduct.images[0])" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-4xl opacity-30">🛍️</div>
            </div>

            <div>
              <div class="flex items-baseline gap-2">
                <span class="text-xl font-extrabold" :style="{ color: theme?.accent ?? 'var(--color-accent)' }">Rs. {{ Number(effectivePrice()).toLocaleString() }}</span>
                <span v-if="quickViewProduct.compare_at_price" class="text-sm line-through" style="color: var(--color-muted);">
                  Rs. {{ Number(quickViewProduct.compare_at_price).toLocaleString() }}
                </span>
              </div>
              <p v-if="quickViewProduct.description" class="text-sm mt-2 leading-relaxed" style="color: var(--color-muted);">{{ quickViewProduct.description }}</p>
            </div>

            <div v-if="quickViewProduct.variants?.length" class="space-y-2">
              <label class="block text-sm font-medium" style="color: var(--color-text);">Select Option</label>
              <div class="flex flex-wrap gap-2">
                <button v-for="variant in quickViewProduct.variants" :key="variant.id" @click="quickViewVariant = variant"
                  class="px-3.5 py-2 rounded-xl text-sm border font-medium"
                  :style="quickViewVariant?.id === variant.id
                    ? { background: theme?.accent ?? 'var(--color-accent)', color: '#fff', borderColor: theme?.accent ?? 'var(--color-accent)' }
                    : { color: 'var(--color-text)', borderColor: 'var(--color-border)' }">
                  {{ variant.option_value }}
                </button>
              </div>
            </div>
          </div>

          <div class="p-4 border-t flex items-center gap-3 bg-black/5" style="border-color: var(--color-border);">
            <div class="flex items-center gap-3 border rounded-xl px-1 py-1 shrink-0 bg-white/50" style="border-color: var(--color-border);">
              <button @click="quickViewQuantity > 1 && quickViewQuantity--" class="w-10 h-10 rounded-lg flex items-center justify-center font-semibold" style="color: var(--color-text);">−</button>
              <span class="w-4 text-center text-sm font-semibold" style="color: var(--color-text);">{{ quickViewQuantity }}</span>
              <button @click="quickViewQuantity++" class="w-10 h-10 rounded-lg flex items-center justify-center font-semibold" style="color: var(--color-text);">+</button>
            </div>
            <button @click="addToCart" class="flex-1 py-3.5 rounded-xl text-sm font-bold shadow-lg active:scale-95 transition-all" :style="{ background: theme?.accent ?? 'var(--color-accent)', color: '#fff' }">
              Add to Cart
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
