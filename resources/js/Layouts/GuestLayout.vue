<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage()
const pageTitle = computed(() => {
  const parts = page.component.split('/')
  if (parts.length > 1 && parts[parts.length - 1] === 'Index') {
    return parts[parts.length - 2]
  }
  return parts[parts.length - 1]
})
</script>

<template>
    <Head :title="pageTitle">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500..900;1,9..144,500..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center bg-[#141B2E] px-6 pt-6 pb-12 sm:justify-center sm:pt-0 font-['Plus_Jakarta_Sans'] relative overflow-hidden"
    >
        <!-- Ambient glow, consistent with landing/login -->
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none" style="background: #1F7A4D;"></div>
        <div class="absolute -bottom-32 -right-16 w-[32rem] h-[32rem] rounded-full opacity-15 blur-3xl pointer-events-none" style="background: #E8A23D;"></div>

        <div class="relative z-10">
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 fill-current text-[#1F7A4D]" />
            </Link>
        </div>

        <div
            class="relative z-10 mt-6 w-full overflow-hidden bg-white/5 border border-white/10 backdrop-blur px-6 py-6 shadow-2xl sm:max-w-md sm:rounded-2xl"
        >
            <slot />
        </div>
    </div>
</template>