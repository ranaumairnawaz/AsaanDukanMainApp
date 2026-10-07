<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  requests: Object
})

const quoteForm = useForm({
  quoted_price: ''
})

const rejectForm = useForm({
  admin_note: ''
})

const activeRequestId = ref(null)
const rejectRequestId = ref(null)

function setQuote(id) {
  quoteForm.post(route('admin.domains.quote', id), {
    preserveScroll: true,
    onSuccess: () => { activeRequestId.value = null; quoteForm.reset() }
  })
}

function approve(id) {
  if (confirm('Are you sure you want to approve and link this domain?')) {
    router.post(route('admin.domains.approve', id), {}, { preserveScroll: true })
  }
}

function reject(id) {
  rejectForm.post(route('admin.domains.reject', id), {
    preserveScroll: true,
    onSuccess: () => { rejectRequestId.value = null; rejectForm.reset() }
  })
}
</script>

<template>
  <AdminLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold font-['Fraunces']">Domain Requests</h1>
      </div>

      <div class="bg-[#101526] border border-white/10 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-black/20 text-white/40 font-medium">
              <tr>
                <th class="px-6 py-4">Store</th>
                <th class="px-6 py-4">Type</th>
                <th class="px-6 py-4">Requested Domain</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="requests.data.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-white/40">No domain requests found.</td>
              </tr>
              <tr v-for="req in requests.data" :key="req.id" class="hover:bg-white/5 transition-colors">
                <td class="px-6 py-4">
                  <p class="font-bold text-white">{{ req.tenant.name }}</p>
                  <p class="text-xs text-white/40">Plan: {{ req.tenant.subscription_plan?.name }}</p>
                </td>
                <td class="px-6 py-4 text-white/60 capitalize">
                  {{ req.type.replace('_', ' ') }}
                </td>
                <td class="px-6 py-4 font-bold text-white">
                  {{ req.requested_value }}<span v-if="req.type === 'subdomain'" class="text-white/40">.assandukan.online</span>
                  <div v-if="req.merchant_owns_domain" class="mt-1">
                    <span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 rounded text-[10px] font-bold uppercase tracking-wider">Already Owns Domain</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span v-if="req.status === 'pending'" class="px-2.5 py-1 bg-yellow-500/10 text-yellow-400 rounded-lg text-xs font-medium">Pending</span>
                  <span v-else-if="req.status === 'quote_sent'" class="px-2.5 py-1 bg-blue-500/10 text-blue-400 rounded-lg text-xs font-medium">Quote Sent</span>
                  <span v-else-if="req.status === 'payment_submitted'" class="px-2.5 py-1 bg-indigo-500/10 text-indigo-400 rounded-lg text-xs font-medium">Payment Submitted</span>
                  <span v-else-if="req.status === 'active'" class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded-lg text-xs font-medium">Active</span>
                  <span v-else-if="req.status === 'rejected'" class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-xs font-medium">Rejected</span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  
                  <!-- Subdomain specific actions -->
                  <template v-if="req.type === 'subdomain' && req.status === 'pending'">
                    <button @click="approve(req.id)" class="px-3 py-1.5 bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 rounded-lg font-medium text-xs">Approve</button>
                    <button @click="rejectRequestId = req.id" class="px-3 py-1.5 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg font-medium text-xs">Reject</button>
                  </template>

                  <!-- Custom Domain specific actions -->
                  <template v-if="req.type === 'custom_domain'">
                    <button v-if="req.status === 'pending'" @click="activeRequestId = req.id" class="px-3 py-1.5 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded-lg font-medium text-xs">Set Quote</button>
                    <button v-if="req.status === 'payment_submitted'" @click="approve(req.id)" class="px-3 py-1.5 bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 rounded-lg font-medium text-xs">Approve & Link</button>
                    <a v-if="req.payment_proof_path" :href="'/storage/' + req.payment_proof_path" target="_blank" class="px-3 py-1.5 bg-white/10 text-white hover:bg-white/20 rounded-lg font-medium text-xs">View Proof</a>
                    <button v-if="['pending', 'payment_submitted'].includes(req.status)" @click="rejectRequestId = req.id" class="px-3 py-1.5 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg font-medium text-xs">Reject</button>
                  </template>

                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="requests.last_page > 1" class="px-6 py-4 border-t border-white/5 flex items-center gap-2">
          <Link v-for="link in requests.links" :key="link.label" :href="link.url"
            class="px-3 py-1 rounded-lg text-sm transition-colors"
            :class="link.active ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white'"
            v-html="link.label" />
        </div>
      </div>
    </div>

    <!-- Set Quote Modal -->
    <div v-if="activeRequestId" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
      <div class="bg-[#141B2E] p-6 rounded-2xl w-full max-w-md border border-white/10">
        <h3 class="text-lg font-bold text-white mb-4">Set Custom Domain Price</h3>
        <form @submit.prevent="setQuote(activeRequestId)" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Price (Rs.)</label>
            <input v-model="quoteForm.quoted_price" type="number" class="w-full bg-[#101526] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]" required />
            <p v-if="quoteForm.errors.quoted_price" class="text-red-400 text-xs mt-1">{{ quoteForm.errors.quoted_price }}</p>
          </div>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="activeRequestId = null" class="px-4 py-2 rounded-xl text-white/60 hover:text-white">Cancel</button>
            <button type="submit" :disabled="quoteForm.processing" class="px-4 py-2 bg-[#1F7A4D] hover:bg-[#2FA968] text-white rounded-xl font-medium">Send Quote</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="rejectRequestId" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80">
      <div class="bg-[#141B2E] p-6 rounded-2xl w-full max-w-md border border-white/10">
        <h3 class="text-lg font-bold text-white mb-4">Reject Request</h3>
        <form @submit.prevent="reject(rejectRequestId)" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Reason (Optional)</label>
            <textarea v-model="rejectForm.admin_note" rows="3" class="w-full bg-[#101526] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]"></textarea>
            <p v-if="rejectForm.errors.admin_note" class="text-red-400 text-xs mt-1">{{ rejectForm.errors.admin_note }}</p>
          </div>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="rejectRequestId = null" class="px-4 py-2 rounded-xl text-white/60 hover:text-white">Cancel</button>
            <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium">Reject</button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
