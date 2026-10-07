<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FormModal from './Form.vue'

const props = defineProps({
  announcements: Array,
  events: Array,
  news: Array,
})

const activeTab = ref('announcements')

const currentItems = computed(() => {
  if (activeTab.value === 'announcements') return props.announcements;
  if (activeTab.value === 'events') return props.events;
  if (activeTab.value === 'news') return props.news;
  return [];
})

const isFormOpen = ref(false)
const selectedItem = ref(null)

function openCreate() {
  selectedItem.value = null
  isFormOpen.value = true
}

function openEdit(item) {
  selectedItem.value = item
  isFormOpen.value = true
}

function deleteItem(item) {
  if (confirm('Are you sure you want to delete this item?')) {
    router.delete(route('admin.community.destroy', { type: activeTab.value, id: item.id }))
  }
}

function togglePublish(item) {
  router.post(route('admin.community.togglePublish', { type: activeTab.value, id: item.id }))
}

function closeForm() {
  isFormOpen.value = false
  selectedItem.value = null
}
</script>

<template>
  <AdminLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold font-['Fraunces']">Community Content</h1>
        <button @click="openCreate" class="px-4 py-2 bg-[#1F7A4D] hover:bg-[#2FA968] text-white rounded-xl font-medium transition-colors">
          + Create New
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex gap-4 mb-6 border-b border-white/10 pb-2">
        <button 
          v-for="tab in ['announcements', 'events', 'news']" 
          :key="tab"
          @click="activeTab = tab"
          class="capitalize font-medium px-2 py-1 rounded-md transition-colors"
          :class="activeTab === tab ? 'text-white bg-white/10' : 'text-white/40 hover:text-white'"
        >
          {{ tab }}
        </button>
      </div>

      <div class="bg-[#101526] border border-white/10 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-black/20 text-white/40 font-medium">
              <tr>
                <th class="px-6 py-4">Title</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Author</th>
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="currentItems.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-white/40">No items found.</td>
              </tr>
              <tr v-for="item in currentItems" :key="item.id" class="hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 font-bold text-white flex items-center gap-3">
                  <img v-if="item.image_url" :src="item.image_url" class="w-10 h-10 rounded object-cover" />
                  {{ item.title }}
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded-lg text-xs font-medium" :class="item.is_published ? 'bg-[#1F7A4D]/20 text-[#2FA968]' : 'bg-[#C4432E]/20 text-[#FF8A75]'">
                    {{ item.is_published ? 'Published' : 'Draft' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-white/60">
                  {{ item.author?.name || 'Admin' }}
                </td>
                <td class="px-6 py-4 text-white/60">
                  {{ new Date(item.created_at).toLocaleDateString() }}
                </td>
                <td class="px-6 py-4 text-right space-x-3">
                  <button @click="togglePublish(item)" class="text-white/40 hover:text-white transition-colors">
                    {{ item.is_published ? 'Unpublish' : 'Publish' }}
                  </button>
                  <button @click="openEdit(item)" class="text-[#2FA968] hover:text-[#1F7A4D] transition-colors">Edit</button>
                  <button @click="deleteItem(item)" class="text-[#FF8A75] hover:text-[#C4432E] transition-colors">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <FormModal 
      v-if="isFormOpen" 
      :type="activeTab" 
      :item="selectedItem" 
      @close="closeForm" 
    />
  </AdminLayout>
</template>
