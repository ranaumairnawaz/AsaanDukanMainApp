<script setup>
/**
 * Drop this into any theme's Home.vue (electronics, decor, cosmetics, fashion...)
 * right after the existing hero/category/product markup:
 *
 *   <SectionRenderer :sections="sections" :tenant="tenant" />
 *
 * `sections` comes straight from the Inertia page props (see README for the
 * controller-side eager load). Every section component in this folder is
 * built using ONLY the shared CSS variables set on <html> by StorefrontLayout
 * (--color-bg, --color-surface, --color-text, --color-accent, --color-on-accent,
 * --color-muted, --color-border, --font-heading, --font-body) — never a
 * theme-specific Tailwind color class. That's what makes a tenant-added
 * section automatically match whichever theme + accent color the tenant
 * picked, with zero per-theme code.
 */
import { computed } from 'vue'
import FeaturedProducts from './FeaturedProducts.vue'
import PromoBanner from './PromoBanner.vue'
import Testimonials from './Testimonials.vue'
import CategoryShowcase from './CategoryShowcase.vue'
import WhatsAppCta from './WhatsAppCta.vue'
import FaqAccordion from './FaqAccordion.vue'

const props = defineProps({
  sections: { type: Array, default: () => [] },
  tenant: Object,
})

const componentMap = {
  featured_products: FeaturedProducts,
  new_arrivals: FeaturedProducts, // same component, config.mode differs
  promo_banner: PromoBanner,
  testimonials: Testimonials,
  category_showcase: CategoryShowcase,
  whatsapp_cta: WhatsAppCta,
  faq: FaqAccordion,
}

const visibleSections = computed(() =>
  props.sections
    .filter(s => s.is_active)
    .sort((a, b) => a.sort_order - b.sort_order)
)

function resolveComponent(type) {
  return componentMap[type] ?? null
}
</script>

<template>
  <template v-for="section in visibleSections" :key="section.id">
    <component
      :is="resolveComponent(section.type)"
      v-if="resolveComponent(section.type)"
      :section="section"
      :tenant="tenant"
    />
  </template>
</template>
