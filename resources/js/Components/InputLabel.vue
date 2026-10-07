<script setup>
defineProps(['value']);
</script>

<template>
    <label class="block text-sm font-medium text-white/70 mb-1.5">
        <slot v-if="$slots.default" />
        <template v-else>{{ value }}</template>
    </label>
</template>