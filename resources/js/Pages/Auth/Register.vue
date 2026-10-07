<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  plan_slug: String,
  plan_features: Object,
  themes: Array
})

const currentStep = ref(1) // 1 = User Account, 2 = Store Profile, 3 = Select Theme

const slugAvailable = ref(null)
const slugChecking = ref(false)
const slugFeedback = ref('')

const categories = [
  'Clothing & Fashion',
  'Cosmetics & Perfumes',
  'Electronics & Gadgets',
  'Home Decor & Furniture',
  'Bakery & Food',
  'Grocery & Kiryana',
  'Other',
]

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  business_name: '',
  store_slug: '',
  category: '',
  theme_slug: 'basic',
  plan_slug: props.plan_slug ?? 'basic',
})

// Auto-generate slug from business name
watch(() => form.business_name, (newName) => {
  if (currentStep.value === 2 && !form.store_slug) {
    form.store_slug = newName
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
  }
})

// Debounce slug availability check
let slugTimeout = null
watch(() => form.store_slug, (newSlug) => {
  // Clean slug
  form.store_slug = newSlug
    .toLowerCase()
    .replace(/[^a-z0-9\-]+/g, '')

  if (!form.store_slug) {
    slugAvailable.value = null
    slugFeedback.value = ''
    return
  }

  slugChecking.value = true
  slugAvailable.value = null

  if (slugTimeout) clearTimeout(slugTimeout)

  slugTimeout = setTimeout(() => {
    axios.get(route('api.check-slug', { slug: form.store_slug }))
      .then(res => {
        slugAvailable.value = res.data.available
        slugFeedback.value = res.data.message
      })
      .catch(() => {
        slugAvailable.value = false
        slugFeedback.value = 'Failed to verify store name.'
      })
      .finally(() => {
        slugChecking.value = false
      })
  }, 450)
})

function nextStep() {
  if (currentStep.value === 1) {
    if (!form.name || !form.email || !form.phone || !form.password || form.password !== form.password_confirmation) {
      alert('Please fill out all user profile details correctly.')
      return
    }
    currentStep.value = 2
  } else if (currentStep.value === 2) {
    if (!form.business_name || !form.store_slug || !form.category || !slugAvailable.value) {
      alert('Please choose an available store name and category.')
      return
    }
    currentStep.value = 3
  }
}

function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function submit() {
  form.post(route('register'))
}
</script>

