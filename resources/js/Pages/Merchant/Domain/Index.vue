<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  subdomain_allowed: Boolean,
  custom_domain_allowed: Boolean,
  current_subdomain: String,
  subdomain_status: String,
  current_custom_domain: String,
  subdomain_request: Object,
  custom_domain_request: Object,
  bank_accounts: Array,
})

const subdomainForm = useForm({
  requested_value: ''
})

const customDomainForm = useForm({
  requested_value: '',
  merchant_owns_domain: false,
})

const paymentForm = useForm({
  domain_request_id: props.custom_domain_request?.id,
  proof_image: null,
})

function submitSubdomain() {
  subdomainForm.post(route('merchant.domain.subdomain'), {
    preserveScroll: true,
    onSuccess: () => subdomainForm.reset()
  })
}

function submitCustomDomain() {
  customDomainForm.post(route('merchant.domain.custom'), {
    preserveScroll: true,
    onSuccess: () => customDomainForm.reset()
  })
}

function submitPayment() {
  paymentForm.post(route('merchant.domain.pay'), {
    forceFormData: true,
    preserveScroll: true,
  })
}

function onFileChange(e) {
  paymentForm.proof_image = e.target.files[0]
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <h1 class="font-['Fraunces'] text-base font-semibold" style="color: var(--text-primary);">Domain Settings</h1>
    </template>

    <div class="px-4 sm:px-6 py-6 max-w-4xl space-y-8">
      
      <!-- Subdomain Section -->
      <div class="p-6 rounded-2xl border space-y-6" style="background: var(--surface-panel); border-color: var(--border-subtle);">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-base font-semibold" style="color: var(--text-primary);">AssanDukan Subdomain</h2>
            <p class="text-xs mt-1" style="color: var(--text-muted);">Get a free branded subdomain on AssanDukan.</p>
          </div>
          <span v-if="!subdomain_allowed" class="text-xs px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg font-medium border border-red-500/20">
            🔒 Upgrade Required
          </span>
          <span v-else class="text-xs px-2.5 py-1 bg-[#1F7A4D]/10 text-[#2FA968] rounded-lg font-medium border border-[#1F7A4D]/20">
            ✅ Eligible
          </span>
        </div>

        <div v-if="current_subdomain && subdomain_status === 'active'" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
          <p class="text-emerald-400 text-sm font-medium">Your current subdomain:</p>
          <a :href="'https://' + current_subdomain + '.assandukan.online'" target="_blank" class="text-lg font-bold text-white hover:underline mt-1 block">
            {{ current_subdomain }}.assandukan.online
          </a>
        </div>

        <div v-else-if="subdomain_request && subdomain_request.status === 'pending'" class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
          <p class="text-yellow-400 text-sm font-medium">Request Pending</p>
          <p class="text-white text-sm mt-1">Admin is reviewing your request for <strong>{{ subdomain_request.requested_value }}.assandukan.online</strong></p>
        </div>

        <form v-else-if="subdomain_allowed" @submit.prevent="submitSubdomain" class="space-y-4">
          <div>
            <label class="block text-xs font-medium mb-1.5" style="color: var(--text-muted);">Request new subdomain</label>
            <div class="flex">
              <input v-model="subdomainForm.requested_value" type="text" placeholder="mystore" class="flex-1 min-w-0 px-4 py-2.5 text-sm rounded-l-xl border-y border-l text-white focus:outline-none focus:border-[#1F7A4D]" style="background: var(--surface-app); border-color: var(--border-subtle);" required />
              <div class="px-4 py-2.5 text-sm border-y border-r rounded-r-xl flex items-center" style="background: var(--surface-panel); border-color: var(--border-subtle); color: var(--text-muted);">
                .assandukan.online
              </div>
            </div>
            <p v-if="subdomainForm.errors.requested_value" class="text-red-400 text-xs mt-1.5">{{ subdomainForm.errors.requested_value }}</p>
          </div>
          <button type="submit" :disabled="subdomainForm.processing" class="px-5 py-2.5 rounded-xl font-medium text-sm text-white bg-[#1F7A4D] hover:bg-[#2FA968] transition-colors disabled:opacity-50">
            Submit Request
          </button>
        </form>
      </div>

      <!-- Custom Domain Section -->
      <div class="p-6 rounded-2xl border space-y-6" style="background: var(--surface-panel); border-color: var(--border-subtle);">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-base font-semibold" style="color: var(--text-primary);">Custom Domain</h2>
            <p class="text-xs mt-1" style="color: var(--text-muted);">Connect your own domain (e.g. www.mystore.com)</p>
          </div>
          <span v-if="!custom_domain_allowed" class="text-xs px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg font-medium border border-red-500/20">
            🔒 Upgrade Required
          </span>
          <span v-else class="text-xs px-2.5 py-1 bg-[#1F7A4D]/10 text-[#2FA968] rounded-lg font-medium border border-[#1F7A4D]/20">
            ✅ Eligible
          </span>
        </div>

        <div v-if="current_custom_domain" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
          <p class="text-emerald-400 text-sm font-medium">Your custom domain is active:</p>
          <a :href="'https://' + current_custom_domain" target="_blank" class="text-lg font-bold text-white hover:underline mt-1 block">
            {{ current_custom_domain }}
          </a>
        </div>

        <div v-else-if="custom_domain_request">
          
          <div v-if="custom_domain_request.status === 'pending'" class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
            <p class="text-yellow-400 text-sm font-medium">Request Pending</p>
            <p class="text-white text-sm mt-1">Admin is reviewing your request for <strong>{{ custom_domain_request.requested_value }}</strong> and will send a price quote shortly.</p>
          </div>

          <div v-else-if="custom_domain_request.status === 'quote_sent'" class="space-y-6">
            <div class="p-5 rounded-xl bg-indigo-500/10 border border-indigo-500/20">
              <p class="text-indigo-300 text-sm font-bold mb-1">Price Quote Received!</p>
              <p class="text-white text-sm">To connect <strong>{{ custom_domain_request.requested_value }}</strong>, please pay:</p>
              <p class="text-2xl font-bold text-white mt-2">Rs. {{ Number(custom_domain_request.quoted_price).toLocaleString() }}</p>
            </div>

            <div v-if="bank_accounts && bank_accounts.length > 0" class="space-y-3">
              <p class="text-sm text-white/60 font-medium">Bank Accounts for Transfer:</p>
              <div v-for="(acc, index) in bank_accounts" :key="index" class="p-4 rounded-xl bg-[#141B2E] border border-white/10 text-sm space-y-1">
                <p class="text-white font-bold">{{ acc.bank_name }}</p>
                <p class="text-white/60">Title: <span class="text-white">{{ acc.account_title }}</span></p>
                <p class="text-white/60">Account #: <span class="text-white font-mono">{{ acc.account_number }}</span></p>
              </div>
            </div>

            <form @submit.prevent="submitPayment" class="space-y-4 pt-4 border-t border-white/10">
              <div class="space-y-1.5">
                <label class="block text-sm font-medium text-white/70">Upload Payment Proof *</label>
                <input type="file" @change="onFileChange" accept="image/*" class="w-full text-sm text-white/60 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500" required />
                <p v-if="paymentForm.errors.proof_image" class="text-red-400 text-xs">{{ paymentForm.errors.proof_image }}</p>
              </div>
              <button type="submit" :disabled="paymentForm.processing" class="w-full py-3 rounded-xl font-bold text-sm text-white disabled:opacity-50" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                Submit Payment Proof
              </button>
            </form>
          </div>

          <div v-else-if="custom_domain_request.status === 'payment_submitted'" class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
            <p class="text-blue-400 text-sm font-medium">Payment Submitted</p>
            <p class="text-white text-sm mt-1">We are verifying your payment. Your domain will be linked shortly.</p>
          </div>

        </div>

        <form v-else-if="custom_domain_allowed" @submit.prevent="submitCustomDomain" class="space-y-4">
          <div>
            <label class="block text-xs font-medium mb-1.5" style="color: var(--text-muted);">Domain Name</label>
            <input v-model="customDomainForm.requested_value" type="text" placeholder="e.g. www.mystore.com" class="w-full px-4 py-2.5 text-sm rounded-xl border text-white focus:outline-none focus:border-[#1F7A4D]" style="background: var(--surface-app); border-color: var(--border-subtle);" required />
            <p v-if="customDomainForm.errors.requested_value" class="text-red-400 text-xs mt-1.5">{{ customDomainForm.errors.requested_value }}</p>
          </div>
          
          <div class="flex items-center gap-2">
            <input v-model="customDomainForm.merchant_owns_domain" type="checkbox" id="owns_domain" class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-[#1F7A4D] focus:ring-[#1F7A4D]" />
            <label for="owns_domain" class="text-sm text-white/80">I already own this domain, I just need help connecting it</label>
          </div>

          <button type="submit" :disabled="customDomainForm.processing" class="px-5 py-2.5 rounded-xl font-medium text-sm text-white bg-[#1F7A4D] hover:bg-[#2FA968] transition-colors disabled:opacity-50">
            Request Domain
          </button>
        </form>

      </div>
    </div>
  </MerchantLayout>
</template>
