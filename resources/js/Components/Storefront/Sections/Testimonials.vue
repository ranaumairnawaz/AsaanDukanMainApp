<script setup>
/**
 * section.config: {
 *   items: [
 *     { name: 'Ayesha K.', text: 'Loved the quality!', rating: 5, location: 'Lahore' },
 *     ...
 *   ]
 * }
 */
import { computed } from 'vue'

const props = defineProps({
  section: Object,
})

const items = computed(() => props.section.config?.items ?? [])
</script>

<template>
  <section v-if="items.length" class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <h2
      class="text-2xl sm:text-3xl font-bold tracking-tight text-center mb-10"
      style="font-family: var(--font-heading, inherit); color: var(--color-text);"
    >
      {{ section.title || 'What Our Customers Say' }}
    </h2>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="(item, i) in items"
        :key="i"
        class="rounded-xl p-6 flex flex-col gap-3"
        style="background: var(--color-surface); border: 1px solid var(--color-border);"
      >
        <div v-if="item.rating" class="flex gap-0.5" style="color: var(--color-accent);">
          <svg v-for="n in 5" :key="n" class="w-4 h-4" :fill="n <= item.rating ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 20 20">
            <path stroke-width="1.5" d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1 1 5.8L10 15.15l-5.21 2.74 1-5.8-4.21-4.1 5.82-.85L10 1.5z" />
          </svg>
        </div>
        <p class="text-sm leading-relaxed" style="color: var(--color-text);">"{{ item.text }}"</p>
        <div class="mt-auto pt-2 text-xs font-semibold" style="color: var(--color-muted);">
          {{ item.name }}<span v-if="item.location"> · {{ item.location }}</span>
        </div>
      </div>
    </div>
  </section>
</template>
