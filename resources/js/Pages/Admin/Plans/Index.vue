<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  plans: Array,
})

function deletePlan(plan) {
  if (confirm(`Delete plan "${plan.name}"? This cannot be undone.`)) {
    useForm({}).delete(route('admin.plans.destroy', plan))
  }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Subscription Plans</h1>
    </template>

    <div class="page-header">
      <div>
        <h2 class="page-title">Plans</h2>
        <p class="page-subtitle">{{ plans.length }} plan(s) configured</p>
      </div>
      <Link :href="route('admin.plans.create')" class="btn-primary shrink-0">
        + New Plan
      </Link>
    </div>

    <div class="space-y-4">
      <div
        v-for="plan in plans"
        :key="plan.id"
        class="card p-4 sm:p-5 hover:border-gray-700 transition-colors"
      >
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="font-bold text-white text-lg">{{ plan.name }}</h2>
              <span v-if="!plan.is_active" class="badge badge-gray">Inactive</span>
              <span class="badge badge-blue font-mono text-[10px]">{{ plan.slug }}</span>
            </div>
            <div class="text-sm text-gray-400 mt-1">
              Rs. {{ Number(plan.price_monthly).toLocaleString() }}/mo
              <span class="text-gray-600 mx-1">·</span>
              Rs. {{ Number(plan.price_yearly).toLocaleString() }}/yr
            </div>
          </div>
          <div class="flex gap-2 shrink-0">
            <Link :href="route('admin.plans.edit', plan)" class="btn-secondary btn-sm">
              ✏️ Edit
            </Link>
            <button @click="deletePlan(plan)" class="btn-danger btn-sm">
              🗑️ Delete
            </button>
          </div>
        </div>

        <!-- Features preview -->
        <div class="mt-4 flex flex-wrap gap-1.5">
          <span
            v-for="feature in plan.features?.slice(0, 10)"
            :key="feature.feature_key"
            class="text-[10px] px-2 py-1 bg-gray-800 text-gray-400 rounded-md"
          >
            {{ feature.feature_key }}:
            <span class="text-gray-200 font-semibold">{{ feature.feature_value }}</span>
          </span>
          <span v-if="plan.features?.length > 10" class="text-[10px] px-2 py-1 bg-gray-800 text-gray-500 rounded-md">
            +{{ plan.features.length - 10 }} more
          </span>
        </div>
      </div>

      <div v-if="plans.length === 0" class="card p-10 text-center text-gray-500">
        No plans created yet.
        <Link :href="route('admin.plans.create')" class="text-indigo-400 hover:underline ml-1">Create one →</Link>
      </div>
    </div>
  </AdminLayout>
</template>
