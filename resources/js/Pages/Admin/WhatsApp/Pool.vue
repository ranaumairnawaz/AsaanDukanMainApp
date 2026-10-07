<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  numbers: Array,
})

const addForm = useForm({ phone_number: '', notes: '' })

const statusColors = {
  active: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25',
  standby: 'bg-blue-500/15 text-blue-400 border-blue-500/25',
  warming: 'bg-yellow-500/15 text-yellow-400 border-yellow-500/25',
  banned: 'bg-red-500/15 text-red-400 border-red-500/25',
  disconnected: 'bg-gray-500/15 text-gray-400 border-gray-500/25',
}

const statusIcons = {
  active: '🟢',
  standby: '🔵',
  warming: '🔥',
  banned: '🚫',
  disconnected: '⚫',
}

function activate(number) {
  if (confirm(`Failover to ${number.phone_number} as the active gateway number?`)) {
    useForm({}).post(route('admin.whatsapp.activate', number))
  }
}

function remove(number) {
  if (confirm(`Remove ${number.phone_number} from pool?`)) {
    useForm({}).delete(route('admin.whatsapp.destroy', number))
  }
}

function addNumber() {
  addForm.post(route('admin.whatsapp.store'), { onSuccess: () => addForm.reset() })
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">WhatsApp Number Pool</h1>
    </template>

    <div class="space-y-5">
      <!-- Warning banner -->
      <div class="bg-orange-500/10 border border-orange-500/25 rounded-xl p-4 text-sm text-orange-300">
        <p class="font-bold mb-1">⚠️ Concentrated Risk Architecture</p>
        <p class="text-orange-400 text-xs">
          All platform messages go through the <strong>active</strong> number. Keep 2–3 warmed backup numbers ready.
          If the active number is banned, click Failover on a standby number immediately.
        </p>
      </div>

      <!-- Number Pool List -->
      <div class="card overflow-hidden">
        <div class="p-4 border-b border-white/5 text-sm font-bold text-white">
          Pool ({{ numbers.length }} numbers)
        </div>
        <div class="divide-y divide-white/5">
          <div v-for="number in numbers" :key="number.id" class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center flex-wrap gap-2">
                <span>{{ statusIcons[number.status] }}</span>
                <span class="font-mono font-bold text-white text-base">{{ number.phone_number }}</span>
                <span class="badge uppercase tracking-wider font-bold border-0" :class="statusColors[number.status]">
                  {{ number.status }}
                </span>
              </div>
              <div class="text-[10px] sm:text-xs text-gray-500 mt-1">
                <span v-if="number.last_active_at">Last active: {{ new Date(number.last_active_at).toLocaleString() }} · </span>
                <span v-if="number.notes">{{ number.notes }}</span>
              </div>
            </div>
            <div class="flex gap-2 shrink-0 border-t sm:border-0 border-white/5 pt-3 sm:pt-0 mt-1 sm:mt-0">
              <button
                v-if="number.status !== 'active'"
                @click="activate(number)"
                class="btn-secondary btn-sm flex-1 sm:flex-none text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10 justify-center"
              >
                ⚡ Failover
              </button>
              <span v-else class="btn-secondary btn-sm flex-1 sm:flex-none bg-emerald-500/10 text-emerald-400 border-emerald-500/20 cursor-default justify-center">
                ● Active
              </span>
              <button
                v-if="number.status !== 'active'"
                @click="remove(number)"
                class="btn-danger btn-sm flex-1 sm:flex-none justify-center px-4"
              >
                Remove
              </button>
            </div>
          </div>
          <div v-if="numbers.length === 0" class="p-10 text-center text-gray-500">
            No numbers registered yet.
          </div>
        </div>
      </div>

      <!-- Add Number Form -->
      <div class="card p-4 sm:p-5 space-y-4">
        <h2 class="text-sm font-bold text-white">Register New Number</h2>
        <form @submit.prevent="addNumber" class="flex flex-col sm:flex-row gap-3 sm:items-end">
          <div class="flex-1 sm:flex-none">
            <label class="form-label">Phone Number (with country code)</label>
            <input v-model="addForm.phone_number" type="text" placeholder="+923001234567" class="form-input sm:w-52" />
          </div>
          <div class="flex-1 sm:flex-none">
            <label class="form-label">Notes (optional)</label>
            <input v-model="addForm.notes" type="text" placeholder="e.g. backup SIM #2" class="form-input sm:w-48" />
          </div>
          <button type="submit" :disabled="addForm.processing" class="btn-primary w-full sm:w-auto py-2.5 sm:py-2">
            + Register
          </button>
        </form>
        <p class="text-[10px] sm:text-xs text-gray-500">
          After registering, pair it via the WhatsApp Gateway QR scan panel at your Hostinger Node.js app URL.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>
