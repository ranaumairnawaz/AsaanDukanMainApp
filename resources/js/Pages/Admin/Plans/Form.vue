<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  plan: Object,
  feature_registry: Object,
  feature_values: Object,
})

const isEditing = computed(() => !!props.plan)

const form = useForm({
  name: props.plan?.name ?? '',
  slug: props.plan?.slug ?? '',
  price_monthly: props.plan?.price_monthly ?? '',
  price_yearly: props.plan?.price_yearly ?? '',
  sort_order: props.plan?.sort_order ?? 0,
  is_active: props.plan?.is_active ?? true,
  features: Object.fromEntries(
    Object.entries(props.feature_registry ?? {}).map(([key, def]) => {
      const raw = props.feature_values?.[key] ?? String(def.default)
      const val = def.type === 'boolean' ? (raw === '1' || raw === 'true' || raw === true) : raw
      return [key, val]
    })
  ),
})

function submit() {
  if (isEditing.value) {
    form.put(route('admin.plans.update', props.plan))
  } else {
    form.post(route('admin.plans.store'))
  }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('admin.plans.index')" class="text-gray-400 hover:text-white text-sm">← Back</Link>
        <h1 class="text-lg font-semibold text-white">{{ isEditing ? 'Edit Plan' : 'New Plan' }}</h1>
      </div>
    </template>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <!-- Basic Info -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <h2 class="text-sm font-semibold text-gray-300">Plan Details</h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Plan Name</label>
            <input v-model="form.name" type="text" placeholder="Growth Plan"
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Slug</label>
            <input v-model="form.slug" type="text" placeholder="growth" :disabled="isEditing"
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:opacity-50" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Monthly Price (Rs.)</label>
            <input v-model="form.price_monthly" type="number" min="0"
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Yearly Price (Rs.)</label>
            <input v-model="form.price_yearly" type="number" min="0"
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Sort Order</label>
            <input v-model="form.sort_order" type="number" min="0"
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-emerald-500" />
          </div>
          <div class="flex items-center gap-2 pt-5">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="accent-emerald-500" />
            <label for="is_active" class="text-sm text-gray-300">Plan is active (visible to merchants)</label>
          </div>
        </div>
      </div>

      <!-- Feature Registry -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <h2 class="text-sm font-semibold text-gray-300">Plan Features</h2>
        <p class="text-xs text-gray-500">All registered features are shown automatically. Toggle or set values per plan.</p>

        <div class="space-y-3">
          <div
            v-for="(def, key) in feature_registry"
            :key="key"
            class="flex items-center justify-between py-2 border-b border-gray-800 last:border-0"
          >
            <div>
              <div class="text-sm text-gray-200">{{ def.label }}</div>
              <div class="text-xs text-gray-500">{{ key }} · {{ def.type }}</div>
            </div>
            <div>
              <!-- Boolean feature -->
              <input v-if="def.type === 'boolean'"
                v-model="form.features[key]"
                type="checkbox"
                class="accent-emerald-500 w-4 h-4" />
              <!-- Numeric/decimal feature -->
              <input v-else
                v-model="form.features[key]"
                type="number"
                min="0"
                step="0.01"
                class="w-28 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-sm text-white text-right focus:outline-none focus:ring-1 focus:ring-emerald-500" />
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-3">
        <button type="submit" :disabled="form.processing"
          class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition-all disabled:opacity-50">
          {{ isEditing ? 'Save Changes' : 'Create Plan' }}
        </button>
        <Link :href="route('admin.plans.index')"
          class="px-6 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition-all">
          Cancel
        </Link>
      </div>
    </form>
  </AdminLayout>
</template>
