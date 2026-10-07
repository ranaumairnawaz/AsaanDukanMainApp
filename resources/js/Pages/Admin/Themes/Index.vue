<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  themes: Array,
})

const showModal = ref(false)
const editingTheme = ref(null)

const form = useForm({
  name: '',
  slug: '',
  demo_url: '',
  is_active: true,
  is_premium: true,
  screenshot: null,
})

function openModal(theme = null) {
  editingTheme.value = theme
  if (theme) {
    form.name = theme.name
    form.slug = theme.slug
    form.demo_url = theme.demo_url ?? ''
    form.is_active = theme.is_active
    form.is_premium = theme.is_premium
    form.screenshot = null
  } else {
    form.reset()
    form.is_active = true
    form.is_premium = true
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  form.reset()
  form.clearErrors()
}

function submit() {
  if (editingTheme.value) {
    // We use POST with _method=PUT to handle file uploads
    form.transform((data) => ({
      ...data,
      _method: 'put',
    })).post(route('admin.themes.update', editingTheme.value.id), {
      onSuccess: closeModal,
      preserveScroll: true,
    })
  } else {
    form.post(route('admin.themes.store'), {
      onSuccess: closeModal,
      preserveScroll: true,
    })
  }
}

function deleteTheme(id) {
  if (confirm('Are you sure you want to delete this theme? It may break merchants using it.')) {
    router.delete(route('admin.themes.destroy', id))
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h2 class="page-title">Theme Registry</h2>
          <p class="page-subtitle">Manage themes available to merchants.</p>
        </div>
        <button @click="openModal()" class="btn-primary shrink-0">
          + Add Theme
        </button>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div v-for="theme in themes" :key="theme.id" class="card overflow-hidden flex flex-col group">
          <div class="aspect-video bg-gray-900 border-b border-white/5 relative">
            <img v-if="theme.screenshot_path" :src="`/storage/${theme.screenshot_path}`" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-500 text-sm">No Screenshot</div>
            
            <div class="absolute top-3 right-3 flex gap-2">
              <span v-if="theme.is_active" class="badge bg-emerald-500/90 text-white backdrop-blur-md shadow-lg border-0 font-bold uppercase tracking-wider">Active</span>
              <span v-else class="badge bg-gray-500/90 text-white backdrop-blur-md shadow-lg border-0 font-bold uppercase tracking-wider">Hidden</span>
            </div>
            <div class="absolute top-3 left-3">
              <span v-if="theme.is_premium" class="badge bg-amber-500/90 text-white backdrop-blur-md shadow-lg border-0 font-bold uppercase tracking-wider">Premium</span>
              <span v-else class="badge bg-sky-500/90 text-white backdrop-blur-md shadow-lg border-0 font-bold uppercase tracking-wider">Free</span>
            </div>
          </div>
          
          <div class="p-4 sm:p-5 flex-1 flex flex-col">
            <h3 class="font-bold text-white text-lg">{{ theme.name }}</h3>
            <p class="text-xs text-gray-500 font-mono mt-0.5">slug: {{ theme.slug }}</p>
            
            <div class="mt-4 flex flex-wrap gap-2">
              <a v-if="theme.demo_url" :href="theme.demo_url" target="_blank" class="btn-secondary btn-sm">
                View Demo ↗
              </a>
            </div>
            
            <div class="mt-auto pt-4 sm:pt-5 flex items-center justify-between border-t border-white/5">
              <button @click="openModal(theme)" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">Edit Settings</button>
              <button @click="deleteTheme(theme.id)" class="text-xs text-gray-600 hover:text-red-400 transition-colors">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
      <div class="card p-6 w-full max-w-md shadow-2xl">
        <h3 class="font-bold text-white text-lg mb-1">{{ editingTheme ? 'Edit Theme' : 'Add Theme' }}</h3>
        
        <form @submit.prevent="submit" class="space-y-4 mt-6">
          <div>
            <label class="form-label">Theme Name</label>
            <input v-model="form.name" type="text" required class="form-input" />
            <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
          </div>
          
          <div>
            <label class="form-label">Directory Slug</label>
            <input v-model="form.slug" type="text" required class="form-input font-mono" placeholder="e.g. fashion" />
            <p class="text-[10px] text-gray-500 mt-1">Must match the Blade/Vue view directory name.</p>
            <p v-if="form.errors.slug" class="form-error">{{ form.errors.slug }}</p>
          </div>

          <div>
            <label class="form-label">Demo Store URL</label>
            <input v-model="form.demo_url" type="text" placeholder="https://" class="form-input" />
          </div>

          <div>
            <label class="form-label">Screenshot <span class="font-normal text-gray-500">(Optional)</span></label>
            <input type="file" @input="form.screenshot = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20" />
            <p v-if="form.errors.screenshot" class="form-error">{{ form.errors.screenshot }}</p>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 accent-emerald-500" />
            <label for="is_active" class="text-sm text-gray-300 cursor-pointer">Active (Visible to merchants & public)</label>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="form.is_premium" type="checkbox" id="is_premium" class="w-4 h-4 accent-amber-500" />
            <label for="is_premium" class="text-sm text-gray-300 cursor-pointer">
              Premium (requires plan upgrade or paid unlock to switch to)
            </label>
          </div>
          <p class="text-[10px] text-gray-500 -mt-2">
            Uncheck for simple/free themes like "Basic" that every plan can use at no extra cost.
          </p>

          <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <button type="button" @click="closeModal" class="btn-secondary flex-1 py-2.5">Cancel</button>
            <button type="submit" :disabled="form.processing" class="btn-primary flex-1 py-2.5">
              {{ form.processing ? 'Saving...' : 'Save Theme' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
