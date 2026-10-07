<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  tenant: Object,
  plan: Object,
  total_due: Number,
  overage_cost: Number,
  plan_price: Number,
  bank_accounts: Array,
  is_upgrade: { type: Boolean, default: false },
})

const bankForm = useForm({
  proof_image: null,
  notes: '',
  plan_id: props.plan?.id ?? null,
  coupon_code: null,
})

const couponCode = ref('')
const appliedCoupon = ref(null)
const couponError = ref('')
const isApplyingCoupon = ref(false)

const finalTotalDue = computed(() => {
  let total = props.total_due;
  if (appliedCoupon.value) {
    if (appliedCoupon.value.discount_type === 'percentage') {
      total -= total * (appliedCoupon.value.discount_amount / 100);
    } else {
      total -= appliedCoupon.value.discount_amount;
    }
  }
  return Math.max(0, total);
})

const discountAmount = computed(() => {
  return Math.max(0, props.total_due - finalTotalDue.value);
})

async function applyCoupon() {
  if (!couponCode.value) return;
  isApplyingCoupon.value = true;
  couponError.value = '';
  try {
    const res = await axios.post(route('merchant.payment.validate-coupon'), { code: couponCode.value });
    appliedCoupon.value = res.data;
    bankForm.coupon_code = res.data.code;
  } catch (err) {
    couponError.value = err.response?.data?.error || 'Failed to apply coupon';
    appliedCoupon.value = null;
    bankForm.coupon_code = null;
  } finally {
    isApplyingCoupon.value = false;
  }
}

function removeCoupon() {
  appliedCoupon.value = null;
  couponCode.value = '';
  bankForm.coupon_code = null;
}

function submitBank() {
  bankForm.post(route('merchant.payment.bank'), {
    forceFormData: true,
  })
}

function onFileChange(e) {
  bankForm.proof_image = e.target.files[0]
}
</script>

<template>
  <MerchantLayout :tenant="tenant">
    <div class="max-w-2xl mx-auto space-y-8">

      <!-- Upgrade Banner -->
      <div v-if="is_upgrade"
        class="p-4 rounded-2xl border-2 border-emerald-500/40 flex items-center gap-3"
        style="background: linear-gradient(135deg, #022c22, #064e3b);">
        <div class="text-2xl">⬆️</div>
        <div>
          <p class="font-bold text-emerald-300 text-sm">You're upgrading your plan!</p>
          <p class="text-emerald-200/60 text-xs mt-0.5">Once your payment is approved by admin, your account will be switched to the <strong class="text-emerald-300">{{ plan?.name }}</strong> plan.</p>
        </div>
      </div>

      <div>
        <h1 class="text-2xl font-bold text-white">{{ is_upgrade ? 'Upgrade Plan' : 'Make a Payment' }}</h1>
        <p class="text-white/50 text-sm mt-1">Pay for your {{ plan?.name }} subscription</p>
      </div>

      <!-- Amount Summary -->
      <div class="p-6 rounded-2xl border" style="background: #111827; border-color: #1f2937;">
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-white/60">Plan ({{ plan?.name }})</span>
            <span class="text-white font-semibold">Rs. {{ Number(plan_price).toLocaleString() }}</span>
          </div>
          <div v-if="overage_cost > 0" class="flex justify-between">
            <span class="text-amber-400">Overage Charges</span>
            <span class="text-amber-400 font-semibold">Rs. {{ Number(overage_cost).toLocaleString() }}</span>
          </div>
          <div v-if="appliedCoupon" class="flex justify-between border-t border-white/10 pt-3 text-emerald-400">
            <span>Discount ({{ appliedCoupon.code }})</span>
            <span class="font-semibold">- Rs. {{ Number(discountAmount).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between border-t border-white/10 pt-3 text-lg font-bold">
            <span class="text-white">Total Due</span>
            <span class="text-emerald-400">Rs. {{ Number(finalTotalDue).toLocaleString() }}</span>
          </div>
        </div>

        <div class="mt-6 pt-6 border-t border-white/10">
          <label class="block text-sm font-medium text-white/70 mb-2">Have a promo code?</label>
          <div v-if="appliedCoupon" class="flex items-center justify-between bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="text-emerald-400">✓</span>
              <span class="text-white text-sm font-bold uppercase">{{ appliedCoupon.code }} applied!</span>
            </div>
            <button @click="removeCoupon" class="text-emerald-400 hover:text-emerald-300 text-sm font-medium">Remove</button>
          </div>
          <div v-else class="flex gap-2">
            <input v-model="couponCode" @keyup.enter="applyCoupon" type="text" placeholder="Enter code" class="flex-1 px-4 py-2.5 rounded-xl border text-sm text-white uppercase" style="background: #1a1d27; border-color: #2d3048;" />
            <button @click="applyCoupon" :disabled="!couponCode || isApplyingCoupon" class="px-5 py-2.5 rounded-xl font-bold text-sm text-white disabled:opacity-50" style="background: #374151;">
              {{ isApplyingCoupon ? '...' : 'Apply' }}
            </button>
          </div>
          <p v-if="couponError" class="text-red-400 text-xs mt-2">{{ couponError }}</p>
        </div>
      </div>

      <!-- Bank Transfer Form -->
      <div class="p-6 rounded-2xl border space-y-5" style="background: #111827; border-color: #1f2937;">
        <h3 class="font-bold text-white">Bank Transfer Details</h3>

        <div v-if="bank_accounts && bank_accounts.length > 0" class="space-y-3">
          <div v-for="(acc, index) in bank_accounts" :key="index"
            class="p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-sm space-y-1">
            <p class="text-indigo-300 font-bold">Bank: {{ acc.bank_name }}</p>
            <p class="text-white/60">Account Title: <span class="text-white">{{ acc.account_title }}</span></p>
            <p class="text-white/60">Account #: <span class="text-white font-mono">{{ acc.account_number }}</span></p>
            <p v-if="acc.iban" class="text-white/60">IBAN: <span class="text-white font-mono">{{ acc.iban }}</span></p>
          </div>
        </div>
        <div v-else class="p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-sm text-white/50 italic">
          No payment accounts configured. Please contact admin.
        </div>

        <form @submit.prevent="submitBank" class="space-y-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-white/70">Upload Payment Proof *</label>
            <input type="file" @change="onFileChange" accept="image/*"
              class="w-full text-sm text-white/60 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500" />
            <p v-if="bankForm.errors.proof_image" class="text-red-400 text-xs">{{ bankForm.errors.proof_image }}</p>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-white/70">Notes (optional)</label>
            <textarea v-model="bankForm.notes" rows="2" placeholder="Transaction ID or reference..."
              class="w-full px-4 py-3 rounded-xl border text-sm text-white"
              style="background: #1a1d27; border-color: #2d3048;"></textarea>
          </div>
          <button type="submit" :disabled="bankForm.processing"
            class="w-full py-4 rounded-xl font-bold text-sm text-white disabled:opacity-50"
            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            {{ bankForm.processing ? 'Submitting...' : 'Submit Payment Proof' }}
          </button>
        </form>
      </div>

    </div>
  </MerchantLayout>
</template>