<template>
  <Head title="Create Your Store">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500..900;1,9..144,500..700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet" />
  </Head>

  <div class="min-h-screen bg-[#141B2E] text-white flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-['Plus_Jakarta_Sans'] relative overflow-hidden">
    <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full opacity-20 blur-3xl pointer-events-none" style="background: #1F7A4D;"></div>
    <div class="absolute -bottom-32 -right-16 w-[32rem] h-[32rem] rounded-full opacity-15 blur-3xl pointer-events-none" style="background: #E8A23D;"></div>

    <!-- Header -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="w-12 h-12 bg-[#1F7A4D] rounded-lg flex items-center justify-center text-white font-['Fraunces'] font-bold text-2xl mx-auto shadow-lg">M</div>
      <h2 class="mt-4 font-['Fraunces'] text-2xl font-semibold text-white tracking-tight">Create Your Merchant Store</h2>
      <p class="mt-1.5 text-sm text-white/50">
        Already have an account?
        <Link :href="route('login')" class="text-[#E8A23D] hover:text-[#f0b45f] font-medium">Sign in</Link>
      </p>
    </div>

    <!-- Wizard Box -->
    <div class="relative z-10 mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
      <div class="bg-white/5 border border-white/10 backdrop-blur py-8 px-6 shadow-2xl rounded-2xl sm:px-10 space-y-6">

        <!-- Step Indicators -->
        <div class="flex items-center justify-between text-xs font-semibold text-white/35 px-2 font-['IBM_Plex_Mono']">
          <span :class="currentStep >= 1 ? 'text-[#E8A23D]' : ''">1. Account</span>
          <span class="w-10 h-px bg-white/10"></span>
          <span :class="currentStep >= 2 ? 'text-[#E8A23D]' : ''">2. Store Settings</span>
          <span class="w-10 h-px bg-white/10"></span>
          <span :class="currentStep >= 3 ? 'text-[#E8A23D]' : ''">3. Theme Selection</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <!-- Step 1: User Profile Details -->
          <div v-show="currentStep === 1" class="space-y-4">
            <h3 class="font-['Fraunces'] text-sm font-semibold text-white/80">Merchant User Profile</h3>

            <div>
              <label class="block text-xs text-white/45 mb-1">Your Full Name</label>
              <input v-model="form.name" type="text" placeholder="Ali Ahmed" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
            </div>

            <div>
              <label class="block text-xs text-white/45 mb-1">Email Address</label>
              <input v-model="form.email" type="email" placeholder="ali@example.com" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
              <p v-if="form.errors.email" class="text-[#FF8A75] text-xs mt-1 font-['IBM_Plex_Mono']">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="block text-xs text-white/45 mb-1">Mobile / WhatsApp Number (for login/alerts)</label>
              <input v-model="form.phone" type="text" placeholder="+923001234567" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-white/45 mb-1">Password</label>
                <input v-model="form.password" type="password" required
                  class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
              </div>
              <div>
                <label class="block text-xs text-white/45 mb-1">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" required
                  class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
              </div>
            </div>
            <p v-if="form.errors.password" class="text-[#FF8A75] text-xs mt-1 font-['IBM_Plex_Mono']">{{ form.errors.password }}</p>
          </div>

          <!-- Step 2: Store Info -->
          <div v-show="currentStep === 2" class="space-y-4">
            <h3 class="font-['Fraunces'] text-sm font-semibold text-white/80">Set Up Your Storefront</h3>

            <div>
              <label class="block text-xs text-white/45 mb-1">Business Name</label>
              <input v-model="form.business_name" type="text" placeholder="Khyber Couture" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-white/25 focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]" />
            </div>

            <div>
              <label class="block text-xs text-white/45 mb-1">Desired Store Slug (Store Link)</label>
              <div class="flex items-center bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 focus-within:ring-2 focus-within:ring-[#1F7A4D]">
                <span class="text-white/35 text-sm select-none font-['IBM_Plex_Mono']">AssanDukan.online/store/</span>
                <input v-model="form.store_slug" type="text" placeholder="khyber-couture" required
                  class="bg-transparent border-0 p-0 text-sm text-white focus:outline-none focus:ring-0 w-full font-['IBM_Plex_Mono']" />
              </div>
              <!-- Live Slug Checker Status -->
              <p v-if="slugChecking" class="text-xs text-white/40 mt-1">⏳ Checking availability...</p>
              <p v-else-if="slugAvailable === true" class="text-xs text-[#2FA968] mt-1">✅ {{ slugFeedback }}</p>
              <p v-else-if="slugAvailable === false" class="text-xs text-[#FF8A75] mt-1">❌ {{ slugFeedback }}</p>
              <p v-if="form.errors.store_slug" class="text-[#FF8A75] text-xs mt-1 font-['IBM_Plex_Mono']">{{ form.errors.store_slug }}</p>
            </div>

            <div>
              <label class="block text-xs text-white/45 mb-1">Business Category</label>
              <select v-model="form.category" required
                class="w-full bg-white/5 border border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#1F7A4D] focus:border-[#1F7A4D]">
                <option value="" disabled class="bg-[#141B2E]">Select business category</option>
                <option v-for="cat in categories" :key="cat" :value="cat" class="bg-[#141B2E]">{{ cat }}</option>
              </select>
            </div>
          </div>

          <!-- Step 3: Select Theme -->
          <div v-show="currentStep === 3" class="space-y-4">
            <h3 class="font-['Fraunces'] text-sm font-semibold text-white/80">Choose Initial Storefront Theme</h3>
            <p class="text-xs text-white/40">You can customize colors, banners, and logos from the dashboard anytime.</p>

            <div class="grid grid-cols-2 gap-3.5">
              <div
                v-for="theme in themes"
                :key="theme.slug"
                @click="() => {
                  if (theme.slug === 'basic' || props.plan_features?.premium_themes_allowed) {
                    form.theme_slug = theme.slug;
                  }
                }"
                class="border rounded-xl p-4 text-left transition-all"
                :class="[
                  (theme.slug !== 'basic' && !props.plan_features?.premium_themes_allowed)
                    ? 'opacity-50 cursor-not-allowed border-white/5 bg-white/5'
                    : 'cursor-pointer hover:-translate-y-0.5',
                  form.theme_slug === theme.slug
                    ? 'border-[#1F7A4D] bg-[#1F7A4D]/10 ring-1 ring-[#1F7A4D]'
                    : 'border-white/10 bg-white/5 hover:border-white/20'
                ]"
              >
                <div class="flex items-center justify-between mb-1">
                  <div class="flex items-center gap-2">
                    <span class="font-['Fraunces'] font-semibold text-sm text-white">{{ theme.name }}</span>
                    <span v-if="theme.slug !== 'basic' && !props.plan_features?.premium_themes_allowed" class="text-[10px] bg-white/10 px-1.5 py-0.5 rounded text-white/50">Pro</span>
                  </div>
                  <span v-if="form.theme_slug === theme.slug" class="text-[#E8A23D] text-xs">● Selected</span>
                </div>
                <p class="text-xs text-white/45 leading-snug">{{ theme.desc }}</p>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex gap-3 pt-4 border-t border-white/10">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="prevStep"
              class="w-full py-3 bg-white/5 hover:bg-white/10 text-white/70 rounded-lg text-sm font-medium transition-all border border-white/10"
            >
              Back
            </button>

            <button
              v-if="currentStep < 3"
              type="button"
              @click="nextStep"
              class="w-full py-3 bg-[#1F7A4D] hover:bg-[#2C9660] text-white rounded-lg text-sm font-semibold transition-all shadow-[0_3px_0_0_#0F4A2C] hover:shadow-[0_1px_0_0_#0F4A2C] hover:translate-y-[2px]"
            >
              Continue
            </button>

            <button
              v-else
              type="submit"
              :disabled="form.processing"
              class="w-full py-3 bg-[#E8A23D] hover:bg-[#f0b45f] text-[#1B1B1D] font-bold rounded-lg text-sm transition-all disabled:opacity-50"
            >
              🚀 Launch Store!
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</template>