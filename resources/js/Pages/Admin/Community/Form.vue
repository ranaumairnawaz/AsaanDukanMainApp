<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps({
  type: String, // 'announcements', 'events', 'news'
  item: Object
})

const emit = defineEmits(['close'])

const form = useForm({
  title: props.item?.title ?? '',
  body: props.item?.body ?? '',
  summary: props.item?.summary ?? '',
  description: props.item?.description ?? '',
  event_date: props.item?.event_date ? new Date(props.item.event_date).toISOString().slice(0, 16) : '',
  location: props.item?.location ?? '',
  is_published: props.item?.is_published ?? true,
  image: null,
})

const imagePreview = ref(props.item?.image_url ?? null)

function handleImageChange(e) {
  const file = e.target.files[0]
  if (file) {
    form.image = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

function submit() {
  if (props.item) {
    form.post(route('admin.community.update', { type: props.type, id: props.item.id }), {
      onSuccess: () => emit('close'),
      forceFormData: true // Needed for file upload with POST disguised as PUT/PATCH, but we use POST for update
    })
  } else {
    form.post(route('admin.community.store', { type: props.type }), {
      onSuccess: () => emit('close')
    })
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4">
    <div class="bg-[#101526] border border-white/10 rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
      
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-white/10 shrink-0">
        <h2 class="text-xl font-bold font-['Fraunces'] text-white">
          {{ item ? 'Edit' : 'Create' }} <span class="capitalize">{{ type.slice(0, -1) }}</span>
        </h2>
        <button @click="$emit('close')" class="text-white/40 hover:text-white transition-colors">✕</button>
      </div>

      <!-- Form Body -->
      <div class="p-6 overflow-y-auto space-y-6 text-white">
        
        <div>
          <label class="block text-sm font-medium text-white/60 mb-2">Title</label>
          <input v-model="form.title" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#2FA968]" required>
          <div v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</div>
        </div>

        <div v-if="type === 'news'">
          <label class="block text-sm font-medium text-white/60 mb-2">Summary</label>
          <textarea v-model="form.summary" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#2FA968]" required></textarea>
          <div v-if="form.errors.summary" class="text-red-400 text-xs mt-1">{{ form.errors.summary }}</div>
        </div>

        <div v-if="type === 'events'" class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Event Date & Time</label>
            <input v-model="form.event_date" type="datetime-local" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#2FA968]" required>
            <div v-if="form.errors.event_date" class="text-red-400 text-xs mt-1">{{ form.errors.event_date }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-white/60 mb-2">Location</label>
            <input v-model="form.location" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-[#2FA968]">
          </div>
        </div>

        <!-- Rich Text Editor -->
        <div>
          <label class="block text-sm font-medium text-white/60 mb-2">Content</label>
          <div class="bg-white text-black rounded-xl overflow-hidden">
            <QuillEditor
              v-if="type === 'events'"
              v-model:content="form.description"
              contentType="html"
              toolbar="minimal"
              theme="snow"
              class="min-h-[200px]"
            />
            <QuillEditor
              v-else
              v-model:content="form.body"
              contentType="html"
              toolbar="minimal"
              theme="snow"
              class="min-h-[200px]"
            />
          </div>
          <div v-if="form.errors.body || form.errors.description" class="text-red-400 text-xs mt-1">Content is required</div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="block text-sm font-medium text-white/60 mb-2">Featured Image</label>
          <div class="flex items-center gap-4">
            <img v-if="imagePreview" :src="imagePreview" class="w-20 h-20 rounded-xl object-cover border border-white/10" />
            <div v-else class="w-20 h-20 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-2xl">📸</div>
            
            <label class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white rounded-lg cursor-pointer transition-colors border border-white/10">
              Upload Image
              <input type="file" class="hidden" accept="image/*" @change="handleImageChange">
            </label>
          </div>
          <div v-if="form.errors.image" class="text-red-400 text-xs mt-1">{{ form.errors.image }}</div>
        </div>

        <div>
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.is_published" type="checkbox" class="w-5 h-5 rounded border-white/20 bg-black/20 text-[#1F7A4D] focus:ring-[#1F7A4D]">
            <span class="text-sm font-medium">Publish Immediately</span>
          </label>
        </div>

      </div>

      <!-- Footer -->
      <div class="p-6 border-t border-white/10 shrink-0 flex items-center justify-end gap-3">
        <button type="button" @click="$emit('close')" class="px-4 py-2 text-white/60 hover:text-white transition-colors">Cancel</button>
        <button @click="submit" :disabled="form.processing" class="px-6 py-2 bg-[#1F7A4D] hover:bg-[#2FA968] disabled:opacity-50 text-white rounded-xl font-medium transition-colors">
          {{ form.processing ? 'Saving...' : 'Save' }}
        </button>
      </div>

    </div>
  </div>
</template>

<style>
/* Adjust Quill toolbar and editor inside dark theme (though we placed it in a white bg container) */
.ql-toolbar {
  border: none !important;
  border-bottom: 1px solid #ccc !important;
  background-color: #f9fafb;
}
.ql-container {
  border: none !important;
  font-family: inherit !important;
  font-size: 1rem !important;
}
.ql-editor {
  min-height: 200px;
}
</style>
