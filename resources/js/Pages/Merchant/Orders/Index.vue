<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
  orders: Object,
  filters: Object,
})

const statusFilter = ref(props.filters?.status ?? 'all')

watch(statusFilter, (newVal) => {
  router.get(route('merchant.orders.index'), { status: newVal }, {
    preserveState: true,
    preserveScroll: true,
  })
})

const statusColors = {
  pending:    'badge badge-yellow',
  confirmed:  'badge badge-blue',
  processing: 'badge badge-purple',
  shipped:    'badge bg-orange-500/15 text-orange-400',
  delivered:  'badge badge-green',
  cancelled:  'badge badge-red',
}

const filterOptions = [
  { value: 'all',        label: 'All Orders' },
  { value: 'pending',    label: 'Pending' },
  { value: 'confirmed',  label: 'Confirmed' },
  { value: 'processing', label: 'Processing' },
  { value: 'shipped',    label: 'Shipped' },
  { value: 'delivered',  label: 'Delivered' },
  { value: 'cancelled',  label: 'Cancelled' },
]
</script>

<template>
  <MerchantLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Orders</h1>
    </template>

    <div class="space-y-5">
      <!-- Header + filter -->
      <div class="page-header">
        <div>
          <h2 class="page-title">All Orders</h2>
          <p class="page-subtitle">Track and manage customer purchases</p>
        </div>
        <select v-model="statusFilter" class="form-input w-full sm:w-44 shrink-0">
          <option v-for="opt in filterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>

      <!-- Mobile Cards -->
      <div class="space-y-3 md:hidden">
        <Link
          v-for="order in orders.data"
          :key="order.id"
          :href="route('merchant.orders.show', order.id)"
          class="card-padded flex items-start justify-between gap-4 hover:border-gray-700 transition-colors"
        >
          <div class="min-w-0">
            <p class="font-mono font-semibold text-indigo-400 text-sm">{{ order.order_number }}</p>
            <p class="text-white font-medium text-sm mt-0.5 truncate">{{ order.customer?.name ?? '—' }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ order.customer?.phone }}</p>
            <p class="text-xs text-gray-600 mt-1">{{ new Date(order.created_at).toLocaleDateString() }}</p>
          </div>
          <div class="shrink-0 text-right">
            <p class="font-bold text-white text-sm">Rs. {{ Number(order.total).toLocaleString() }}</p>
            <span :class="statusColors[order.status]" class="mt-1.5 block text-center capitalize">{{ order.status }}</span>
          </div>
        </Link>
        <p v-if="orders.data.length === 0" class="card p-10 text-center text-gray-500 text-sm">No orders found.</p>
      </div>

      <!-- Desktop Table -->
      <div class="card overflow-hidden hidden md:block">
        <div v-if="orders.data.length === 0" class="p-12 text-center text-gray-500 text-sm">
          No orders found matching this filter.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders.data" :key="order.id">
                <td>
                  <Link :href="route('merchant.orders.show', order.id)" class="font-mono text-indigo-400 hover:text-indigo-300 font-semibold">
                    {{ order.order_number }}
                  </Link>
                  <div class="text-[10px] text-gray-600 mt-0.5 uppercase">{{ order.payment_method }}</div>
                </td>
                <td>
                  <div class="text-white font-medium">{{ order.customer?.name ?? '—' }}</div>
                  <div class="text-xs text-gray-500">{{ order.customer?.phone ?? '—' }}</div>
                </td>
                <td class="text-xs text-gray-400">{{ new Date(order.created_at).toLocaleString() }}</td>
                <td class="font-bold text-white">Rs. {{ Number(order.total).toLocaleString() }}</td>
                <td>
                  <span :class="statusColors[order.status]" class="capitalize">{{ order.status }}</span>
                </td>
                <td class="text-right">
                  <Link :href="route('merchant.orders.show', order.id)" class="btn-secondary btn-sm">
                    Details →
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="orders.links.length > 3" class="px-5 py-4 border-t border-gray-800 flex flex-wrap items-center justify-center gap-1">
          <template v-for="(link, i) in orders.links" :key="i">
            <Link v-if="link.url" :href="link.url" v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs transition-all"
              :class="link.active ? 'bg-indigo-500 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5'" />
            <span v-else v-html="link.label" class="px-2 text-xs text-gray-600"></span>
          </template>
        </div>
      </div>

      <!-- Mobile pagination -->
      <div v-if="orders.links.length > 3" class="flex flex-wrap items-center justify-center gap-1 md:hidden">
        <template v-for="(link, i) in orders.links" :key="i">
          <Link v-if="link.url" :href="link.url" v-html="link.label"
            class="px-3 py-1.5 rounded-lg text-xs border transition-all"
            :class="link.active ? 'bg-indigo-500 text-white border-indigo-500' : 'border-gray-700 text-gray-400'" />
        </template>
      </div>
    </div>
  </MerchantLayout>
</template>
