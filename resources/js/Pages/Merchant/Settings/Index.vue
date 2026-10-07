<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  can_change_store_info: Boolean,
  store_info_change_fee: Number,
  theme_change_fee: Number,
  pending_unlocks: Array,
  approved_unlocks: Array,
})

const form = useForm({
  name: props.tenant.name || '',
  store_slug: props.tenant.store_slug || '',
  whatsapp_number: props.tenant.whatsapp_number || '',
  delivery_charge: props.tenant.delivery_charge || 0,
  bank_name: props.tenant.bank_name || '',
  account_title: props.tenant.account_title || '',
  account_number: props.tenant.account_number || '',
})

const unlockForm = useForm({
  unlock_type: '',
  payment_proof: null,
})

function submit() {
  form.post(route('merchant.settings.update'), { preserveScroll: true })
}

function requestUnlock(type) {
  unlockForm.unlock_type = type
  document.getElementById('unlock-proof-input').click()
}

function handleProofSelected(e) {
  const file = e.target.files[0]
  if (!file) return
  unlockForm.payment_proof = file
  unlockForm.post(route('merchant.settings.unlock'), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { unlockForm.payment_proof = null; e.target.value = '' }
  })
}

function isPending(type) {
  return props.pending_unlocks?.includes(type)
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <h1 class="font-['Fraunces'] text-base font-semibold" style="color: var(--text-primary);">Store Settings</h1>
    </template>

    <!-- Hidden file input for unlock proof -->
    <input id="unlock-proof-input" type="file" accept="image/*" class="hidden" @change="handleProofSelected" />

    <div class="px-4 sm:px-6 py-6 max-w-4xl space-y-8">

      <form @submit.prevent="submit" class="space-y-8">

        <!-- Store Identity -->
        <div class="p-6 rounded-2xl border space-y-5" style="background: var(--surface-panel); border-color: var(--border-subtle);">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h2 class="text-base font-semibold" style="color: var(--text-primary);">Store Identity</h2>
              <p class="text-xs mt-1" style="color: var(--text-muted);">Manage your store's name and URL slug.</p>
            </div>
            <!-- Unlock button for store info -->
            <div v-if="!can_change_store_info" class="shrink-0">
              <div v-if="isPending('store_info_change')" class="flex items-center gap-2 text-xs px-3 py-2 bg-yellow-500/10 text-yellow-400 border border-yellow-500/30 rounded-xl">
                ⏳ Unlock request pending admin approval
              </div>
              <button v-else type="button" @click="requestUnlock('store_info_change')" :disabled="unlockForm.processing"
                class="flex items-center gap-2 text-xs px-4 py-2 rounded-xl border border-[#1F7A4D]/40 bg-[#1F7A4D]/10 font-semibold transition-all hover:bg-[#1F7A4D]/20 disabled:opacity-50"
                style="color: var(--accent-green-text);">
                🔓 Pay Rs. {{ Number(store_info_change_fee).toLocaleString() }} to Unlock
              </button>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6 pt-4 border-t" style="border-color: var(--border-subtle);">
            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Store Name</label>
              <input v-model="form.name" type="text" :disabled="!can_change_store_info"
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all"
                :class="!can_change_store_info ? 'opacity-50 cursor-not-allowed' : 'focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20'"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Store URL Slug</label>
              <div class="flex">
                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 text-xs whitespace-nowrap"
                  style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-muted);">
                  /store/
                </span>
                <input v-model="form.store_slug" type="text" :disabled="!can_change_store_info"
                  class="w-full px-3 py-2.5 rounded-r-xl border focus:outline-none transition-all"
                  :class="!can_change_store_info ? 'opacity-50 cursor-not-allowed' : 'focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20'"
                  style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              </div>
              <p v-if="form.errors.store_slug" class="text-red-500 text-xs mt-1">{{ form.errors.store_slug }}</p>
            </div>

            <div v-if="!can_change_store_info" class="md:col-span-2 text-xs flex items-start gap-2 p-3 rounded-lg bg-[#C4432E]/10" style="color: var(--accent-red-text);">
              <span class="shrink-0">🔒</span>
              <span>Store Name and URL Slug are locked on your current plan. Click <strong>"Pay to Unlock"</strong> above, upload your payment proof, and the admin will approve it within 24 hours.</span>
            </div>
          </div>
        </div>

        <!-- Checkout Settings -->
        <div class="p-6 rounded-2xl border space-y-5" style="background: var(--surface-panel); border-color: var(--border-subtle);">
          <div>
            <h2 class="text-base font-semibold" style="color: var(--text-primary);">Checkout Settings</h2>
            <p class="text-xs mt-1" style="color: var(--text-muted);">Configure how you receive orders and charge for delivery.</p>
          </div>

          <div class="grid md:grid-cols-2 gap-6 pt-4 border-t" style="border-color: var(--border-subtle);">
            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">WhatsApp Number (For Orders)</label>
              <input v-model="form.whatsapp_number" type="tel" placeholder="03001234567"
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p class="text-[10px] mt-1" style="color: var(--text-muted);">Customers will be redirected here after checkout to confirm their order.</p>
              <p v-if="form.errors.whatsapp_number" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Flat Delivery Charge (Rs.)</label>
              <input v-model="form.delivery_charge" type="number" min="0" step="1"
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p v-if="form.errors.delivery_charge" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_charge }}</p>
            </div>
          </div>
        </div>

        <!-- Bank Details -->
        <div class="p-6 rounded-2xl border space-y-5" style="background: var(--surface-panel); border-color: var(--border-subtle);">
          <div>
            <h2 class="text-base font-semibold" style="color: var(--text-primary);">Bank Account Details <span class="text-xs font-normal opacity-60">(Optional)</span></h2>
            <p class="text-xs mt-1" style="color: var(--text-muted);">If provided, 'Bank Transfer' will appear as a payment option at checkout.</p>
          </div>

          <div class="grid md:grid-cols-2 gap-6 pt-4 border-t" style="border-color: var(--border-subtle);">
            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Bank Name</label>
              <input v-model="form.bank_name" type="text" placeholder="e.g. Meezan Bank, SadaPay..."
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p v-if="form.errors.bank_name" class="text-red-500 text-xs mt-1">{{ form.errors.bank_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Account Title</label>
              <input v-model="form.account_title" type="text" placeholder="e.g. John Doe"
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p v-if="form.errors.account_title" class="text-red-500 text-xs mt-1">{{ form.errors.account_title }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1.5" style="color: var(--text-primary);">Account Number / IBAN</label>
              <input v-model="form.account_number" type="text" placeholder="e.g. PK00 MEZN 0000 0000 0000 00"
                class="w-full px-4 py-2.5 rounded-xl border focus:outline-none transition-all focus:border-[#1F7A4D] focus:ring-1 focus:ring-[#1F7A4D]/20"
                style="background: var(--surface-page); border-color: var(--border-subtle); color: var(--text-primary);" />
              <p v-if="form.errors.account_number" class="text-red-500 text-xs mt-1">{{ form.errors.account_number }}</p>
            </div>
          </div>
        </div>

        <div class="flex justify-end pb-12">
          <button type="submit" :disabled="form.processing"
            class="px-8 py-3 rounded-xl text-sm font-bold text-white transition-all hover:brightness-110 active:scale-95 disabled:opacity-50"
            style="background: var(--accent-green);">
            {{ form.processing ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </MerchantLayout>
</template>
