<script setup>
/**
 * section.config: { items: [{ question: 'Do you deliver nationwide?', answer: '...' }] }
 */
import { ref, computed } from 'vue'

const props = defineProps({
  section: Object,
})

const items = computed(() => props.section.config?.items ?? [])
const openIndex = ref(null)

function toggle(i) {
  openIndex.value = openIndex.value === i ? null : i
}
</script>

<template>
  <section v-if="items.length" class="max-w-3xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <h2
      class="text-2xl sm:text-3xl font-bold tracking-tight text-center mb-8"
      style="font-family: var(--font-heading, inherit); color: var(--color-text);"
    >
      {{ section.title || 'Frequently Asked Questions' }}
    </h2>

    <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--color-border);">
      <div
        v-for="(item, i) in items"
        :key="i"
        style="background: var(--color-surface);"
        :style="i > 0 ? { borderTop: '1px solid var(--color-border)' } : {}"
      >
        <button
          @click="toggle(i)"
          class="w-full flex items-center justify-between gap-4 p-5 text-left text-sm font-semibold transition-colors focus:outline-none"
          style="color: var(--color-text); background: transparent;"
          @mouseover="$event.currentTarget.style.background = 'color-mix(in srgb, var(--color-text) 5%, transparent)'"
          @mouseleave="$event.currentTarget.style.background = 'transparent'"
        >
          <span>{{ item.question }}</span>
          <svg
            class="w-4 h-4 shrink-0 transition-transform duration-200"
            :class="{ 'rotate-180': openIndex === i }"
            style="color: var(--color-muted);"
            fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div
          v-if="openIndex === i"
          class="px-5 pb-5 text-sm leading-relaxed"
          style="color: var(--color-muted);"
        >
          {{ item.answer }}
        </div>
      </div>
    </div>
  </section>
</template>