<script setup>
defineProps({
    type: { type: String, default: 'submit' },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-transparent bg-[#1F7A4D] px-5 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-[0_3px_0_0_#0F4A2C] transition-all hover:bg-[#2C9660] hover:shadow-[0_1px_0_0_#0F4A2C] hover:translate-y-[2px] focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:ring-offset-2 focus:ring-offset-[#141B2E] disabled:opacity-40 disabled:hover:translate-y-0 disabled:hover:shadow-[0_3px_0_0_#0F4A2C]"
    >
        <slot />
    </button>
</template>