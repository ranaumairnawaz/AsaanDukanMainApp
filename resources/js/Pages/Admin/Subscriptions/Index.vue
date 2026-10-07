<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  tenants: Object,
  plans: Array,
  stats: Object,
  filters: Object,
})

const page = usePage()
const flash = computed(() => page.props.flash ?? {})

const search = ref(props.filters?.search ?? '')
const planFilter = ref(props.filters?.plan ?? '')
const statusFilter = ref(props.filters?.status ?? '')

function applyFilters() {
  router.get(route('admin.subscriptions.index'), {
    search: search.value,
    plan: planFilter.value,
    status: statusFilter.value,
  }, { preserveState: true, replace: true })
}

// Extend modal
const extendModal = ref(null) // { tenant }
const extendForm = useForm({ days: 30, plan_id: '' })

function openExtend(tenant) {
  extendModal.value = tenant
  extendForm.days = 30
  extendForm.plan_id = tenant.subscription_plan_id ?? ''
}

function submitExtend() {
  extendForm.post(route('admin.subscriptions.extend', extendModal.value.id), {
    preserveScroll: true,
    onSuccess: () => { extendModal.value = null },
  })
}

function expiryBadge(dateStr) {
  if (!dateStr) return { label: 'No expiry', cls: 'badge-gray' }
  const d = new Date(dateStr)
  const now = new Date()
  const diff = (d - now) / 86400000
  if (diff < 0)   return { label: 'Expired', cls: 'badge-red' }
  if (diff < 7)   return { label: `${Math.ceil(diff)}d left`, cls: 'badge-yellow' }
  if (diff < 30)  return { label: `${Math.ceil(diff)}d left`, cls: 'badge-blue' }
  return { label: new Date(dateStr).toLocaleDateString('en-PK', { day:'numeric', month:'short', year:'numeric' }), cls: 'badge-green' }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Subscription Management</h1>
    </template>

    <div class="space-y-5">

      <!-- Flash -->
      <div v-if="flash.success" class="bg-emerald-500/10 border border-emerald-500/25 rounded-xl p-4 text-sm text-emerald-300 flex items-center gap-3">
        <span class="text-lg">✓</span> {{ flash.success }}
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="card p-4 text-center">
          <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
          <p class="text-xs text-gray-500 mt-1">Total Stores</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-2xl font-bold text-emerald-400">{{ stats.active }}</p>
          <p class="text-xs text-gray-500 mt-1">Active</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-2xl font-bold text-yellow-400">{{ stats.expiring_soon }}</p>
          <p class="text-xs text-gray-500 mt-1">Expiring Soon</p>
        </div>
        <div class="card p-4 text-center">
          <p class="text-2xl font-bold text-red-400">{{ stats.expired }}</p>
          <p class="text-xs text-gray-500 mt-1">Expired</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="card p-4 flex flex-wrap gap-3 items-center">
        <input v-model="search" @input="applyFilters" type="text" placeholder="Search store name / slug…" class="form-input flex-1 min-w-[180px]" />
        <select v-model="planFilter" @change="applyFilters" class="form-input w-auto">
          <option value="">All Plans</option>
          <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="statusFilter" @change="applyFilters" class="form-input w-auto">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="expiring_soon">Expiring Soon (7d)</option>
          <option value="expired">Expired</option>
        </select>
      </div>

      <!-- Table -->
      <div class="card overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead>
            <tr class="border-b border-white/5 text-[11px] text-gray-500 uppercase tracking-wider">
              <th class="px-4 py-3">Store</th>
              <th class="px-4 py-3">Plan</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Expires</th>
              <th class="px-4 py-3">Orders</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-white/[0.02] transition-colors">
              <td class="px-4 py-3">
                <div class="font-medium text-white">{{ tenant.name }}</div>
                <div class="text-xs text-gray-500">/store/{{ tenant.store_slug }}</div>
              </td>
              <td class="px-4 py-3">
                <span class="badge badge-blue">{{ tenant.subscription_plan?.name ?? '—' }}</span>
              </td>
              <td class="px-4 py-3">
                <span :class="['badge', tenant.status === 'active' ? 'badge-green' : 'badge-red']">
                  {{ tenant.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="['badge', expiryBadge(tenant.subscription_expires_at).cls]">
                  {{ expiryBadge(tenant.subscription_expires_at).label }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-400">{{ tenant.orders_count }}</td>
              <td class="px-4 py-3">
                <button @click="openExtend(tenant)"
                  class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                  style="background: var(--color-accent, #10b981)22; color: var(--color-accent, #10b981);">
                  Extend
                </button>
              </td>
            </tr>
            <tr v-if="!tenants.data.length">
              <td colspan="6" class="px-4 py-10 text-center text-gray-500">No subscriptions found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="tenants.last_page > 1" class="flex justify-center gap-2 flex-wrap">
        <Link v-for="link in tenants.links" :key="link.label"
          :href="link.url ?? '#'"
          :class="['px-3 py-1.5 rounded-lg text-sm transition-colors', link.active ? 'bg-emerald-500 text-black font-bold' : 'text-gray-400 hover:bg-white/5']"
          v-html="link.label"
        />
      </div>

    </div>

    <!-- Extend Modal -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
      <div v-if="extendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="extendModal = null"></div>
        <div class="relative card p-6 w-full max-w-md space-y-5 shadow-2xl">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-white font-bold text-base">Extend Subscription</h2>
              <p class="text-xs text-gray-500 mt-1">{{ extendModal.name }}</p>
            </div>
            <button @click="extendModal = null" class="text-gray-500 hover:text-white text-xl leading-none">×</button>
          </div>

          <form @submit.prevent="submitExtend" class="space-y-4">
            <div>
              <label class="form-label">Extend by (days)</label>
              <input v-model="extendForm.days" type="number" min="1" max="365" class="form-input" />
            </div>
            <div>
              <label class="form-label">Override Plan (optional)</label>
              <select v-model="extendForm.plan_id" class="form-input">
                <option value="">Keep current plan</option>
                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div class="flex gap-3 pt-1">
              <button type="button" @click="extendModal = null" class="flex-1 py-2.5 rounded-xl text-sm border border-white/10 text-gray-400 hover:bg-white/5 transition-colors">
                Cancel
              </button>
              <button type="submit" :disabled="extendForm.processing" class="btn-primary flex-1 py-2.5">
                {{ extendForm.processing ? 'Saving…' : 'Extend' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>
