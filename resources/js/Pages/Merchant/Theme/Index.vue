<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  themes: Object,
  active_slug: String,
  settings: Object,
  products: Array,
  can_change: Boolean,
  theme_change_fee: Number,
  theme_unlock_pending: Boolean,
})

const form = useForm({
  _method: 'put',
  theme_slug: props.active_slug ?? 'basic',
  accent_color: props.settings?.accent_color ?? '',
  secondary_color: props.settings?.secondary_color ?? '',
  typography: props.settings?.typography ?? 'Inter',
  heading_font: props.settings?.heading_font ?? '',
  favicon: null,
  logo: null,
  banner_image: null,
  banner_text: props.settings?.custom_settings?.banner_text ?? '',
  store_description: props.settings?.custom_settings?.store_description ?? '',
  bg_color: props.settings?.custom_settings?.bg_color ?? '',
  text_color: props.settings?.custom_settings?.text_color ?? '',
  seo_title: props.settings?.custom_settings?.seo_title ?? '',
  seo_description: props.settings?.custom_settings?.seo_description ?? '',
  seo_image: null,
})



const previewSlug = ref(props.active_slug ?? 'basic')
const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const formError = computed(() => page.props.errors?.error ?? null)

// True when the currently *selected* (not yet saved) theme is premium.
const selectedIsPremium = computed(() => !!props.themes?.[form.theme_slug]?.is_premium)
const isChangingTheme = computed(() => form.theme_slug !== props.active_slug)
// Only block/charge when they're actually switching INTO a premium theme.
const needsUnlock = computed(() => isChangingTheme.value && selectedIsPremium.value && !props.can_change)

const unlockForm = useForm({ unlock_type: 'theme_change', payment_proof: null })

function requestThemeUnlock() {
  document.getElementById('theme-unlock-proof-input').click()
}

function handleProofSelected(e) {
  const file = e.target.files[0]
  if (!file) return
  unlockForm.payment_proof = file
  unlockForm.post(route('merchant.settings.unlock'), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => { unlockForm.payment_proof = null; e.target.value = '' },
  })
}

