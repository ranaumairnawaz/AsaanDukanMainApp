<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({ type: String, required: true });

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-lg border border-white/10 bg-white/5 text-white text-sm placeholder-white/25 shadow-sm transition-all focus:border-[#1F7A4D] focus:ring-2 focus:ring-[#1F7A4D] focus:outline-none px-4 py-3"
        v-model="model"
        ref="input"
    />
</template>