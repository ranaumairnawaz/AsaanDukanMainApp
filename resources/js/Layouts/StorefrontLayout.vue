<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, watch, ref, computed } from 'vue'
import CartSlideover from '@/Pages/Storefront/Shared/CartSlideover.vue'

const props = defineProps({
  tenant: Object,
  theme: Object,
  title: { type: String, default: '' },
  seoImage: { type: String, default: '' },
  seoDescription: { type: String, default: '' },
})

const isCartOpen = ref(false)
const mobileMenuOpen = ref(false)
const isScrolled = ref(false)

function applyTheme() {
  const vars = props.theme?.css_vars || props.theme?.preview_css_vars
  if (!vars) return
  const root = document.documentElement
  for (const [key, value] of Object.entries(vars)) {
    root.style.setProperty(key, value)
  }
}

function onScroll() {
  isScrolled.value = window.scrollY > 8
}

function onKeydown(e) {
  if (e.key === 'Escape') {
    mobileMenuOpen.value = false
    isCartOpen.value = false
  }
}

function googleFontHref(family) {
  return `https://fonts.googleapis.com/css2?family=${family.replace(/ /g, '+')}:wght@300;400;500;600;700;800&display=swap`
}

onMounted(() => {
  applyTheme()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
})

watch(() => props.theme, applyTheme, { deep: true })


</script>

