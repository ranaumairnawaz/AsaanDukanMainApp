<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  plans: Array,
})

const form = useForm({
  status: props.tenant.status,
  subscription_plan_id: props.tenant.subscription_plan_id,
  subscription_expires_at: props.tenant.subscription_expires_at 
    ? props.tenant.subscription_expires_at.split('T')[0] 
    : '',
})

function submit() {
  form.put(route('admin.tenants.update', props.tenant))
}

function suspend() {
  if (confirm(`Suspend ${props.tenant.name}? They will lose storefront and dashboard access.`)) {
    useForm({}).post(route('admin.tenants.suspend', props.tenant))
  }
}

function activate() {
  if (confirm(`Activate ${props.tenant.name}?`)) {
    useForm({}).post(route('admin.tenants.activate', props.tenant))
  }
}

const passwordForm = useForm({
  new_password: '',
  new_password_confirmation: ''
})

function changePassword() {
  if (confirm(`Change password for owner of ${props.tenant.name}?`)) {
    passwordForm.post(route('admin.tenants.change_password', props.tenant), {
      preserveScroll: true,
      onSuccess: () => passwordForm.reset()
    })
  }
}

const owner = props.tenant.users?.[0];

function toggleBlock() {
  if (owner?.is_blocked) {
    if (confirm(`Unblock owner of ${props.tenant.name}?`)) {
      useForm({}).post(route('admin.tenants.unblock_owner', props.tenant))
    }
  } else {
    if (confirm(`Block owner of ${props.tenant.name}? They will lose login access and store will suspend.`)) {
      useForm({}).post(route('admin.tenants.block_owner', props.tenant))
    }
  }
}

const giftPlanForm = useForm({
  plan_id: '',
  status: 'active',
  expires_at: ''
})

function submitGiftPlan() {
  if (confirm(`Gift plan to ${props.tenant.name}?`)) {
    giftPlanForm.post(route('admin.tenants.gift_plan', props.tenant), {
      preserveScroll: true,
      onSuccess: () => {
        form.subscription_plan_id = giftPlanForm.plan_id;
        form.status = giftPlanForm.status;
        form.subscription_expires_at = giftPlanForm.expires_at;
      }
    })
  }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('admin.tenants.index')" class="text-gray-400 hover:text-white text-sm shrink-0">← Back</Link>
        <h1 class="text-base font-semibold text-white truncate">Manage: {{ tenant.name }}</h1>
      </div>
    </template>

    <div class="grid md:grid-cols-3 gap-5 sm:gap-6">
      <!-- Tenant Overview -->
      <div class="md:col-span-2 space-y-5 sm:space-y-6">
        <!-- Settings Form -->
        <div class="card p-4 sm:p-5 space-y-4">
          <h2 class="text-sm font-bold text-white">Subscription & Status</h2>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="form-label">Status</label>
              <select v-model="form.status" class="form-input">
                <option value="trial">Trial</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>

            <div>
              <label class="form-label">Subscription Plan</label>
              <select v-model="form.subscription_plan_id" class="form-input">
                <option :value="null">No active plan</option>
                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                  {{ plan.name }} (Rs. {{ Number(plan.price_monthly).toLocaleString() }}/mo)
                </option>
              </select>
            </div>

            <div>
              <label class="form-label">Subscription Expires At</label>
              <input v-model="form.subscription_expires_at" type="date" class="form-input" />
            </div>

            <div class="pt-2">
              <button type="submit" :disabled="form.processing" class="btn-primary w-full sm:w-auto px-6">
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Store Information -->
        <div class="card p-4 sm:p-5 space-y-3">
          <h2 class="text-sm font-bold text-white">Store Metadata</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-400">
            <div>
              <span class="text-xs text-gray-500 block mb-0.5">Slug / Path</span>
              <span class="font-mono text-white text-xs break-all">/store/{{ tenant.store_slug }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block mb-0.5">Subdomain</span>
              <span class="text-white font-mono text-xs break-all">
                {{ tenant.subdomain ? tenant.subdomain + '.AssanDukan.online' : 'None' }} 
                <span class="text-gray-500 ml-1">({{ tenant.subdomain_status }})</span>
              </span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block mb-0.5">Category</span>
              <span class="text-white">{{ tenant.category ?? '—' }}</span>
            </div>
            <div>
              <span class="text-xs text-gray-500 block mb-0.5">Custom Domain</span>
              <span class="text-white font-mono text-xs break-all">{{ tenant.custom_domain ?? 'None' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions / Quick Panel -->
      <div class="space-y-5 sm:space-y-6">
        <div class="card p-4 sm:p-5 space-y-4">
          <h2 class="text-sm font-bold text-white">Administrative Actions</h2>

          <div class="space-y-2">
            <!-- Gift Plan -->
            <div class="bg-[#141B2E] p-3 rounded-lg border border-white/5 space-y-3 mb-4">
              <h3 class="text-xs font-semibold text-white/70">🎁 Gift Plan</h3>
              <form @submit.prevent="submitGiftPlan" class="space-y-2">
                <select v-model="giftPlanForm.plan_id" class="form-input py-1.5 text-xs">
                  <option value="" disabled>Select Plan</option>
                  <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
                <input v-model="giftPlanForm.expires_at" type="date" required class="form-input py-1.5 text-xs" />
                <button type="submit" :disabled="giftPlanForm.processing" class="btn-primary w-full py-1.5 text-xs">Gift Plan</button>
              </form>
            </div>

            <!-- Password Reset -->
            <div class="bg-[#141B2E] p-3 rounded-lg border border-white/5 space-y-3 mb-4">
              <h3 class="text-xs font-semibold text-white/70">🔑 Reset Owner Password</h3>
              <form @submit.prevent="changePassword" class="space-y-2">
                <input v-model="passwordForm.new_password" type="password" placeholder="New Password" required minlength="6" class="form-input py-1.5 text-xs" />
                <input v-model="passwordForm.new_password_confirmation" type="password" placeholder="Confirm Password" required minlength="6" class="form-input py-1.5 text-xs" />
                <button type="submit" :disabled="passwordForm.processing" class="w-full px-4 py-1.5 bg-gray-500/10 border border-gray-500/20 hover:border-gray-500/40 text-gray-300 rounded-lg text-xs hover:bg-gray-500/20 transition-all font-medium">Update Password</button>
              </form>
            </div>

            <button
              v-if="owner"
              @click="toggleBlock"
              class="w-full px-4 py-2 border rounded-lg text-sm transition-all text-left font-medium"
              :class="owner.is_blocked ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20' : 'bg-orange-500/10 border-orange-500/20 text-orange-400 hover:bg-orange-500/20'"
            >
              {{ owner.is_blocked ? '🔓 Unblock User' : '🛑 Block User' }}
            </button>

            <button
              v-if="tenant.status !== 'suspended'"
              @click="suspend"
              class="w-full px-4 py-2 bg-red-500/10 border border-red-500/20 hover:border-red-500/40 text-red-400 rounded-lg text-sm hover:bg-red-500/20 transition-all text-left font-medium"
            >
              🚫 Suspend Store
            </button>
            <button
              v-else
              @click="activate"
              class="w-full px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 hover:border-emerald-500/40 text-emerald-400 rounded-lg text-sm hover:bg-emerald-500/20 transition-all text-left font-medium"
            >
              ✅ Activate Store
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
