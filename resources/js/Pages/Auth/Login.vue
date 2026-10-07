<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Sign In — AssanDukan" />

    <div class="min-h-screen flex bg-[#141B2E] font-['Plus_Jakarta_Sans']">

        <!-- Left Panel: Branding -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12 bg-[#141B2E]">

            <!-- subtle ambient glow, night-bazaar not cosmic-purple -->
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-20 blur-3xl" style="background: #1F7A4D;"></div>
            <div class="absolute -bottom-32 -right-16 w-[32rem] h-[32rem] rounded-full opacity-15 blur-3xl" style="background: #E8A23D;"></div>
            <div class="absolute inset-0 border-r border-white/5"></div>

            <!-- Logo + tagline -->
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg font-['Fraunces'] font-bold text-white bg-[#1F7A4D]">A</div>
                    <span class="text-white font-['Fraunces'] font-semibold text-2xl tracking-tight">AssanDukan</span>
                </div>
                <h1 class="font-['Fraunces'] text-4xl font-semibold text-white leading-[1.1] mb-4">
                    Sell smarter.<br />Reach customers on<br /><span class="italic text-[#E8A23D]">WhatsApp.</span>
                </h1>
                <p class="text-white/50 text-base leading-relaxed max-w-xs">
                    Pakistan's WhatsApp-first e-commerce platform. Build a store your customers can order from in minutes.
                </p>
            </div>

            <!-- Mini order ticket, echoing the landing page signature -->
            <div class="relative z-10 max-w-[240px]">
                <div class="bg-[#FBF6EC] text-[#1B1B1D] rounded-t-lg px-5 pt-5 pb-6"
                     style="clip-path: polygon(0 0,100% 0,100% 94%,92% 100%,84% 94%,76% 100%,68% 94%,60% 100%,52% 94%,44% 100%,36% 94%,28% 100%,20% 94%,12% 100%,4% 94%,0 100%);">
                    <div class="text-center font-['Fraunces'] font-semibold text-xs tracking-widest uppercase">AssanDukan</div>
                    <div class="text-center text-[9px] text-[#1B1B1D]/45 font-['IBM_Plex_Mono'] mt-1">Order #00842</div>
                    <div class="border-t border-dashed border-[#1B1B1D]/25 my-3"></div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-[10px] font-['IBM_Plex_Mono'] text-[#1B1B1D]/55">TOTAL</span>
                        <span class="font-['Fraunces'] font-bold text-base text-[#C4432E]">Rs. 2,250</span>
                    </div>
                </div>
                <div class="flex justify-center py-1.5">
                    <div class="w-px h-4 border-l border-dashed border-white/25"></div>
                </div>
                <div class="bg-[#1F7A4D] rounded-xl rounded-tl-sm px-3.5 py-2.5 shadow-lg flex items-center gap-2">
                    <span class="text-sm leading-none">💬</span>
                    <span class="text-[11px] text-white/90">Sent to your WhatsApp ✓✓</span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 bg-[#0F1424]">
            <div class="w-full max-w-md space-y-8">

                <!-- Mobile logo -->
                <div class="flex items-center gap-3 lg:hidden mb-2">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-base font-['Fraunces'] font-bold text-white bg-[#1F7A4D]">M</div>
                    <span class="text-white font-['Fraunces'] font-semibold text-xl">AssanDukan</span>
                </div>

                <div>
                    <h2 class="font-['Fraunces'] text-3xl font-semibold text-white">Welcome back 👋</h2>
                    <p class="text-white/50 mt-2 text-sm">Sign in to your store owner dashboard</p>
                </div>

                <div v-if="status" class="px-4 py-3 rounded-xl text-sm text-[#2FA968] bg-[#1F7A4D]/10 border border-[#1F7A4D]/30 font-['IBM_Plex_Mono']">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-white/70">Email Address</label>
                        <input
                            id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                            class="w-full px-4 py-3.5 rounded-lg border border-white/10 bg-white/5 text-white text-sm placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D] transition-all"
                            placeholder="you@example.com"
                        />
                        <p v-if="form.errors.email" class="text-[#C4432E] text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-white/70">Password</label>
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="text-xs text-[#E8A23D] hover:text-[#f0b45f] transition-colors">
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <input
                                id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                required autocomplete="current-password"
                                class="w-full px-4 py-3.5 rounded-lg border border-white/10 bg-white/5 text-white text-sm placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D] transition-all pr-14"
                                placeholder="••••••••"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/40 hover:text-white/70 transition-colors text-xs font-['IBM_Plex_Mono']">
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-[#C4432E] text-xs mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember -->
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input type="checkbox" v-model="form.remember" class="w-4 h-4 rounded border-white/20 accent-[#1F7A4D]" />
                        <span class="text-sm text-white/55">Keep me signed in</span>
                    </label>

                    <!-- Submit -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full py-4 rounded-lg font-bold text-white text-sm tracking-wide transition-all active:scale-[0.98] disabled:opacity-50 bg-[#1F7A4D] hover:bg-[#2C9660] shadow-[0_4px_0_0_#0F4A2C] hover:shadow-[0_2px_0_0_#0F4A2C] hover:translate-y-[2px]">
                        {{ form.processing ? 'Signing in...' : 'Sign In to Dashboard' }}
                    </button>

                    <!-- Register CTA -->
                    <p class="text-center text-sm text-white/40">
                        Don't have a store yet?
                        <Link :href="route('register')" class="text-[#E8A23D] hover:text-[#f0b45f] font-semibold ml-1">
                            Start for free
                        </Link>
                    </p>
                </form>


            </div>
        </div>
    </div>
</template>