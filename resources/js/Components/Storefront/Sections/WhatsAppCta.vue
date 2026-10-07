<script setup>
/**
 * section.config: { message: 'Order now on WhatsApp for instant confirmation!' }
 * Reinforces the core "order becomes a WhatsApp message" value prop.
 * No config needed beyond optional custom copy — tenant.whatsapp_number
 * already exists on the tenant record.
 */
import { computed } from 'vue'

const props = defineProps({
  section: Object,
  tenant: Object,
})

const waLink = computed(() => {
  const number = (props.tenant?.whatsapp_number || '').replace(/[^0-9]/g, '')
  const text = encodeURIComponent(props.section.config?.message || `Hi, I'd like to ask about your products.`)
  return number ? `https://wa.me/${number}?text=${text}` : null
})
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
    <div
      class="rounded-2xl p-8 sm:p-10 flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left"
      style="background: color-mix(in srgb, var(--color-accent) 12%, var(--color-surface)); border: 1px solid var(--color-border);"
    >
      <div>
        <h2
          class="text-xl sm:text-2xl font-bold"
          style="font-family: var(--font-heading, inherit); color: var(--color-text);"
        >
          {{ section.title || 'Questions? Chat with us on WhatsApp' }}
        </h2>
        <p v-if="section.subtitle" class="mt-1 text-sm" style="color: var(--color-muted);">
          {{ section.subtitle }}
        </p>
      </div>
      <a
        v-if="waLink"
        :href="waLink"
        target="_blank"
        rel="noopener"
        class="shrink-0 inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold transition-transform hover:scale-[1.02] active:scale-[0.98]"
        style="background: var(--color-accent); color: var(--color-on-accent);"
      >
        Message on WhatsApp
      </a>
    </div>
  </section>
</template>
