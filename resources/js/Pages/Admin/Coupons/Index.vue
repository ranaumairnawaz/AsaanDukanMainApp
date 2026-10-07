<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
  coupons: Array
})

function deleteCoupon(id) {
  if (confirm('Are you sure you want to delete this coupon?')) {
    router.delete(route('admin.coupons.destroy', id))
  }
}
</script>

<template>
  <AdminLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold font-['Fraunces']">Admin Coupons</h1>
        <Link :href="route('admin.coupons.create')" class="px-4 py-2 bg-[#1F7A4D] hover:bg-[#2FA968] text-white rounded-xl font-medium transition-colors">
          + Create Coupon
        </Link>
      </div>

      <div class="bg-[#101526] border border-white/10 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-black/20 text-white/40 font-medium">
              <tr>
                <th class="px-6 py-4">Code</th>
                <th class="px-6 py-4">Discount</th>
                <th class="px-6 py-4">Uses</th>
                <th class="px-6 py-4">Expires</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="coupons.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-white/40">No coupons created yet.</td>
              </tr>
              <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 font-bold text-white">{{ coupon.code }}</td>
                <td class="px-6 py-4">
                  {{ coupon.discount_type === 'percentage' ? coupon.discount_amount + '%' : 'Rs. ' + coupon.discount_amount }}
                </td>
                <td class="px-6 py-4 text-white/60">
                  {{ coupon.used_count }} / {{ coupon.max_uses ?? '∞' }}
                </td>
                <td class="px-6 py-4 text-white/60">
                  {{ coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString() : 'Never' }}
                </td>
                <td class="px-6 py-4">
                  <span v-if="coupon.is_active" class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded-lg text-xs font-medium">Active</span>
                  <span v-else class="px-2.5 py-1 bg-red-500/10 text-red-400 rounded-lg text-xs font-medium">Inactive</span>
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                  <Link :href="route('admin.coupons.edit', coupon.id)" class="text-blue-400 hover:text-blue-300">Edit</Link>
                  <button @click="deleteCoupon(coupon.id)" class="text-red-400 hover:text-red-300">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
