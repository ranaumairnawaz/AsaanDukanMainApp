<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  payments: Object, // paginated
})

const rejectReason = ref('')
const rejectingId = ref(null)

function approve(id) {
  if (confirm('Approve this payment and activate the tenant\'s subscription?')) {
    router.post(route('admin.payments.approve', id))
  }
}

function openReject(id) {
  rejectingId.value = id
  rejectReason.value = ''
}

function confirmReject() {
  router.post(route('admin.payments.reject', rejectingId.value), {
    reason: rejectReason.value,
  }, {
    onSuccess: () => { rejectingId.value = null }
  })
}

const statusColors = {
  pending:  'text-amber-400 bg-amber-500/10 border-amber-500/20',
  approved: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
  rejected: 'text-red-400 bg-red-500/10 border-red-500/20',
}

const methodLabels = {
  bank_transfer: '🏦 Bank Transfer',
  jazzcash:      '📱 JazzCash',
  easypaisa:     '📱 Easypaisa',
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Payment Approval Queue</h1>
    </template>

    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h2 class="page-title">Payments</h2>
          <p class="page-subtitle">Review merchant subscription payments</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-400 shrink-0">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
          {{ payments.data.filter(p => p.status === 'pending').length }} pending
        </div>
      </div>

      <!-- Mobile Layout (< lg) -->
      <div class="space-y-3 lg:hidden">
        <div v-for="payment in payments.data" :key="'m-' + payment.id" class="card-padded space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="font-bold text-white">{{ payment.tenant?.name }}</div>
              <div class="text-xs text-gray-500">{{ payment.tenant?.store_slug }}</div>
            </div>
            <span class="badge uppercase tracking-wider font-bold" :class="statusColors[payment.status]">
              {{ payment.status }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
              <div class="text-gray-500 text-xs">Plan</div>
              <div class="text-white">{{ payment.plan?.name }}</div>
            </div>
            <div>
              <div class="text-gray-500 text-xs">Amount</div>
              <div class="text-white font-bold">Rs. {{ Number(payment.amount).toLocaleString() }}</div>
            </div>
            <div>
              <div class="text-gray-500 text-xs">Method</div>
              <div class="text-white">{{ methodLabels[payment.payment_method] ?? payment.payment_method }}</div>
            </div>
            <div>
              <div class="text-gray-500 text-xs">Date</div>
              <div class="text-white">{{ new Date(payment.created_at).toLocaleDateString() }}</div>
            </div>
          </div>
          
          <div v-if="payment.gateway_reference">
            <div class="text-gray-500 text-xs">Reference</div>
            <div class="font-mono text-xs text-gray-400 break-all">{{ payment.gateway_reference }}</div>
          </div>

          <!-- Order Snapshot (only shown when snapshot data exists) -->
          <div v-if="payment.orders_count_snapshot != null" class="bg-emerald-900/20 border border-emerald-800/40 rounded-lg p-2.5 space-y-1">
            <div class="text-emerald-400 text-[10px] font-bold uppercase tracking-wider mb-1">Order Count (at Submission)</div>
            <div class="flex justify-between text-xs">
              <span class="text-gray-400">Orders this cycle:</span>
              <span class="text-white font-mono">{{ payment.orders_count_snapshot }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-gray-400">Plan limit:</span>
              <span class="text-white font-mono">{{ payment.order_limit_snapshot ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between text-xs" v-if="payment.order_overage_count > 0">
              <span class="text-amber-400">Overage orders:</span>
              <span class="text-amber-400 font-mono font-bold">+{{ payment.order_overage_count }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-3 border-t border-gray-800 flex flex-wrap gap-2">
            <template v-if="payment.status === 'pending'">
              <a v-if="payment.proof_image_path" :href="`/storage/${payment.proof_image_path}`" target="_blank"
                class="btn-secondary btn-sm flex-1 text-center justify-center text-blue-400 border-blue-500/20 hover:bg-blue-500/10">
                View Proof
              </a>
              <button @click="approve(payment.id)" class="btn-secondary btn-sm flex-1 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10">
                ✓ Approve
              </button>
              <button @click="openReject(payment.id)" class="btn-secondary btn-sm flex-1 text-red-400 border-red-500/20 hover:bg-red-500/10">
                ✕ Reject
              </button>
            </template>
            <span v-else-if="payment.admin_notes" class="text-xs text-gray-500">{{ payment.admin_notes }}</span>
          </div>
        </div>
        <div v-if="payments.data.length === 0" class="card p-10 text-center text-gray-500">
          No payments found.
        </div>
      </div>

      <!-- Desktop Table (>= lg) -->
      <div class="card overflow-hidden hidden lg:block">
        <div v-if="payments.data.length === 0" class="p-12 text-center text-gray-500 text-sm">
          No payments found.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="data-table min-w-[800px]">
            <thead>
              <tr>
                <th>Tenant</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Orders</th>
                <th>Status</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments.data" :key="payment.id">
                <td>
                  <div class="font-bold text-white">{{ payment.tenant?.name }}</div>
                  <div class="text-xs text-gray-500">{{ payment.tenant?.store_slug }}</div>
                </td>
                <td class="text-gray-300">{{ payment.plan?.name }}</td>
                <td class="font-bold text-white whitespace-nowrap">Rs. {{ Number(payment.amount).toLocaleString() }}</td>
                <td class="text-gray-300 whitespace-nowrap">{{ methodLabels[payment.payment_method] ?? payment.payment_method }}</td>
                <td class="font-mono text-xs text-gray-400 max-w-[150px] truncate">{{ payment.gateway_reference }}</td>
                <td>
                  <!-- Order snapshot pill -->
                  <div v-if="payment.orders_count_snapshot != null" class="text-xs text-gray-400">
                    <span class="text-white font-mono">{{ payment.orders_count_snapshot }}</span> orders
                    <span v-if="payment.order_overage_count > 0" class="text-amber-400 ml-1">(+{{ payment.order_overage_count }} over)</span>
                  </div>
                  <span v-else class="text-gray-600 text-xs">—</span>
                </td>
                <td>
                  <span class="badge uppercase tracking-wider font-bold border-0" :class="statusColors[payment.status]">
                    {{ payment.status }}
                  </span>
                </td>
                <td class="text-gray-400 text-xs whitespace-nowrap">{{ new Date(payment.created_at).toLocaleDateString() }}</td>
                <td class="text-right">
                  <div v-if="payment.status === 'pending'" class="flex items-center justify-end gap-2">
                    <a v-if="payment.proof_image_path" :href="`/storage/${payment.proof_image_path}`" target="_blank"
                      class="btn-secondary btn-sm text-blue-400 border-blue-500/20 hover:bg-blue-500/10">
                      Proof
                    </a>
                    <button @click="approve(payment.id)" class="btn-secondary btn-sm text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10">
                      ✓ Appr
                    </button>
                    <button @click="openReject(payment.id)" class="btn-secondary btn-sm text-red-400 border-red-500/20 hover:bg-red-500/10">
                      ✕ Rej
                    </button>
                  </div>
                  <span v-else-if="payment.admin_notes" class="text-xs text-gray-500 truncate block max-w-[150px]" :title="payment.admin_notes">{{ payment.admin_notes }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reject Modal -->
      <div v-if="rejectingId !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="card p-6 w-full max-w-md space-y-4 shadow-2xl">
          <h3 class="font-bold text-white text-lg">Reject Payment</h3>
          <textarea v-model="rejectReason" rows="3" placeholder="Reason for rejection (optional)..."
            class="form-input resize-none"></textarea>
          <div class="flex gap-3 pt-2">
            <button @click="rejectingId = null" class="btn-secondary flex-1 py-2.5">Cancel</button>
            <button @click="confirmReject" class="btn-danger flex-1 py-2.5">Confirm Reject</button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>
