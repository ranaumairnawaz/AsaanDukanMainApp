<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  tenants: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')

function applyFilters() {
  router.get(route('admin.tenants.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true })
}

const statusColors = {
  active:    'badge badge-green',
  trial:     'badge badge-yellow',
  suspended: 'badge badge-red',
}

function impersonate(tenant) {
  if (confirm(`Log in as "${tenant.name}"? You will be taken to their merchant dashboard.`)) {
    useForm({}).post(route('admin.tenants.impersonate', tenant))
  }
}

function sendReminder(tenant) {
  const owner = tenant.users?.find(u => u.tenant_role === 'owner')
  if (!owner || !owner.phone) {
    alert('No owner phone number found for this tenant.')
    return
  }

  let daysLeft = 'Unknown'
  if (tenant.subscription_expires_at) {
    const expires = new Date(tenant.subscription_expires_at)
    const today = new Date()
    const diffTime = expires - today
    daysLeft = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  }

  const message = `Hello ${owner.name},\n\nThis is a friendly reminder from AssanDukan.online. Your subscription for the store "${tenant.name}" is expiring in ${daysLeft} days.\n\nPlease renew your plan to ensure uninterrupted service.\n\nThank you!`
  
  // Clean phone number
  let phone = owner.phone.replace(/[^0-9]/g, '')
  if (phone.startsWith('0')) {
    phone = '92' + phone.substring(1)
  }

  const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`
  window.open(url, '_blank')
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Tenants</h1>
    </template>

    <!-- Header + Filters -->
    <div class="page-header">
      <div>
        <h2 class="page-title">All Stores</h2>
        <p class="page-subtitle">{{ tenants.total ?? tenants.data?.length }} stores registered</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <input
        v-model="search"
        @keydown.enter="applyFilters"
        placeholder="Search by name or slug..."
        class="form-input sm:w-64"
      />
      <select
        v-model="status"
        @change="applyFilters"
        class="form-input sm:w-44"
      >
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="trial">Trial</option>
        <option value="suspended">Suspended</option>
      </select>
      <button @click="applyFilters" class="btn-primary">
        Search
      </button>
    </div>

    <!-- Mobile Cards (< md) -->
    <div class="space-y-3 md:hidden">
      <div
        v-for="tenant in tenants.data"
        :key="tenant.id"
        class="card-padded space-y-3"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="font-semibold text-white truncate">{{ tenant.name }}</p>
            <p class="text-xs text-gray-500">/store/{{ tenant.store_slug }}</p>
          </div>
          <span :class="statusColors[tenant.status]">{{ tenant.status }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-400">{{ tenant.subscription_plan?.name ?? 'No Plan' }}</span>
          <div class="flex items-center gap-3">
            <button @click="sendReminder(tenant)" class="text-emerald-400 hover:text-emerald-300 font-medium" title="Send WhatsApp Reminder">
              💬 Remind
            </button>
            <Link :href="route('admin.tenants.show', tenant)" class="text-indigo-400 hover:text-indigo-300 font-medium">
              View →
            </Link>
          </div>
        </div>
      </div>
      <p v-if="tenants.data.length === 0" class="text-center text-gray-500 py-10 card">No tenants found.</p>
    </div>

    <!-- Desktop Table (>= md) -->
    <div class="card overflow-hidden hidden md:block">
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Store</th>
              <th>Plan</th>
              <th>Status</th>
              <th>Expires</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tenant in tenants.data" :key="tenant.id">
              <td>
                <div class="font-medium text-white">{{ tenant.name }}</div>
                <div class="text-gray-500 text-xs">/store/{{ tenant.store_slug }}</div>
              </td>
              <td>{{ tenant.subscription_plan?.name ?? '—' }}</td>
              <td>
                <span :class="statusColors[tenant.status]">{{ tenant.status }}</span>
              </td>
              <td class="text-xs">
                {{ tenant.subscription_expires_at ? new Date(tenant.subscription_expires_at).toLocaleDateString() : '—' }}
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <Link :href="route('admin.tenants.show', tenant)" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium">
                    View →
                  </Link>
                  <button @click="impersonate(tenant)" class="text-xs px-2 py-1 bg-amber-900/40 text-amber-300 hover:bg-amber-800/60 rounded-lg border border-amber-700/50 transition-colors font-medium">
                    🔑 Login As
                  </button>
                  <button @click="sendReminder(tenant)" class="text-xs px-2 py-1 bg-emerald-900/40 text-emerald-300 hover:bg-emerald-800/60 rounded-lg border border-emerald-700/50 transition-colors font-medium" title="Send WhatsApp Reminder">
                    💬 Remind
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="tenants.data.length === 0">
              <td colspan="5" class="text-center py-10 text-gray-500">No tenants found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex flex-wrap gap-1.5 mt-4 justify-center sm:justify-end">
      <Link
        v-for="link in tenants.links"
        :key="link.label"
        :href="link.url ?? '#'"
        v-html="link.label"
        class="px-3 py-1.5 rounded-lg text-xs border transition-all"
        :class="link.active
          ? 'bg-indigo-600 text-white border-indigo-600'
          : 'border-gray-700 text-gray-400 hover:border-gray-500 hover:text-white'"
      />
    </div>
  </AdminLayout>
</template>
