<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'

defineProps({
  payments: Object,
})

const statusColors = {
  pending:  'badge badge-yellow',
  approved: 'badge badge-green',
  rejected: 'badge badge-red',
}

const typeLabels = {
  theme_change:      '🎨 Theme Change',
  store_info_change: '✏️ Name / Slug Change',
}

function approve(payment) {
  if (confirm(`Approve unlock for "${payment.tenant?.name}"? They will be able to use this feature once.`)) {
    useForm({}).post(route('admin.unlock_payments.approve', payment))
  }
}

function reject(payment) {
  const note = prompt('Optional: Enter a reason for rejection (shown to merchant):')
  if (note === null) return // cancelled
  useForm({ admin_note: note }).post(route('admin.unlock_payments.reject', payment))
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">🔓 Unlock Payment Requests</h1>
    </template>

    <div class="page-header">
      <div>
        <h2 class="page-title">Feature Unlock Requests</h2>
        <p class="page-subtitle">Merchants paying to unlock features outside their plan.</p>
      </div>
    </div>

    <!-- Desktop Table -->
    <div class="card overflow-hidden hidden md:block">
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Merchant</th>
              <th>Feature</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Payment Proof</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments.data" :key="payment.id">
              <td>
                <div class="font-medium text-white">{{ payment.tenant?.name ?? '—' }}</div>
                <div class="text-gray-500 text-xs">/store/{{ payment.tenant?.store_slug }}</div>
              </td>
              <td>
                <span class="text-xs font-medium">{{ typeLabels[payment.unlock_type] ?? payment.unlock_type }}</span>
              </td>
              <td class="font-mono text-sm">Rs. {{ Number(payment.amount).toLocaleString() }}</td>
              <td><span :class="statusColors[payment.status]">{{ payment.status }}</span></td>
              <td>
                <a
                  v-if="payment.payment_proof_path"
                  :href="`/storage/${payment.payment_proof_path}`"
                  target="_blank"
                  class="text-indigo-400 hover:text-indigo-300 text-xs underline"
                >
                  View Proof →
                </a>
                <span v-else class="text-gray-600 text-xs">—</span>
              </td>
              <td class="text-xs text-gray-400">
                {{ new Date(payment.created_at).toLocaleDateString() }}
              </td>
              <td>
                <div v-if="payment.status === 'pending'" class="flex gap-2">
                  <button @click="approve(payment)" class="btn-primary btn-sm text-xs">✅ Approve</button>
                  <button @click="reject(payment)" class="btn-danger btn-sm text-xs">❌ Reject</button>
                </div>
                <div v-else>
                  <span class="text-gray-500 text-xs italic">{{ payment.admin_note || 'No note' }}</span>
                </div>
              </td>
            </tr>
            <tr v-if="payments.data.length === 0">
              <td colspan="7" class="text-center py-12 text-gray-500">No unlock requests yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="space-y-3 md:hidden">
      <div v-for="payment in payments.data" :key="payment.id" class="card-padded space-y-3">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="font-semibold text-white text-sm">{{ payment.tenant?.name }}</p>
            <p class="text-xs text-gray-500">{{ typeLabels[payment.unlock_type] ?? payment.unlock_type }}</p>
          </div>
          <span :class="statusColors[payment.status]">{{ payment.status }}</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="font-mono text-sm text-gray-300">Rs. {{ Number(payment.amount).toLocaleString() }}</span>
          <a v-if="payment.payment_proof_path" :href="`/storage/${payment.payment_proof_path}`" target="_blank" class="text-indigo-400 text-xs underline">View Proof</a>
        </div>
        <div v-if="payment.status === 'pending'" class="flex gap-2 pt-1">
          <button @click="approve(payment)" class="btn-primary btn-sm flex-1 text-xs">✅ Approve</button>
          <button @click="reject(payment)" class="btn-danger btn-sm flex-1 text-xs">❌ Reject</button>
        </div>
      </div>
      <p v-if="payments.data.length === 0" class="card p-10 text-center text-gray-500">No unlock requests yet.</p>
    </div>

    <!-- Pagination -->
    <div class="flex flex-wrap gap-1.5 mt-4 justify-center sm:justify-end">
      <a
        v-for="link in payments.links"
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
