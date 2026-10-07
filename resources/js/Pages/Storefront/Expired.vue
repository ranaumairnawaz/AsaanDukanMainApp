<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  tenant: Object,
})
</script>

<template>
  <Head :title="tenant.name + ' - Unavailable'" />
  <div class="min-h-screen bg-[var(--color-surface)] flex flex-col items-center justify-center p-6 text-center">
    <div class="w-24 h-24 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-4xl mb-6 shadow-sm">
      🏪
    </div>
    <h1 class="text-3xl font-bold text-[var(--color-text)] mb-2">Store Unavailable</h1>
    <p class="text-[var(--color-text)] max-w-md text-lg">
      The store <strong>{{ tenant.name }}</strong> is currently unavailable. 
      Please check back later or contact the store owner.
    </p>
    
    <div class="mt-12 text-sm text-[var(--color-text)]">
      Powered by <a href="/" class="hover:text-[var(--color-text)] underline">AssanDukan</a>
    </div>
  </div>
</template>
