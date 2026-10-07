<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  sections: Array,
})

const page = usePage()
const themeSlug = computed(() => page.props.auth?.tenant?.theme_slug)
const isBasicTheme = computed(() => themeSlug.value === 'basic')

const list = ref([...props.sections])
const addMenuOpen = ref(false)

const typeMeta = {
  featured_products:  { icon: '⭐', label: 'Featured Products' },
  new_arrivals:       { icon: '🆕', label: 'New Arrivals' },
  promo_banner:       { icon: '🖼️', label: 'Promo Banner' },
  testimonials:       { icon: '💬', label: 'Testimonials' },
  category_showcase:  { icon: '🗂️', label: 'Category Showcase' },
  whatsapp_cta:       { icon: '📱', label: 'WhatsApp CTA' },
  faq:                { icon: '❓', label: 'FAQ' },
}

function meta(type) {
  return typeMeta[type] ?? { icon: '📦', label: type }
}

function move(index, dir) {
  const target = index + dir
  if (target < 0 || target >= list.value.length) return
  const copy = [...list.value]
  ;[copy[index], copy[target]] = [copy[target], copy[index]]
  list.value = copy
  persistOrder()
}

function persistOrder() {
  router.post(route('merchant.sections.reorder'), {
    ids: list.value.map(s => s.id),
  }, { preserveScroll: true, preserveState: true })
}

function toggle(section) {
  useForm({}).post(route('merchant.sections.toggle', section.id), { preserveScroll: true })
}

function destroy(section) {
  if (confirm(`Remove the "${meta(section.type).label}" section? This cannot be undone.`)) {
    useForm({}).delete(route('merchant.sections.destroy', section.id), { preserveScroll: true })
  }
}

const availableTypes = Object.keys(typeMeta)
</script>

<template>
  <MerchantLayout>
    <template #header>
      <div class="flex items-center justify-between w-full gap-3">
        <h1 class="text-base font-semibold text-white truncate">Storefront Sections</h1>
        <div class="relative shrink-0">
          <button @click="addMenuOpen = !addMenuOpen" class="btn-primary btn-sm">+ Add Section</button>
          <div v-if="addMenuOpen" class="absolute right-0 mt-2 w-56 bg-[#111] border border-white/10 rounded-xl shadow-xl z-20 overflow-hidden">
            <Link v-for="type in availableTypes" :key="type"
              :href="route('merchant.sections.create', { type })"
              class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-300 hover:bg-white/5 hover:text-white transition-colors">
              <span>{{ meta(type).icon }}</span> {{ meta(type).label }}
            </Link>
          </div>
        </div>
      </div>
    </template>

    <p class="text-sm text-gray-500 mb-5">
      These blocks render on your storefront home page, in this order, below your product grid.
      Use the arrows to reorder, the switch to hide a block without deleting it.
    </p>

    <!-- Basic Theme Warning -->
    <div v-if="isBasicTheme" class="bg-yellow-500/10 border border-yellow-500/25 rounded-xl p-4 mb-6 flex items-start gap-3">
      <span class="text-yellow-500 text-xl leading-none">⚠</span>
      <div>
        <p class="text-sm text-yellow-300 font-semibold mb-1">Your current theme does not support sections.</p>
        <p class="text-xs text-yellow-500/80">You are currently using the "Basic" theme. To use storefront sections, please go to Settings > Theme and switch to any other theme (e.g., Fashion, Decor, etc.). Any sections you add here will be saved but won't be visible on your storefront until you switch themes.</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="list.length === 0" class="card p-10 sm:p-14 text-center text-gray-500">
      <div class="text-5xl mb-4">🧩</div>
      <p class="text-white font-semibold mb-1">No sections yet</p>
      <p class="text-sm mb-5">Add a promo banner, testimonials, or FAQ block to your storefront.</p>
      <button @click="addMenuOpen = true" class="btn-primary">+ Add Your First Section</button>
    </div>

    <div v-else class="space-y-3">
      <div v-for="(section, i) in list" :key="section.id"
        class="card p-4 flex items-center gap-4"
        :class="{ 'opacity-50': !section.is_active }">

        <!-- Reorder -->
        <div class="flex flex-col gap-1 shrink-0">
          <button @click="move(i, -1)" :disabled="i === 0"
            class="w-6 h-6 rounded-md bg-white/5 hover:bg-white/10 disabled:opacity-20 disabled:cursor-not-allowed text-gray-400 text-xs flex items-center justify-center">▲</button>
          <button @click="move(i, 1)" :disabled="i === list.length - 1"
            class="w-6 h-6 rounded-md bg-white/5 hover:bg-white/10 disabled:opacity-20 disabled:cursor-not-allowed text-gray-400 text-xs flex items-center justify-center">▼</button>
        </div>

        <div class="w-10 h-10 shrink-0 rounded-xl bg-white/5 flex items-center justify-center text-xl">
          {{ meta(section.type).icon }}
        </div>

        <div class="flex-1 min-w-0">
          <div class="font-semibold text-white text-sm truncate">
            {{ section.title || meta(section.type).label }}
          </div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ meta(section.type).label }}
            <span v-if="section.type === 'featured_products'"> · {{ section.products_count }} product{{ section.products_count === 1 ? '' : 's' }}</span>
          </div>
        </div>

        <!-- Active toggle -->
        <button @click="toggle(section)" class="shrink-0 w-11 h-6 rounded-full transition-colors relative"
          :class="section.is_active ? 'bg-emerald-500' : 'bg-white/10'">
          <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all"
            :class="section.is_active ? 'left-5' : 'left-0.5'"></span>
        </button>

        <div class="flex gap-2 shrink-0">
          <Link :href="route('merchant.sections.edit', section.id)"
            class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-gray-300 rounded-lg text-xs transition-all">
            ✏️ Edit
          </Link>
          <button @click="destroy(section)"
            class="px-2.5 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs transition-all">
            🗑
          </button>
        </div>
      </div>
    </div>
  </MerchantLayout>
</template>
