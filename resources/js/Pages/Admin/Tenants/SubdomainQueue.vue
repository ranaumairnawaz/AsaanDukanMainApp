<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  pending_tenants: Array,
})

function approve(tenant) {
  if (confirm(`Approve subdomain "${tenant.subdomain}.AssanDukan.online" for ${tenant.name}?`)) {
    useForm({}).post(route('admin.tenants.subdomain.approve', tenant))
  }
}

function reject(tenant) {
  if (confirm(`Reject subdomain request for ${tenant.name}?`)) {
    useForm({}).post(route('admin.tenants.subdomain.reject', tenant))
  }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-lg font-semibold text-white">Subdomain Requests</h1>
    </template>

    <!-- Info box -->
    <div class="bg-blue-500/10 border border-blue-500/25 rounded-xl p-4 mb-5 text-sm text-blue-300">
      <p class="font-medium mb-1">⚙️ Semi-Manual Workflow</p>
      <p class="text-blue-400 text-xs">
        After approving here, manually create the subdomain in Hostinger hPanel pointing to the same <code>public_html</code> root.
        Then clicking Approve marks it active in the database — Laravel's middleware handles the rest automatically.
      </p>
    </div>

    <div v-if="pending_tenants.length === 0" class="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center text-gray-500">
      ✅ No pending subdomain requests right now.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="tenant in pending_tenants"
        :key="tenant.id"
        class="bg-gray-900 border border-gray-800 rounded-xl p-5 flex items-center justify-between gap-4"
      >
        <div>
          <div class="font-medium text-white">{{ tenant.name }}</div>
          <div class="text-sm text-orange-400 mt-0.5">
            {{ tenant.subdomain }}.AssanDukan.online
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Plan: {{ tenant.subscription_plan?.name ?? 'None' }} · 
            Requested: {{ new Date(tenant.created_at).toLocaleDateString() }}
          </div>
        </div>
        <div class="flex gap-2 shrink-0">
          <button
            @click="approve(tenant)"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm transition-all"
          >
            ✅ Approve
          </button>
          <button
            @click="reject(tenant)"
            class="px-4 py-2 bg-red-500/15 hover:bg-red-500/25 text-red-400 border border-red-500/25 rounded-lg text-sm transition-all"
          >
            ❌ Reject
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
