<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  coupons: Array,
})

const showModal = ref(false)
const editingCoupon = ref(null)

const form = useForm({
  code: '',
  type: 'percentage',
  value: '',
  usage_limit: '',
  expires_at: '',
  is_active: true,
})

function openModal(coupon = null) {
  editingCoupon.value = coupon
  if (coupon) {
    form.code = coupon.code
    form.type = coupon.type
    form.value = coupon.value
    form.usage_limit = coupon.usage_limit ?? ''
    form.expires_at = coupon.expires_at ? new Date(coupon.expires_at).toISOString().split('T')[0] : ''
    form.is_active = coupon.is_active
  } else {
    form.reset()
    form.is_active = true
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  form.reset()
  form.clearErrors()
}

function submit() {
  if (editingCoupon.value) {
    form.put(route('merchant.coupons.update', editingCoupon.value.id), {
      onSuccess: closeModal
    })
  } else {
    form.post(route('merchant.coupons.store'), {
      onSuccess: closeModal
    })
  }
}

function deleteCoupon(id) {
  if (confirm('Are you sure you want to delete this coupon?')) {
    router.delete(route('merchant.coupons.destroy', id))
  }
}

function generateCode() {
  form.code = Math.random().toString(36).substring(2, 10).toUpperCase()
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Discount Coupons</h1>
    </template>

    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h2 class="page-title">Coupons</h2>
          <p class="page-subtitle">Create codes to offer discounts.</p>
        </div>
        <button @click="openModal()" class="btn-primary shrink-0">
          + Create Coupon
        </button>
      </div>

      <!-- Mobile Cards (< md) -->
      <div class="space-y-3 md:hidden">
        <div v-for="coupon in coupons" :key="coupon.id" class="card-padded space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-white font-bold font-mono text-base">{{ coupon.code }}</div>
              <div v-if="coupon.expires_at" class="text-[10px] text-gray-500 mt-0.5">
                Exp: {{ new Date(coupon.expires_at).toLocaleDateString() }}
              </div>
            </div>
            <span v-if="coupon.is_active" class="badge badge-green">Active</span>
            <span v-else class="badge badge-gray">Disabled</span>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-indigo-400 font-bold">
              {{ coupon.type === 'percentage' ? coupon.value + '%' : 'Rs. ' + coupon.value }}
            </span>
            <span class="text-gray-500 text-xs">off</span>
          </div>

          <div>
            <div class="text-xs text-gray-400 mb-1">
              {{ coupon.times_used }} {{ coupon.usage_limit ? `/ ${coupon.usage_limit}` : 'used' }}
            </div>
            <div v-if="coupon.usage_limit" class="w-full bg-gray-800 rounded-full h-1.5 overflow-hidden">
              <div class="bg-indigo-500 h-full rounded-full" :style="`width: ${Math.min(100, (coupon.times_used / coupon.usage_limit) * 100)}%`"></div>
            </div>
          </div>

          <div class="flex gap-2 pt-2 border-t border-gray-800">
            <button @click="openModal(coupon)" class="btn-secondary btn-sm flex-1">Edit</button>
            <button @click="deleteCoupon(coupon.id)" class="btn-danger btn-sm px-3">🗑️</button>
          </div>
        </div>
        <div v-if="coupons.length === 0" class="card p-10 text-center text-gray-500">
          No coupons created yet.
        </div>
      </div>

      <!-- Desktop Table (>= md) -->
      <div class="card overflow-hidden hidden md:block">
        <div v-if="coupons.length === 0" class="p-12 text-center text-gray-500 text-sm">
          No coupons created yet.
        </div>
        
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Discount</th>
              <th>Usage</th>
              <th>Status</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="coupon in coupons" :key="coupon.id">
              <td>
                <div class="text-white font-bold font-mono text-base">{{ coupon.code }}</div>
                <div v-if="coupon.expires_at" class="text-xs text-gray-500 mt-0.5">
                  Expires: {{ new Date(coupon.expires_at).toLocaleDateString() }}
                </div>
              </td>
              <td>
                <span class="text-indigo-400 font-bold">
                  {{ coupon.type === 'percentage' ? coupon.value + '%' : 'Rs. ' + coupon.value }}
                </span>
                <span class="text-gray-500 text-xs ml-1">off</span>
              </td>
              <td class="w-48">
                <div class="text-gray-300 text-xs mb-1.5">
                  {{ coupon.times_used }} {{ coupon.usage_limit ? `/ ${coupon.usage_limit}` : 'used' }}
                </div>
                <div v-if="coupon.usage_limit" class="w-full bg-gray-800 rounded-full h-1.5 overflow-hidden">
                  <div class="bg-indigo-500 h-full rounded-full" :style="`width: ${Math.min(100, (coupon.times_used / coupon.usage_limit) * 100)}%`"></div>
                </div>
              </td>
              <td>
                <span v-if="coupon.is_active" class="badge badge-green">Active</span>
                <span v-else class="badge badge-gray">Disabled</span>
              </td>
              <td class="text-right space-x-2">
                <button @click="openModal(coupon)" class="btn-secondary btn-sm">Edit</button>
                <button @click="deleteCoupon(coupon.id)" class="btn-danger btn-sm">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
      <div class="bg-[#111] border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="font-bold text-white text-lg mb-1">{{ editingCoupon ? 'Edit Coupon' : 'Create Coupon' }}</h3>
        
        <form @submit.prevent="submit" class="space-y-4 mt-6">
          <div>
            <label class="form-label">Coupon Code</label>
            <div class="flex gap-2">
              <input v-model="form.code" type="text" required class="form-input uppercase font-mono placeholder:normal-case" placeholder="e.g. SUMMER50" />
              <button type="button" @click="generateCode" class="btn-secondary btn-sm px-4">Generate</button>
            </div>
            <p v-if="form.errors.code" class="form-error">{{ form.errors.code }}</p>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Discount Type</label>
              <select v-model="form.type" class="form-input">
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount (Rs.)</option>
              </select>
            </div>
            
            <div>
              <label class="form-label">Discount Value</label>
              <input v-model="form.value" type="number" step="0.01" min="0" required class="form-input" />
              <p v-if="form.errors.value" class="form-error">{{ form.errors.value }}</p>
            </div>
          </div>

          <div>
            <label class="form-label">Usage Limit <span class="font-normal text-gray-500">(Optional)</span></label>
            <input v-model="form.usage_limit" type="number" min="1" placeholder="Leave blank for unlimited" class="form-input" />
          </div>

          <div>
            <label class="form-label">Expiry Date <span class="font-normal text-gray-500">(Optional)</span></label>
            <input v-model="form.expires_at" type="date" class="form-input style-color-scheme-dark" />
          </div>

          <div class="flex items-center gap-3 pt-2">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 accent-emerald-500" />
            <label for="is_active" class="text-sm text-gray-300 cursor-pointer">Coupon is active and can be used</label>
          </div>

          <div class="flex gap-3 pt-4">
            <button type="button" @click="closeModal" class="btn-secondary flex-1 py-2.5">Cancel</button>
            <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-2.5">
              {{ form.processing ? 'Saving...' : 'Save Coupon' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </MerchantLayout>
</template>

<style>
.style-color-scheme-dark {
  color-scheme: dark;
}
</style>
