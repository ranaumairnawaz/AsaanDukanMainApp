<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  products: Object,
  filters: Object,
  product_limit: Number,
  product_count: Number,
  can_add: Boolean,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

function applyFilters() {
  router.get(route('merchant.products.index'), { search: search.value, status: status.value }, {
    preserveState: true, replace: true
  })
}

function toggle(product) {
  useForm({}).post(route('merchant.products.toggle', product))
}

function destroy(product) {
  if (confirm(`Delete "${product.name}"? This cannot be undone.`)) {
    useForm({}).delete(route('merchant.products.destroy', product))
  }
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <div class="flex items-center justify-between w-full gap-3">
        <h1 class="text-base font-semibold text-white truncate">Products</h1>
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-xs text-gray-500 hidden sm:block">
            {{ product_count }} / {{ product_limit === 0 ? '∞' : product_limit }}
          </span>
          <Link v-if="can_add" :href="route('merchant.products.create')"
            class="btn-primary btn-sm">
            + Add
          </Link>
          <span v-else class="btn-secondary btn-sm opacity-50 cursor-not-allowed" title="Plan limit reached">
            + Add
          </span>
        </div>
      </div>
    </template>

    <!-- Limit Banner -->
    <div v-if="!can_add" class="mb-4 bg-orange-500/10 border border-orange-500/25 rounded-xl px-4 py-3 text-sm text-orange-400 flex items-center gap-2">
      ⚠️ Plan limit of {{ product_limit }} products reached.
      <Link href="#" class="underline font-semibold">Upgrade →</Link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
      <input v-model="search" @keydown.enter="applyFilters" placeholder="Search products..."
        class="form-input sm:w-64" />
      <select v-model="status" @change="applyFilters" class="form-input sm:w-40">
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="hidden">Hidden</option>
      </select>
      <button @click="applyFilters" class="btn-secondary">Search</button>
    </div>

    <!-- Empty State -->
    <div v-if="products.data.length === 0" class="card p-10 sm:p-14 text-center text-gray-500">
      <div class="text-5xl mb-4">📦</div>
      <p class="text-white font-semibold mb-1">No products yet</p>
      <p class="text-sm mb-5">Add your first product to start selling.</p>
      <Link :href="route('merchant.products.create')" class="btn-primary">
        + Add First Product
      </Link>
    </div>

    <!-- Products Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
      <div v-for="product in products.data" :key="product.id"
        class="bg-[#111] border border-white/5 rounded-2xl overflow-hidden hover:border-white/10 transition-all group flex flex-col">
        
        <!-- Image -->
        <div class="aspect-square bg-[#1a1a1a] relative overflow-hidden">
          <img v-if="product.images?.length"
            :src="`/storage/${product.images[0]}`"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
          <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-700">📷</div>
          <button @click="toggle(product)"
            class="absolute top-2 right-2 px-2 py-1 rounded-lg text-[10px] font-bold transition-all"
            :class="product.is_active ? 'bg-emerald-500/90 text-black' : 'bg-gray-800/90 text-gray-400'">
            {{ product.is_active ? 'Live' : 'Off' }}
          </button>
        </div>

        <!-- Info -->
        <div class="p-3 flex-1 flex flex-col">
          <h3 class="font-medium text-white text-sm truncate mb-1">{{ product.name }}</h3>
          <div class="flex items-center gap-1.5 mb-auto">
            <span class="text-emerald-400 font-bold text-sm">Rs. {{ Number(product.price).toLocaleString() }}</span>
            <span v-if="product.compare_at_price" class="text-gray-500 text-xs line-through">
              Rs. {{ Number(product.compare_at_price).toLocaleString() }}
            </span>
          </div>
          <div class="flex items-center justify-between text-[10px] text-gray-500 mt-2">
            <span>Qty: {{ product.stock_quantity }}</span>
            <span v-if="product.variants?.length">{{ product.variants.length }} variants</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-3 pb-3 flex gap-2">
          <Link :href="route('merchant.products.edit', product)"
            class="flex-1 text-center py-1.5 bg-white/5 hover:bg-white/10 text-gray-300 rounded-lg text-xs transition-all">
            ✏️ Edit
          </Link>
          <button @click="destroy(product)"
            class="px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs transition-all">
            🗑
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex flex-wrap gap-1.5 mt-5 justify-center sm:justify-end">
      <Link v-for="link in products.links" :key="link.label"
        :href="link.url ?? '#'" v-html="link.label"
        class="px-3 py-1.5 rounded-lg text-xs border transition-all"
        :class="link.active
          ? 'bg-emerald-600 text-white border-emerald-600'
          : 'border-white/10 text-gray-400 hover:border-white/20'" />
    </div>
  </MerchantLayout>
</template>
