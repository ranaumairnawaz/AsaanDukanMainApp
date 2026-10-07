<script setup>
/**
 * section.config: {
 *   image_path: 'banners/3/xyz.png',
 *   cta_text: 'Shop the Sale',
 *   cta_link: '#products',
 *   layout: 'image_right' | 'image_left'
 * }
 */
import { computed } from 'vue'

const props = defineProps({
  section: Object,
  tenant: Object,
})

const config = computed(() => props.section.config ?? {})
const imageLeft = computed(() => config.value.layout === 'image_left')
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <div
      class="rounded-2xl overflow-hidden grid md:grid-cols-2 items-stretch"
      style="background: var(--color-surface); border: 1px solid var(--color-border);"
    >
      <div
        class="p-8 sm:p-12 flex flex-col justify-center gap-4"
        :class="imageLeft ? 'md:order-2' : ''"
      >
        <h2
          class="text-2xl sm:text-3xl font-bold tracking-tight"
          style="font-family: var(--font-heading, inherit); color: var(--color-text);"
        >
          {{ section.title || 'Special Offer' }}
        </h2>
        <p v-if="section.subtitle" class="text-sm sm:text-base" style="color: var(--color-muted);">
          {{ section.subtitle }}
        </p>
        <a
          v-if="config.cta_text"
          :href="config.cta_link || '#products'"
          class="inline-flex w-fit items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold transition-transform hover:scale-[1.02] active:scale-[0.98]"
          style="background: var(--color-accent); color: var(--color-on-accent);"
        >
          {{ config.cta_text }}
        </a>
      </div>
      <div
        class="aspect-video md:aspect-auto min-h-[220px] relative"
        :class="imageLeft ? 'md:order-1' : ''"
        style="background: var(--color-bg);"
      >
        <img
          v-if="config.image_path"
          :src="`/storage/${config.image_path}`"
          :alt="section.title"
          class="w-full h-full object-cover"
        />
        <div v-else class="flex items-center justify-center h-full text-6xl opacity-30">🎉</div>
      </div>
    </div>
  </section>
</template>
