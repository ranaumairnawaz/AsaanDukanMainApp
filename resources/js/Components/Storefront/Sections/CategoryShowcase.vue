<script setup>
/**
 * section.config: {
 *   items: [
 *     { label: 'Skincare', category: 'SERUM', image_path: 'uploads/skincare.png' },
 *     ...
 *   ]
 * }
 * `category` must match the string stored in products.category so the link
 * can filter the catalog (e.g. `${tenant.store_url}?category=SERUM`).
 */
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  section: Object,
  tenant: Object,
})

const items = computed(() => props.section.config?.items ?? [])
</script>

<template>
  <section v-if="items.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <h2
      class="text-2xl sm:text-3xl font-bold tracking-tight mb-8"
      style="font-family: var(--font-heading, inherit); color: var(--color-text);"
    >
      {{ section.title || 'Shop by Category' }}
    </h2>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
      <Link
        v-for="(item, i) in items"
        :key="i"
        :href="`${tenant.store_url}?category=${encodeURIComponent(item.category)}`"
        class="group relative rounded-xl overflow-hidden aspect-[4/5] flex items-end transition-transform hover:-translate-y-0.5"
        style="background: var(--color-surface); border: 1px solid var(--color-border);"
      >
        <img
          v-if="item.image_path"
          :src="`/storage/${item.image_path}`"
          :alt="item.label"
          class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform"
        />
        <div
          class="absolute inset-0"
          style="background: linear-gradient(to top, color-mix(in srgb, var(--color-bg) 80%, transparent), transparent 60%);"
        />
        <span
          class="relative z-10 p-4 text-sm sm:text-base font-semibold"
          style="color: var(--color-text);"
        >
          {{ item.label }}
        </span>
      </Link>
    </div>
  </section>
</template>