function submit() {
  form.post(route('merchant.theme.update'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <MerchantLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Storefront Theme</h1>
    </template>

    <div class="space-y-5">
      <!-- Flash Messages -->
      <div v-if="flash.success" class="bg-emerald-500/10 border border-emerald-500/25 rounded-xl p-4 text-sm text-emerald-300 flex items-center gap-3">
        <span class="text-lg">✓</span> {{ flash.success }}
      </div>
      <div v-if="formError" class="bg-red-500/10 border border-red-500/25 rounded-xl p-4 text-sm text-red-300 flex items-center gap-3">
        <span class="text-lg">⚠</span> {{ formError }}
      </div>

      <!-- Hidden file input for the theme-unlock payment proof -->
      <input id="theme-unlock-proof-input" type="file" accept="image/*" class="hidden" @change="handleProofSelected" />

      <!-- Premium theme lock banner: only shown when the SELECTED theme is premium and locked -->
      <div v-if="needsUnlock" class="bg-orange-500/10 border border-orange-500/25 rounded-xl p-4 text-sm text-orange-300 space-y-2">
        <div><span class="font-bold">🔒 "{{ themes[form.theme_slug]?.name }}" is a premium theme.</span> Your plan only includes the free Basic theme — switching to a premium theme needs an upgrade or a one-time unlock fee.</div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
          <div v-if="theme_unlock_pending" class="text-xs px-3 py-2 bg-yellow-500/10 text-yellow-400 border border-yellow-500/30 rounded-xl">
            ⏳ Unlock request pending admin approval
          </div>
          <button v-else type="button" @click="requestThemeUnlock" :disabled="unlockForm.processing"
            class="text-xs px-4 py-2 rounded-xl border border-orange-500/40 bg-orange-500/10 font-semibold transition-all hover:bg-orange-500/20 disabled:opacity-50">
            🔓 Pay Rs. {{ Number(theme_change_fee).toLocaleString() }} to Unlock This Theme
          </button>
          <span class="text-xs text-orange-300/70">Or upgrade your plan for unlimited free theme switching.</span>
        </div>
      </div>

      <form @submit.prevent="submit" class="grid lg:grid-cols-2 gap-5 sm:gap-6">
        <!-- Theme Selection -->
        <div class="space-y-5 sm:space-y-6">
          <div class="card p-4 sm:p-5 space-y-4">
            <h2 class="font-bold text-white text-sm">Select Theme</h2>

            <div class="space-y-3">
              <label
                v-for="(theme, slug) in themes"
                :key="slug"
                class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-4 border rounded-xl cursor-pointer transition-all"
                :class="form.theme_slug === slug
                  ? 'border-emerald-500 bg-emerald-500/5 ring-1 ring-emerald-500'
                  : 'border-white/5 bg-black/20 hover:border-white/15'"
              >
                <input type="radio" v-model="form.theme_slug" :value="slug" @change="previewSlug = slug" class="sr-only" />
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-xl border border-white/10"
                    :style="{ background: theme.accent + '22', borderColor: theme.accent + '44' }">
                    {{ slug === 'fashion' ? '👗' : slug === 'cosmetics' ? '💄' : slug === 'electronics' ? '💻' : slug === 'decor' ? '🏠' : '🏪' }}
                  </div>
                  <div class="flex-1 sm:hidden">
                    <div class="font-bold text-white text-sm">{{ theme.name }}</div>
                  </div>
                  <div class="w-5 h-5 shrink-0 sm:hidden rounded-full border-2 flex items-center justify-center ml-auto"
                    :class="form.theme_slug === slug ? 'border-emerald-500 bg-emerald-500' : 'border-white/20'">
                    <div v-if="form.theme_slug === slug" class="w-2 h-2 bg-white rounded-full"></div>
                  </div>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="hidden sm:flex items-center gap-2">
                    <span class="font-bold text-white text-sm">{{ theme.name }}</span>
                    <span v-if="theme.is_premium" class="badge bg-amber-500/15 text-amber-300 border border-amber-500/30 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded">
                      Premium
                    </span>
                    <span v-else class="badge bg-sky-500/15 text-sky-300 border border-sky-500/30 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded">
                      Free
                    </span>
                  </div>
                  <div class="text-[10px] sm:text-xs text-gray-500 mt-1 sm:mt-0.5 leading-snug">{{ theme.description }}</div>
                  <div v-if="theme.is_premium && !can_change" class="text-[10px] sm:text-xs text-amber-400/80 mt-1">
                    Rs. {{ Number(theme_change_fee).toLocaleString() }} one-time fee to switch to this theme
                  </div>
                </div>

                <div class="hidden sm:flex w-5 h-5 shrink-0 rounded-full border-2 items-center justify-center"
                  :class="form.theme_slug === slug ? 'border-emerald-500 bg-emerald-500' : 'border-white/20'">
                  <div v-if="form.theme_slug === slug" class="w-2 h-2 bg-white rounded-full"></div>
                </div>
              </label>
            </div>
          </div>

          <!-- Custom Branding -->
          <div class="card p-4 sm:p-5 space-y-4">
            <h2 class="font-bold text-white text-sm">Brand Colors & Typography</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Primary Color (Accent)</label>
                <div class="flex items-center gap-3">
                  <input type="color" v-model="form.accent_color"
                    class="w-12 h-10 rounded-lg border border-white/10 cursor-pointer bg-transparent shrink-0" />
                  <input type="text" v-model="form.accent_color" placeholder="#10b981"
                    class="form-input font-mono uppercase" />
                </div>
              </div>

              <div>
                <label class="form-label">Secondary Color (Background)</label>
                <div class="flex items-center gap-3">
                  <input type="color" v-model="form.secondary_color"
                    class="w-12 h-10 rounded-lg border border-white/10 cursor-pointer bg-transparent shrink-0" />
                  <input type="text" v-model="form.secondary_color" placeholder="#111111"
                    class="form-input font-mono uppercase" />
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Background Color (Optional)</label>
                <div class="flex items-center gap-3">
                  <input type="color" v-model="form.bg_color"
                    class="w-12 h-10 rounded-lg border border-white/10 cursor-pointer bg-transparent shrink-0" />
                  <input type="text" v-model="form.bg_color" placeholder="#f8f9fb"
                    class="form-input font-mono uppercase" />
                </div>
              </div>

              <div>
                <label class="form-label">Text Color (Optional)</label>
                <div class="flex items-center gap-3">
                  <input type="color" v-model="form.text_color"
                    class="w-12 h-10 rounded-lg border border-white/10 cursor-pointer bg-transparent shrink-0" />
                  <input type="text" v-model="form.text_color" placeholder="#111111"
                    class="form-input font-mono uppercase" />
                </div>
              </div>
            </div>

            <div>
              <label class="form-label">Body Typography (Google Font Name)</label>
              <input v-model="form.typography" type="text" placeholder="Inter, Roboto, Outfit..." class="form-input" />
            </div>

            <div>
              <label class="form-label">Heading Typography (Optional)</label>
              <input v-model="form.heading_font" type="text" placeholder="Playfair Display, Cormorant Garamond..." class="form-input" />
            </div>

            <div>
              <label class="form-label">Store Logo (Replaces text in navbar)</label>
              <input type="file" @input="form.logo = $event.target.files[0]" accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10" />
              <div v-if="settings?.logo_override_path" class="mt-2 flex items-center gap-2">
                <span class="text-xs text-gray-500">Current:</span>
                <img :src="`/storage/${settings.logo_override_path}`" class="h-8 object-contain bg-white/10 p-0.5 rounded" />
              </div>
            </div>

            <div>
              <label class="form-label">Hero Banner Image</label>
              <p class="text-[10px] text-gray-500 mb-1.5">Replaces the product photo in the hero section</p>
              <input type="file" @input="form.banner_image = $event.target.files[0]" accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10" />
              <div v-if="settings?.banner_image_path" class="mt-2 flex items-center gap-2">
                <span class="text-xs text-gray-500">Current:</span>
                <img :src="`/storage/${settings.banner_image_path}`" class="h-12 w-20 object-cover bg-white/10 rounded" />
              </div>
            </div>

            <div>
              <label class="form-label">Favicon (.ico, .png, .svg)</label>
              <input type="file" @input="form.favicon = $event.target.files[0]" accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10" />
              <div v-if="settings?.favicon_path" class="mt-2 flex items-center gap-2">
                <span class="text-xs text-gray-500">Current:</span>
                <img :src="`/storage/${settings.favicon_path}`" class="w-6 h-6 object-contain bg-white/10 p-0.5 rounded" />
              </div>
            </div>
          </div>
        </div>

        <!-- Store Copy Settings -->
        <div class="space-y-5 sm:space-y-6">
          <div class="card p-4 sm:p-5 space-y-4">
            <h2 class="font-bold text-white text-sm">Storefront Copy</h2>
            <div>
              <label class="form-label">Banner Headline</label>
              <input v-model="form.banner_text" type="text" placeholder="Welcome to Khyber Couture" class="form-input" />
            </div>
            <div>
              <label class="form-label">Store Description / Tagline</label>
              <textarea v-model="form.store_description" rows="3"
                placeholder="Browse our exclusive Pakistani lawn suits and order on WhatsApp!"
                class="form-input resize-none"></textarea>
            </div>
          </div>


          <div class="card p-4 sm:p-5 space-y-4">
            <h2 class="font-bold text-white text-sm">SEO & Meta Tags</h2>
            <div>
              <label class="form-label">SEO Title (Max 60 chars)</label>
              <input v-model="form.seo_title" type="text" placeholder="Buy Quality Products at MyStore" class="form-input" maxlength="60" />
            </div>
            <div>
              <label class="form-label">SEO Description (Max 160 chars)</label>
              <textarea v-model="form.seo_description" rows="2" maxlength="160"
                placeholder="Shop the best products online. Fast delivery."
                class="form-input resize-none"></textarea>
            </div>
            <div>
              <label class="form-label">SEO Open Graph Image (For social sharing)</label>
              <input type="file" @input="form.seo_image = $event.target.files[0]" accept="image/*" class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-white/5 file:text-white hover:file:bg-white/10" />
              <div v-if="settings?.seo_image_path" class="mt-2 flex items-center gap-2">
                <span class="text-xs text-gray-500">Current:</span>
                <img :src="`/storage/${settings.seo_image_path}`" class="h-10 w-16 object-cover bg-white/10 rounded" />
              </div>
            </div>
          </div>

          <!-- Active Theme Preview Panel -->
          <div class="card p-4 sm:p-5 space-y-3">
            <h2 class="font-bold text-white text-sm">Currently Active</h2>
            <div class="flex items-center gap-3 p-3 bg-black/20 border border-white/5 rounded-xl">
              <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-xl border border-white/10"
                :style="{ background: (themes[active_slug]?.accent ?? '#10b981') + '22' }">
                {{ active_slug === 'fashion' ? '👗' : active_slug === 'cosmetics' ? '💄' : active_slug === 'electronics' ? '💻' : active_slug === 'decor' ? '🏠' : '🏪' }}
              </div>
              <div>
                <div class="font-bold text-white text-sm">{{ themes[active_slug]?.name ?? 'Fashion' }}</div>
                <div class="text-[10px] sm:text-xs text-gray-500">Your live storefront theme</div>
              </div>
            </div>
          </div>

          <div class="pt-2">
            <button type="submit" class="btn-primary w-full py-3 text-base disabled:opacity-50" :disabled="needsUnlock">
              {{ form.processing ? 'Saving...' : (needsUnlock ? 'Unlock the theme above to save' : 'Save Theme Settings') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </MerchantLayout>
</template>
