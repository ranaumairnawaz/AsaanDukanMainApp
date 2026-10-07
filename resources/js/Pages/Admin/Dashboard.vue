<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  stats: Object,
  chart_data: Array,
})

const cards = [
  { label: 'Total Tenants', value: props.stats.total_tenants, color: 'blue', icon: '🏪' },
  { label: 'Active', value: props.stats.active_tenants, color: 'green', icon: '✅' },
  { label: 'Trial', value: props.stats.trial_tenants, color: 'yellow', icon: '⏳' },
  { label: 'Suspended', value: props.stats.suspended, color: 'red', icon: '🚫' },
  { label: 'Pending Subdomains', value: props.stats.pending_subdomains, color: 'orange', icon: '🌐' },
  { label: 'Active WA Numbers', value: props.stats.active_numbers + ' / ' + props.stats.total_numbers, color: 'emerald', icon: '📱' },
]

const colorMap = {
  blue: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  green: 'bg-green-500/10 text-green-400 border-green-500/20',
  yellow: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  red: 'bg-red-500/10 text-red-400 border-red-500/20',
  orange: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
  emerald: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Dashboard</h1>
    </template>

    <div class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <div
          v-for="card in cards"
          :key="card.label"
          class="card p-4 sm:p-5 transition-all hover:scale-[1.02]"
          :class="colorMap[card.color]"
        >
          <div class="text-2xl mb-1 sm:mb-2">{{ card.icon }}</div>
          <div class="text-xl sm:text-2xl font-bold">{{ card.value }}</div>
          <div class="text-xs sm:text-sm opacity-70 mt-0.5 sm:mt-1">{{ card.label }}</div>
        </div>
      </div>

      <!-- Registration Chart -->
      <div v-if="chart_data && chart_data.length" class="card p-4 sm:p-5">
        <h2 class="text-sm font-bold text-white mb-4">New Registrations (Last 7 Days)</h2>
        <div class="h-48 flex items-end gap-2 mt-4 pb-2 border-b border-white/10">
          <div v-for="point in chart_data" :key="point.date" class="flex-1 flex flex-col justify-end items-center group relative h-full">
            <!-- Tooltip -->
            <div class="absolute -top-8 bg-gray-800 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
              {{ point.tenants }} tenants
            </div>
            <!-- Bar -->
            <div class="w-full max-w-[40px] bg-blue-500/80 hover:bg-blue-400 rounded-t-sm transition-all duration-300"
                 :style="{ height: Math.max((point.tenants / (Math.max(...chart_data.map(d => d.tenants)) || 1)) * 100, 2) + '%' }">
            </div>
            <!-- Label -->
            <div class="absolute -bottom-6 text-[10px] text-gray-400 truncate w-full text-center">
              {{ point.date }}
            </div>
          </div>
        </div>
        <div class="h-6"></div> <!-- Spacer for labels -->
      </div>

      <!-- Quick Actions -->
      <div class="card p-4 sm:p-5">
        <h2 class="text-sm font-bold text-white mb-4">Quick Actions</h2>
        <div class="flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-3">
          <a :href="route('admin.tenants.subdomains')"
             class="btn-secondary text-orange-400 hover:text-orange-300 border-orange-500/20 hover:border-orange-500/40 bg-orange-500/5 hover:bg-orange-500/10 justify-center">
            🌐 Review Subdomains
            <span v-if="stats.pending_subdomains > 0" class="ml-1.5 bg-orange-500 text-black font-bold text-[10px] px-1.5 py-0.5 rounded-full">{{ stats.pending_subdomains }}</span>
          </a>
          <a :href="route('admin.plans.create')"
             class="btn-secondary text-blue-400 hover:text-blue-300 border-blue-500/20 hover:border-blue-500/40 bg-blue-500/5 hover:bg-blue-500/10 justify-center">
            ➕ Create Plan
          </a>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
