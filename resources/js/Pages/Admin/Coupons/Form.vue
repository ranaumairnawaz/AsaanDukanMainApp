<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  coupon: {
    type: Object,
    default: () => null
  }
})

const form = useForm({
  code: props.coupon?.code ?? '',
  discount_type: props.coupon?.discount_type ?? 'percentage',
  discount_amount: props.coupon?.discount_amount ?? '',
  max_uses: props.coupon?.max_uses ?? '',
  expires_at: props.coupon?.expires_at ? props.coupon.expires_at.split('T')[0] : '',
  is_active: props.coupon?.is_active ?? true,
})

function submit() {
  if (props.coupon) {
    form.put(route('admin.coupons.update', props.coupon.id))
  } else {
    form.post(route('admin.coupons.store'))
  }
}
</script>

<template>
  <AdminLayout>
    <div class="p-6 max-w-3xl mx-auto">
      <div class="flex items-center gap-4 mb-6">
        <Link :href="route('admin.coupons.index')" class="text-white/40 hover:text-white">
          ← Back
        </Link>
        <h1 class="text-2xl font-bold font-['Fraunces']">{{ coupon ? 'Edit Coupon' : 'Create Coupon' }}</h1>
      </div>

      <form @submit.prevent="submit" class="bg-[#101526] border border-white/10 rounded-2xl p-6 space-y-6">
        <div>
          <label class="block text-sm font-medium text-white/60 mb-2">Coupon Code</label>
          <input v-model="form.code" type="text" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D] uppercase" placeholder="e.g. SUMMER50" required />
          <p v-if="form.errors.code" class="text-red-400 text-xs mt-1">{{ form.errors.code }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Discount Type</label>
            <select v-model="form.discount_type" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed Amount (Rs.)</option>
            </select>
            <p v-if="form.errors.discount_type" class="text-red-400 text-xs mt-1">{{ form.errors.discount_type }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Discount Amount</label>
            <input v-model="form.discount_amount" type="number" step="0.01" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]" required />
            <p v-if="form.errors.discount_amount" class="text-red-400 text-xs mt-1">{{ form.errors.discount_amount }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Max Uses (Optional)</label>
            <input v-model="form.max_uses" type="number" placeholder="Leave empty for unlimited" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]" />
            <p v-if="form.errors.max_uses" class="text-red-400 text-xs mt-1">{{ form.errors.max_uses }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Expires At (Optional)</label>
            <input v-model="form.expires_at" type="date" class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#1F7A4D]" style="color-scheme: dark" />
            <p v-if="form.errors.expires_at" class="text-red-400 text-xs mt-1">{{ form.errors.expires_at }}</p>
          </div>
        </div>

        <div>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="w-5 h-5 rounded bg-[#141B2E] border-white/10 text-[#1F7A4D] focus:ring-[#1F7A4D] focus:ring-offset-0 focus:ring-offset-transparent" />
            <span class="text-white">Active</span>
          </label>
        </div>

        <div class="pt-4 border-t border-white/5">
          <button type="submit" class="w-full py-3 bg-[#1F7A4D] hover:bg-[#2FA968] text-white rounded-xl font-medium transition-colors" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Save Coupon' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
