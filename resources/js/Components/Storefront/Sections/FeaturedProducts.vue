<script setup>
/**
 * section.config (optional): { layout: 'grid' | 'carousel', columns: 4 }
 * section.products: array of product objects (tenant-picked, via storefront_section_products)
 */
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { productImageUrl } from '@/utils/productImage'
import QuickViewModal from '@/Pages/Storefront/Shared/QuickViewModal.vue'

const props = defineProps({
  section: Object,
  tenant: Object,
})

const products = computed(() => props.section.products ?? [])

const quickViewProduct = ref(null)

function addToCart(product) {
  if (product.variants?.length) {
    quickViewProduct.value = product
  } else {
    router.post(`${props.tenant.store_url}/cart`, {
      product_id: product.id,
      quantity: 1,
    }, { preserveScroll: true })
  }
}

</script>

<template>
  <section
    v-if="products.length"
    class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20"
  >
    <div class="mb-8 sm:mb-10 flex items-end justify-between gap-4">
      <div>
        <h2
          class="text-2xl sm:text-3xl font-bold tracking-tight"
          style="font-family: var(--font-heading, inherit); color: var(--color-text);"
        >
          {{ section.title || 'Featured Products' }}
        </h2>
        <p v-if="section.subtitle" class="mt-1 text-sm" style="color: var(--color-muted);">
          {{ section.subtitle }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
      <Link
        v-for="product in products"
        :key="product.id"
        :href="`${tenant.store_url}/product/${product.id}`"
        class="group rounded-xl overflow-hidden flex flex-col transition-all hover:-translate-y-0.5"
        style="background: var(--color-surface); border: 1px solid var(--color-border);"
      >
        <div class="aspect-square relative p-3" style="background: var(--color-bg);">
          <img
            v-if="product.images?.length"
            :src="productImageUrl(product.images[0])"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform"
          />
          <div v-else class="flex items-center justify-center h-full text-4xl opacity-40">🛍️</div>
          <div
            v-if="product.compare_at_price"
            class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase"
            style="background: var(--color-accent); color: var(--color-on-accent);"
          >
            Sale
          </div>
        </div>
        <div class="p-4 flex-1 flex flex-col">
          <h3 class="text-sm font-semibold flex-1 line-clamp-2" style="color: var(--color-text);">
            {{ product.name }}
          </h3>
          <div class="mt-3 flex items-end gap-2 justify-between">
            <div>
              <span
                v-if="product.compare_at_price"
                class="text-xs line-through opacity-60 block"
                style="color: var(--color-muted);"
              >
                Rs. {{ Number(product.compare_at_price).toLocaleString() }}
              </span>
              <span class="font-bold text-base block" style="color: var(--color-accent);">
                Rs. {{ Number(product.price).toLocaleString() }}
              </span>
            </div>
            <button @click.prevent="addToCart(product)" class="p-2 rounded-full shadow-sm hover:scale-105 active:scale-95 transition-all" style="background: var(--color-accent); color: var(--color-on-accent, #fff);">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </button>
          </div>
        </div>
      </Link>
    </div>
    <QuickViewModal :product="quickViewProduct" :tenant="tenant" @close="quickViewProduct = null" />
  </section>
</template>
