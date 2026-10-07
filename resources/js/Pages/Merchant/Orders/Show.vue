<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  order: Object,
})

const statusColors = {
  pending: 'bg-yellow-500/15 text-yellow-400 border border-yellow-500/20',
  confirmed: 'bg-blue-500/15 text-blue-400 border border-blue-500/20',
  processing: 'bg-purple-500/15 text-purple-400 border border-purple-500/20',
  shipped: 'bg-orange-500/15 text-orange-400 border border-orange-500/20',
  delivered: 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20',
  cancelled: 'bg-red-500/15 text-red-400 border border-red-500/20',
}

const form = useForm({
  status: props.order.status,
  courier_name: props.order.courier_name ?? '',
  tracking_number: props.order.tracking_number ?? '',
})

function saveChanges() {
  form.put(route('merchant.orders.update', props.order.id), {
    preserveScroll: true,
  })
}

// Print packing slip
function printOrder() {
  window.print()
}
</script>

<template>
  <MerchantLayout>
    <div class="space-y-4 sm:space-y-6">
      
      <!-- Top nav -->
      <div class="flex items-center gap-4 text-xs sm:text-sm print:hidden">
        <Link :href="route('merchant.orders.index')" class="text-gray-400 hover:text-white transition-colors flex items-center gap-2">
          <span>&larr;</span> Back to Orders
        </Link>
      </div>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-4 sm:pb-6">
        <div>
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-1">
            <h1 class="text-xl sm:text-2xl font-bold text-white font-mono">#{{ order.order_number }}</h1>
            <span class="badge border uppercase tracking-wider font-bold w-max" :class="statusColors[order.status]">
              {{ order.status }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-gray-500">{{ new Date(order.created_at).toLocaleString() }}</p>
        </div>
        <div class="flex gap-2 print:hidden w-full sm:w-auto">
          <button @click="printOrder" class="btn-secondary w-full sm:w-auto px-4 py-2 text-xs sm:text-sm">
            🖨️ Print Slip
          </button>
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left: Items & Totals -->
        <div class="md:col-span-2 space-y-4 sm:space-y-6">
          
          <div class="card overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-white/5">
              <h2 class="font-bold text-white text-sm sm:text-base">Order Items</h2>
            </div>
            
            <div class="divide-y divide-white/5">
              <div v-for="item in order.items" :key="item.id" class="flex flex-col sm:flex-row gap-4 p-4 sm:p-6">
                <div class="flex gap-4 w-full">
                  <div class="w-16 h-16 rounded-xl bg-gray-900 overflow-hidden shrink-0 border border-white/5">
                    <img v-if="item.product?.images?.[0]" :src="`/storage/${item.product.images[0]}`" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-[10px] sm:text-xs text-gray-500">No Img</div>
                  </div>
                  
                  <div class="flex-1 min-w-0 flex flex-col justify-center">
                    <h3 class="text-sm sm:text-base font-bold text-white truncate">{{ item.product?.name ?? 'Unknown Product' }}</h3>
                    <div class="text-[10px] sm:text-sm text-gray-400 mt-0.5 sm:mt-1 flex flex-wrap items-center gap-1 sm:gap-2">
                      <span>Rs. {{ Number(item.unit_price).toLocaleString() }} × {{ item.quantity }}</span>
                      <span v-if="item.variant" class="badge bg-white/5 text-gray-400 border border-white/10 shrink-0">
                        {{ item.variant.option_name }}: {{ item.variant.option_value }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <div class="sm:text-right mt-2 sm:mt-0 flex justify-between sm:block items-center pl-20 sm:pl-0">
                  <div class="text-[10px] sm:hidden text-gray-500 uppercase tracking-widest font-bold">Subtotal</div>
                  <div class="text-sm sm:text-base font-bold text-white">Rs. {{ Number(item.subtotal).toLocaleString() }}</div>
                </div>
              </div>
            </div>

            <!-- Totals -->
            <div class="bg-black/20 p-4 sm:p-6 border-t border-white/5 space-y-2 sm:space-y-3">
              <div class="flex justify-between text-xs sm:text-sm text-gray-400">
                <span>Subtotal</span>
                <span>Rs. {{ Number(order.subtotal).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-xs sm:text-sm text-gray-400">
                <span>Delivery Fee</span>
                <span>Rs. {{ Number(order.delivery_fee).toLocaleString() }}</span>
              </div>
              <div class="flex justify-between text-sm sm:text-base font-bold text-white pt-2 sm:pt-3 border-t border-white/5">
                <span>Total</span>
                <span>Rs. {{ Number(order.total).toLocaleString() }}</span>
              </div>
            </div>
          </div>
          
        </div>

        <!-- Right: Customer & Management -->
        <div class="space-y-4 sm:space-y-6">
          
          <!-- Customer Info -->
          <div class="card p-4 sm:p-6 space-y-4">
            <h2 class="font-bold text-white text-sm sm:text-base border-b border-white/5 pb-3">Customer</h2>
            <div>
              <div class="text-white font-bold text-sm sm:text-base">{{ order.customer?.name ?? 'Guest' }}</div>
              <div class="text-xs sm:text-sm text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                <span>📱 {{ order.customer?.phone ?? '—' }}</span>
                <a :href="`https://wa.me/${order.customer?.phone?.replace(/\D/g, '')}`" target="_blank" class="text-emerald-400 text-xs hover:underline print:hidden bg-emerald-500/10 px-2 py-0.5 rounded">Chat</a>
              </div>
            </div>
            
            <div class="pt-4 border-t border-white/5">
              <h3 class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 sm:mb-2">Delivery Address</h3>
              <p class="text-xs sm:text-sm text-gray-300 leading-relaxed whitespace-pre-wrap">{{ order.delivery_address }}</p>
            </div>
            
            <div v-if="order.notes" class="pt-4 border-t border-white/5">
              <h3 class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5 sm:mb-2">Order Notes</h3>
              <p class="text-xs sm:text-sm text-amber-200 bg-amber-500/10 p-3 rounded-xl border border-amber-500/20 leading-relaxed">{{ order.notes }}</p>
            </div>
          </div>

          <!-- Order Management -->
          <div class="card p-4 sm:p-6 space-y-4 sm:space-y-5 print:hidden">
            <h2 class="font-bold text-white text-sm sm:text-base border-b border-white/5 pb-3">Management</h2>
            
            <form @submit.prevent="saveChanges" class="space-y-4">
              <div>
                <label class="form-label">Status</label>
                <select v-model="form.status" class="form-input">
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="processing">Processing</option>
                  <option value="shipped">Shipped</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                <p v-if="form.status === 'shipped'" class="text-[10px] text-emerald-400 mt-1.5 font-bold">🚚 Customer will be notified via WhatsApp</p>
              </div>
              
              <div class="pt-2 border-t border-white/5">
                <label class="form-label">Courier Name</label>
                <input v-model="form.courier_name" type="text" placeholder="e.g. TCS, Leopard" class="form-input" />
              </div>
              
              <div>
                <label class="form-label">Tracking Number</label>
                <input v-model="form.tracking_number" type="text" placeholder="e.g. 1234567890" class="form-input font-mono" />
              </div>

              <div class="pt-4 border-t border-white/5">
                <button type="submit" :disabled="form.processing" class="btn-primary w-full py-3">
                  {{ form.processing ? 'Saving...' : 'Update Order' }}
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </MerchantLayout>
</template>
