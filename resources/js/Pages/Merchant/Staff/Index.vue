<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  staff: Array,
})

const showAddModal = ref(false)

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  tenant_role: 'staff',
})

function submit() {
  form.post(route('merchant.staff.store'), {
    onSuccess: () => {
      showAddModal.value = false
      form.reset()
    }
  })
}

function removeStaff(id) {
  if (confirm('Are you sure you want to remove this staff member?')) {
    router.delete(route('merchant.staff.destroy', id))
  }
}
</script>

<template>
  <MerchantLayout>
    <div class="space-y-4 sm:space-y-6">
      
      <div class="page-header flex-col sm:flex-row gap-4 items-start sm:items-center">
        <div>
          <h1 class="page-title">Staff Management</h1>
          <p class="page-subtitle">Manage who has access to your store dashboard.</p>
        </div>
        
        <button v-if="$page.props.auth.user.tenant_role === 'owner'" @click="showAddModal = true" class="btn-primary w-full sm:w-auto shrink-0">
          + Add Staff
        </button>
      </div>

      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="data-table min-w-[500px]">
            <thead>
              <tr>
                <th>User</th>
                <th>Contact</th>
                <th>Role</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Owner -->
              <tr>
                <td>
                  <div class="font-bold text-white">{{ $page.props.auth.user.name }} <span class="text-emerald-400 font-normal ml-1">(You)</span></div>
                </td>
                <td>
                  <div class="text-gray-400 text-[10px] sm:text-xs font-mono">{{ $page.props.auth.user.email }}</div>
                </td>
                <td>
                  <span class="badge border border-emerald-500/20 bg-emerald-500/15 text-emerald-400 uppercase tracking-wider font-bold">
                    Owner
                  </span>
                </td>
                <td class="text-right"></td>
              </tr>

              <!-- Staff -->
              <tr v-for="user in staff" :key="user.id">
                <td>
                  <div class="font-bold text-white">{{ user.name }}</div>
                </td>
                <td>
                  <div class="text-gray-400 text-[10px] sm:text-xs font-mono">{{ user.email }}</div>
                  <div class="text-gray-500 text-[10px] sm:text-xs">{{ user.phone }}</div>
                </td>
                <td>
                  <span class="badge border uppercase tracking-wider font-bold"
                    :class="user.tenant_role === 'manager' ? 'bg-blue-500/15 text-blue-400 border-blue-500/20' : 'bg-gray-500/15 text-gray-400 border-gray-500/20'">
                    {{ user.tenant_role }}
                  </span>
                </td>
                <td class="text-right">
                  <button v-if="$page.props.auth.user.tenant_role === 'owner'" @click="removeStaff(user.id)" class="btn-danger btn-sm">
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
    </div>

    <!-- Add Staff Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
      <div class="card p-5 sm:p-6 w-full max-w-md shadow-2xl">
        <h3 class="font-bold text-white text-lg mb-1">Add Staff Member</h3>
        <p class="text-xs text-gray-400 mb-6">Create an account for a team member.</p>
        
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="form-label">Name</label>
            <input v-model="form.name" type="text" required class="form-input" />
            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
          </div>
          
          <div>
            <label class="form-label">Email Address (Login ID)</label>
            <input v-model="form.email" type="email" required class="form-input" />
            <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="form-label">Password</label>
            <input v-model="form.password" type="text" required minlength="8" class="form-input" />
            <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
          </div>
          
          <div>
            <label class="form-label">Role</label>
            <select v-model="form.tenant_role" class="form-input">
              <option value="manager">Manager (Can manage products & orders)</option>
              <option value="staff">Staff (Can only manage orders)</option>
            </select>
          </div>

          <div class="flex gap-3 pt-4 border-t border-white/5">
            <button type="button" @click="showAddModal = false" class="btn-secondary flex-1 py-3">Cancel</button>
            <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-3">
              {{ form.processing ? 'Saving...' : 'Add Member' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </MerchantLayout>
</template>
