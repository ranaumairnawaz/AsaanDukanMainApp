<script setup>
defineProps(['message']);
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-[#FF8A75] font-['IBM_Plex_Mono']">{{ message }}</p>
    </div>
</template>