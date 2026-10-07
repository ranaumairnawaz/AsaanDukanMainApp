<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({ status: String })

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Admin Portal — AssanDukan">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500..900;1,9..144,500..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen flex items-center justify-center px-6 relative overflow-hidden bg-[#0F1424] font-['Plus_Jakarta_Sans']">

        <!-- Subtle grid background, restrained -->
        <div class="absolute inset-0 opacity-[0.05] pointer-events-none"
            style="background-image: linear-gradient(#C4432E 1px, transparent 1px), linear-gradient(90deg, #C4432E 1px, transparent 1px); background-size: 40px 40px;"></div>

        <!-- Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px] rounded-full blur-3xl opacity-10 pointer-events-none"
            style="background: radial-gradient(circle, #C4432E, transparent);"></div>

        <div class="relative z-10 w-full max-w-sm space-y-8">

            <!-- Header -->
            <div class="text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-xl flex items-center justify-center text-2xl border border-[#C4432E]/40 bg-[#C4432E]/10">
                    🔐
                </div>
                <h1 class="font-['Fraunces'] text-2xl font-semibold text-white tracking-tight">Admin Portal</h1>
                <p class="text-sm text-white/40 font-['IBM_Plex_Mono']">AssanDukan Super Admin Access</p>
            </div>

            <!-- Status -->
            <div v-if="status" class="px-4 py-3 rounded-lg text-sm text-[#2FA968] bg-[#1F7A4D]/10 border border-[#1F7A4D]/30 text-center">
                {{ status }}
            </div>

            <!-- Card -->
            <div class="p-8 rounded-2xl border border-white/10 bg-white/5 backdrop-blur space-y-6 shadow-2xl">

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-white/45 uppercase tracking-widest">
                            Admin Email
                        </label>
                        <input
                            id="admin-email" type="email" v-model="form.email" required autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3.5 rounded-lg border border-white/10 bg-white/5 text-white text-sm placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#C4432E] focus:border-[#C4432E] transition-all"
                            placeholder="admin@AssanDukan.online"
                        />
                        <p v-if="form.errors.email" class="text-[#FF8A75] text-xs">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-white/45 uppercase tracking-widest">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                id="admin-password" :type="showPassword ? 'text' : 'password'"
                                v-model="form.password" required autocomplete="current-password"
                                class="w-full px-4 py-3.5 rounded-lg border border-white/10 bg-white/5 text-white text-sm placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#C4432E] focus:border-[#C4432E] transition-all pr-14"
                                placeholder="••••••••"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/35 hover:text-white/60 transition-colors text-xs font-['IBM_Plex_Mono']">
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-[#FF8A75] text-xs">{{ form.errors.password }}</p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-4 rounded-lg font-bold text-sm tracking-wide text-white transition-all active:scale-[0.98] disabled:opacity-50 mt-2 bg-[#C4432E] hover:bg-[#d65940] shadow-[0_4px_0_0_#7A2A1D] hover:shadow-[0_2px_0_0_#7A2A1D] hover:translate-y-[2px]">
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Authenticating...
                        </span>
                        <span v-else>Access Admin Panel</span>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center space-y-2">
                <p class="text-xs text-white/25 font-['IBM_Plex_Mono']">
                    Restricted access. Unauthorized attempts are logged.
                </p>
                <a href="/login" class="text-xs text-white/35 hover:text-white/55 transition-colors underline">
                    ← Back to Store Owner Login
                </a>
            </div>
        </div>
    </div>
</template>