<template>
  <Head>
    <title>{{ theme?.seo_title || `${title ? title + ' | ' : ''}${tenant?.name ?? 'Store'}` }}</title>
    <meta name="description" :content="seoDescription || theme?.seo_description || theme?.store_description || `Welcome to ${tenant?.name}`" />
    <meta property="og:title" :content="theme?.seo_title || `${title ? title + ' | ' : ''}${tenant?.name ?? 'Store'}`" />
    <meta property="og:description" :content="seoDescription || theme?.seo_description || theme?.store_description || `Welcome to ${tenant?.name}`" />
    <meta v-if="seoImage || theme?.seo_image_url" property="og:image" :content="seoImage || theme?.seo_image_url" />
    
    <link v-if="theme?.settings?.favicon_path" rel="icon" :href="`/storage/${theme.settings.favicon_path}`" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    
    <!-- Heading Font: Use heading_font if set, otherwise fallback to typography -->
    <link
      v-if="theme?.heading_font"
      rel="stylesheet"
      :href="googleFontHref(theme.heading_font)"
    />
    <link
      v-else-if="theme?.typography"
      rel="stylesheet"
      :href="googleFontHref(theme.typography)"
    />

    <!-- Body Font -->
    <link
      v-if="theme?.typography"
      rel="stylesheet"
      :href="googleFontHref(theme.typography)"
    />
    
    <!-- Hard fallbacks if neither font option is configured by the merchant -->
    <template v-if="!theme?.heading_font && !theme?.typography">
      <link
        v-if="theme?.slug === 'cosmetics'"
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap"
      />
      <link
        v-else-if="theme?.slug === 'decor'"
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Nunito:wght@300;400;600&display=swap"
      />
      <link
        v-else
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      />
    </template>
  </Head>

  <div
    class="storefront-root min-h-screen flex flex-col"
    :style="{ backgroundColor: theme.background, color: theme.text_color, fontFamily: theme.typography }"
  >
    <!-- Storefront Nav -->
    <header
      class="sticky top-0 z-50 transition-all duration-300"
      :class="isScrolled ? 'py-2.5 shadow-sm' : 'py-4'"
      :style="{
        background: isScrolled ? 'rgba(255,255,255,0.95)' : '#ffffff',
        borderColor: '#e5e7eb',
        backdropFilter: isScrolled ? 'blur(16px)' : 'none',
      }"
      style="border-bottom: 1px solid"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between">
        
        <div class="flex items-center gap-4">
          <!-- Store name/logo -->
          <a
            :href="`/store/${tenant?.store_slug}/`"
            class="flex items-center transition-opacity hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 rounded-sm"
            style="--tw-ring-color: var(--color-accent, #10b981); --tw-ring-offset-color: var(--color-surface, #111);"
          >
            <img v-if="theme?.logo_path" :src="`/storage/${theme.logo_path}`" :alt="tenant?.name" class="h-8 sm:h-10 object-contain max-w-[200px]" />
            <span v-else class="text-lg sm:text-xl font-bold truncate max-w-[160px] sm:max-w-none tracking-tight"
              style="font-family: var(--font-heading, 'Inter', sans-serif); color: #111827;">
              {{ tenant?.name }}
            </span>
          </a>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
          <nav class="hidden md:flex items-center gap-6">
            <a :href="`/store/${tenant?.store_slug}/`" class="text-sm font-medium transition-opacity hover:opacity-70 focus-visible:outline-none" style="color: #374151;">Home</a>
            <a :href="`/store/${tenant?.store_slug}/#products`" class="text-sm font-medium transition-opacity hover:opacity-70 focus-visible:outline-none" style="color: #374151;">Products</a>
            <a :href="`/store/${tenant?.store_slug}/track`" class="text-sm font-medium transition-opacity hover:opacity-70 focus-visible:outline-none" style="color: #374151;">Track Order</a>
          </nav>
          
          <button
            @click="isCartOpen = true"
            aria-label="Open cart"
            class="p-2.5 sm:px-4 sm:py-2.5 rounded-xl font-semibold transition-all duration-200 hover:opacity-90 hover:scale-[1.03] active:scale-[0.98] inline-flex items-center gap-2"
            style="background: var(--color-accent, #10b981); color: var(--color-on-accent, #0a0a0a);"
          >
            <svg class="w-5 h-5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="hidden sm:inline text-sm">Cart</span>
          </button>
          
          <!-- Mobile Menu Toggle -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2.5 rounded-xl transition-colors duration-200 active:scale-[0.94]"
            style="color: #374151; background: #f3f4f6;"
          >
            <svg v-if="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 -translate-y-2 scale-95"
      >
        <div
          v-if="mobileMenuOpen"
          class="absolute top-full left-0 w-full p-4 md:hidden"
        >
          <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-2xl flex flex-col gap-2 max-w-sm mx-auto ml-auto" style="color: #111827;">
            <a :href="`/store/${tenant?.store_slug}/`" class="px-4 py-3 rounded-xl text-sm font-medium transition-colors active:scale-[0.98] hover:bg-gray-100" style="color: #374151;" @click="mobileMenuOpen = false">Home</a>
            <a :href="`/store/${tenant?.store_slug}/#products`" class="px-4 py-3 rounded-xl text-sm font-medium transition-colors active:scale-[0.98] hover:bg-gray-100" style="color: #374151;" @click="mobileMenuOpen = false">Products</a>
            <a :href="`/store/${tenant?.store_slug}/track`" class="px-4 py-3 rounded-xl text-sm font-medium transition-colors active:scale-[0.98] hover:bg-gray-100" style="color: #374151;" @click="mobileMenuOpen = false">Track Order</a>
          </div>
        </div>
      </Transition>
    </header>

    <!-- Backdrop for mobile menu -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileMenuOpen"
        class="fixed inset-0 top-[calc(var(--header-h,64px))] z-40 bg-black/40 backdrop-blur-sm"
        @click="mobileMenuOpen = false"
      />
    </Transition>

    <!-- Page slot & Footer wrapper -->
    <div class="flex-1 flex flex-col min-w-0">
      <main class="flex-1">
        <slot />
      </main>
      
      <footer
        class="py-12 mt-20 px-4 sm:px-6 text-center text-sm"
        style="background: #111827; border-top: 1px solid #1f2937; color: #9ca3af;"
      >
        <div class="max-w-7xl mx-auto space-y-2">
          <p class="font-semibold text-base" style="color: #f9fafb;">{{ tenant?.name }}</p>
          <p class="text-xs" style="color: #9ca3af;">Orders placed via this site are confirmed on WhatsApp.</p>
          <p class="text-xs" style="color: #4b5563;">Powered by Asaan Dukan</p>
        </div>
      </footer>
    </div>

    <!-- Cart Slideover -->
    <CartSlideover
      :isOpen="isCartOpen"
      @close="isCartOpen = false"
      :tenant="tenant"
      :theme="theme"
    />
  </div>
</template>
