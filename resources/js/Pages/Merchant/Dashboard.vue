<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  stats: Object,
  recent_orders: Array,
  top_products: Array,
  theme: Object,
  analytics_enabled: Boolean,
})

const statusColors = {
  pending: 'bg-yellow-500/15 text-yellow-400',
  confirmed: 'bg-blue-500/15 text-blue-400',
  processing: 'bg-purple-500/15 text-purple-400',
  shipped: 'bg-orange-500/15 text-orange-400',
  delivered: 'bg-emerald-500/15 text-emerald-400',
  cancelled: 'bg-red-500/15 text-red-400',
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <div class="truncate mr-4">
          <h1 class="text-base font-bold text-white truncate">Welcome back 👋</h1>
          <p class="text-xs text-gray-500 mt-0.5 truncate">{{ tenant?.name }}</p>
        </div>
        <div class="flex flex-col sm:flex-row items-end sm:items-center gap-1.5 sm:gap-2 shrink-0">
          <span class="badge font-bold uppercase tracking-wider text-[10px]"
            :class="{
              'bg-emerald-500/15 text-emerald-400': tenant?.status === 'active',
              'bg-yellow-500/15 text-yellow-400': tenant?.status === 'trial',
              'bg-red-500/15 text-red-400': tenant?.status === 'suspended',
            }">
            {{ tenant?.status }}
          </span>
          <span class="badge text-[10px] text-gray-400 bg-white/5 border border-white/10 uppercase tracking-wider font-bold">
            {{ tenant?.subscription_plan?.name ?? 'No Plan' }}
          </span>
        </div>
      </div>
    </template>

    <div class="space-y-4 sm:space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5 hover:border-emerald-500/20 transition-all relative overflow-hidden group">
          <div class="text-2xl mb-1 sm:mb-2">💰</div>
          <div v-if="analytics_enabled">
            <div class="text-lg sm:text-2xl font-bold text-white">Rs. {{ Number(stats.today_revenue).toLocaleString() }}</div>
            <div class="text-[10px] sm:text-xs text-gray-500 mt-1">Today's Revenue</div>
            <div class="text-[10px] sm:text-xs text-gray-600 mt-0.5 truncate">Total Rs. {{ Number(stats.total_revenue).toLocaleString() }}</div>
          </div>
          <div v-else class="absolute inset-0 backdrop-blur-sm bg-black/40 flex items-center justify-center flex-col p-4 text-center">
            <span class="text-lg">🔒</span>
            <span class="text-[10px] text-gray-400 font-bold mt-1">Growth Plan Required</span>
          </div>
        </div>
        <div class="card p-4 sm:p-5 hover:border-blue-500/20 transition-all relative overflow-hidden group">
          <div class="text-2xl mb-1 sm:mb-2">🛒</div>
          <div v-if="analytics_enabled">
            <div class="text-lg sm:text-2xl font-bold text-white">{{ stats.today_orders }}</div>
            <div class="text-[10px] sm:text-xs text-gray-500 mt-1">Today's Orders</div>
            <div class="text-[10px] sm:text-xs text-gray-600 mt-0.5 truncate">Total: {{ stats.total_orders }}</div>
          </div>
          <div v-else class="absolute inset-0 backdrop-blur-sm bg-black/40 flex items-center justify-center flex-col p-4 text-center">
            <span class="text-lg">🔒</span>
            <span class="text-[10px] text-gray-400 font-bold mt-1">Growth Plan Required</span>
          </div>
        </div>
        <div class="card p-4 sm:p-5 hover:border-yellow-500/20 transition-all">
          <div class="text-2xl mb-1 sm:mb-2">⏳</div>
          <div class="text-lg sm:text-2xl font-bold text-white">{{ stats.pending_orders }}</div>
          <div class="text-[10px] sm:text-xs text-gray-500 mt-1">Pending Orders</div>
          <div class="text-[10px] sm:text-xs text-gray-600 mt-0.5 truncate">Needs attention</div>
        </div>
        <div class="card p-4 sm:p-5 hover:border-purple-500/20 transition-all">
          <div class="text-2xl mb-1 sm:mb-2">📦</div>
          <div class="text-lg sm:text-2xl font-bold text-white">{{ stats.total_products }}</div>
          <div class="text-[10px] sm:text-xs text-gray-500 mt-1">Total Products</div>
          <div class="text-[10px] sm:text-xs text-gray-600 mt-0.5 truncate">{{ stats.active_products }} active</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <Link :href="route('merchant.products.create')"
          class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 sm:p-5 flex items-center gap-3 sm:gap-4 hover:bg-emerald-500/15 hover:border-emerald-500/40 transition-all group">
          <div class="w-10 h-10 shrink-0 bg-emerald-500/20 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">➕</div>
          <div class="min-w-0">
            <div class="font-bold text-white text-sm truncate">Add Product</div>
            <div class="text-xs text-gray-500 mt-0.5 truncate">Upload new item</div>
          </div>
        </Link>
        <Link :href="route('merchant.theme')"
          class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-4 sm:p-5 flex items-center gap-3 sm:gap-4 hover:bg-purple-500/15 hover:border-purple-500/40 transition-all group">
          <div class="w-10 h-10 shrink-0 bg-purple-500/20 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">🎨</div>
          <div class="min-w-0">
            <div class="font-bold text-white text-sm truncate">Customize Theme</div>
            <div class="text-xs text-gray-500 mt-0.5 truncate">Change colors</div>
          </div>
        </Link>
        <a :href="``" target="_blank"
          class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4 sm:p-5 flex items-center gap-3 sm:gap-4 hover:bg-blue-500/15 hover:border-blue-500/40 transition-all group">
          <div class="w-10 h-10 shrink-0 bg-blue-500/20 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">🔗</div>
          <div class="min-w-0">
            <div class="font-bold text-white text-sm truncate">View Storefront</div>
            <div class="text-xs text-gray-500 mt-0.5 truncate">See customer view</div>
          </div>
        </a>
      </div>

      <div class="grid lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Recent Orders -->
        <div class="card overflow-hidden flex flex-col">
          <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h2 class="font-bold text-white text-sm">Recent Orders</h2>
          </div>

          <div v-if="recent_orders.length === 0" class="p-8 text-center text-gray-500 text-sm">
            No orders yet.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="data-table min-w-[400px]">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in recent_orders" :key="order.id">
                  <td class="font-mono text-xs text-gray-300">
                    <div>{{ order.order_number }}</div>
                    <div class="text-[10px] text-gray-500 font-sans truncate max-w-[120px]">{{ order.customer?.name ?? '—' }}</div>
                  </td>
                  <td class="text-white font-bold whitespace-nowrap">Rs. {{ Number(order.total).toLocaleString() }}</td>
                  <td>
                    <span class="badge border-0 uppercase tracking-wider font-bold" :class="statusColors[order.status]">
                      {{ order.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top Products -->
        <div class="card overflow-hidden relative flex flex-col">
          <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h2 class="font-bold text-white text-sm">Top Products</h2>
          </div>

          <div v-if="!analytics_enabled" class="absolute inset-0 mt-14 z-10 backdrop-blur-md bg-black/50 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center text-xl mb-3 ring-1 ring-white/10 shadow-xl">🔒</div>
            <div class="font-bold text-white text-sm">Analytics Locked</div>
            <div class="text-xs text-gray-400 mt-1">Upgrade to Growth or Enterprise to view best-selling products.</div>
          </div>

          <div v-if="top_products.length === 0 && analytics_enabled" class="p-8 text-center text-gray-500 text-sm">
            No sales yet.
          </div>

          <div v-else class="divide-y divide-white/5 flex-1" :class="{ 'opacity-30 pointer-events-none': !analytics_enabled }">
            <div v-for="item in (analytics_enabled && top_products.length ? top_products : [1,2,3])" :key="item.product_id ?? item" class="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3 hover:bg-white/[0.02] transition-colors">
              <div class="w-10 h-10 rounded-lg bg-gray-900 overflow-hidden flex-shrink-0 border border-white/5">
                <img v-if="item.product?.images?.[0]" :src="`/storage/${item.product.images[0]}`" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-gray-500">No Img</div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-bold text-white truncate">{{ item.product?.name ?? 'Product Title Here' }}</div>
                <div class="text-xs text-emerald-400 font-bold">{{ item.total_sold ?? '0' }} sold</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MerchantLayout>
</template>
