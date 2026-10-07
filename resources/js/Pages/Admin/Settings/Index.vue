<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  settings: Object,
})

const activeTab = ref('brand')
const tabs = [
  { id: 'brand', label: 'Brand & Display' },
  { id: 'landing', label: 'Landing Page' },
  { id: 'payment', label: 'Payments' },
  { id: 'email', label: 'Email (SMTP)' },
  { id: 'seo', label: 'SEO' },
  { id: 'maintenance', label: '🔧 Maintenance' },
]

const form = useForm({
  _method: 'post',
  brand_platform_name: props.settings.brand_platform_name ?? 'AsaanDukan',
  brand_primary_color: props.settings.brand_primary_color ?? '#1F7A4D',
  brand_secondary_color: props.settings.brand_secondary_color ?? '#0f172a',
  brand_typography: props.settings.brand_typography ?? 'Inter',
  admin_whatsapp_number: props.settings.admin_whatsapp_number ?? '',
  brand_logo: null,
  brand_favicon: null,
  
  landing_show_hero: props.settings.landing_show_hero ?? '1',
  landing_show_trusted: props.settings.landing_show_trusted ?? '1',
  landing_show_features: props.settings.landing_show_features ?? '1',
  landing_show_analytics: props.settings.landing_show_analytics ?? '1',
  landing_show_notifications: props.settings.landing_show_notifications ?? '1',
  landing_show_stats: props.settings.landing_show_stats ?? '1',
  landing_show_use_cases: props.settings.landing_show_use_cases ?? '1',
  landing_show_faq: props.settings.landing_show_faq ?? '1',
  landing_show_testimonials: props.settings.landing_show_testimonials ?? '1',
  landing_show_pricing: props.settings.landing_show_pricing ?? '1',
  landing_show_cta: props.settings.landing_show_cta ?? '1',
  landing_show_contact: props.settings.landing_show_contact ?? '1',

  landing_hero_headline: props.settings.landing_hero_headline ?? 'Apna Dukan,',
  landing_hero_headline_end: props.settings.landing_hero_headline_end ?? 'Online Karein',
  landing_hero_subheadline: props.settings.landing_hero_subheadline ?? 'Sirf kuch minutes mein apna store tayar karein — no coding, no hassle.',
  landing_hero_offer: props.settings.landing_hero_offer ?? 'New — WhatsApp Order Alerts',
  landing_hero_btn1_text: props.settings.landing_hero_btn1_text ?? 'Start Free Trial',
  landing_hero_btn2_text: props.settings.landing_hero_btn2_text ?? 'See How It Works',
  landing_hero_image_1: null,
  landing_hero_image_2: null,
  landing_hero_image_3: null,

  landing_trusted_title: props.settings.landing_trusted_title ?? 'Trusted by shopkeepers across Pakistan',

  landing_features_title: props.settings.landing_features_title ?? 'Everything your dukan needs',
  landing_features_json: typeof props.settings.landing_features_json === 'string' ? JSON.parse(props.settings.landing_features_json) : (props.settings.landing_features_json ?? [
    { title: 'WhatsApp Orders', text: 'Every order lands straight in your WhatsApp — never miss a sale.', icon: 'MessageCircle' },
    { title: 'Custom Domains', text: 'Connect your own .com or .pk domain to build your brand identity.', icon: 'Globe' },
    { title: 'Offline Mode (PWA)', text: 'Your storefront works even when your customers are offline.', icon: 'WifiOff' },
    { title: 'Staff Management', text: 'Add your employees and control exactly what they can see and do.', icon: 'Users' },
    { title: 'Dynamic SEO', text: 'Automatically optimized for Google search and perfect social media sharing.', icon: 'Search' },
    { title: 'Advanced Analytics', text: 'Track daily sales, top products, and overall growth from your dashboard.', icon: 'BarChart2' }
  ]),

  landing_analytics_title: props.settings.landing_analytics_title ?? 'One Dashboard For Your Whole Dukan',
  landing_analytics_text: props.settings.landing_analytics_text ?? 'Every order, product and customer, tracked in one place.',
  landing_analytics_bullets_json: typeof props.settings.landing_analytics_bullets_json === 'string' ? JSON.parse(props.settings.landing_analytics_bullets_json) : (props.settings.landing_analytics_bullets_json ?? ['One dashboard for every order', 'Daily sales totals']),
  landing_analytics_image: null,

  landing_notifications_title: props.settings.landing_notifications_title ?? 'Get Order Alerts Straight To WhatsApp',
  landing_notifications_text: props.settings.landing_notifications_text ?? 'The moment a customer checks out, you get a WhatsApp message.',
  landing_notifications_bullets_json: typeof props.settings.landing_notifications_bullets_json === 'string' ? JSON.parse(props.settings.landing_notifications_bullets_json) : (props.settings.landing_notifications_bullets_json ?? ['Instant order pings', 'Customer name & items']),
  landing_notifications_image: null,

  landing_stats_json: typeof props.settings.landing_stats_json === 'string' ? JSON.parse(props.settings.landing_stats_json) : (props.settings.landing_stats_json ?? [
    { value: '2,400+', label: 'Dukaans Launched' }
  ]),

  landing_use_cases_title: props.settings.landing_use_cases_title ?? 'A storefront for every kind of shop',

  landing_faq_title: props.settings.landing_faq_title ?? 'Frequently asked questions',
  landing_faq_json: typeof props.settings.landing_faq_json === 'string' ? JSON.parse(props.settings.landing_faq_json) : (props.settings.landing_faq_json ?? [
    { question: 'Do I need my own domain?', answer: 'No! Your store is hosted on our platform immediately.' }
  ]),
  landing_faq_image: null,

  landing_testimonials_title: props.settings.landing_testimonials_title ?? 'What our shopkeepers say',
  landing_testimonials_json: typeof props.settings.landing_testimonials_json === 'string' ? JSON.parse(props.settings.landing_testimonials_json) : (props.settings.landing_testimonials_json ?? []),

  landing_pricing_title: props.settings.landing_pricing_title ?? 'Simple, honest pricing',

  landing_cta_title: props.settings.landing_cta_title ?? 'Ready to open your dukan tonight?',
  landing_cta_text: props.settings.landing_cta_text ?? 'No coding, no credit card.',
  landing_cta_image: null,
  
  landing_newsletter_title: props.settings.landing_newsletter_title ?? 'Subscribe For Dukan Tips',
  landing_newsletter_text: props.settings.landing_newsletter_text ?? 'One email a month.',

  landing_contact_email: props.settings.landing_contact_email ?? 'support@asaandukan.online',
  landing_contact_phone: props.settings.landing_contact_phone ?? '+92 300 1234567',
  
  landing_support_whatsapp: props.settings.landing_support_whatsapp ?? '+923001234567',

  landing_menu: typeof props.settings.landing_menu === 'string' ? JSON.parse(props.settings.landing_menu) : (props.settings.landing_menu ?? []),

  payment_currency: props.settings.payment_currency ?? 'PKR',
  custom_domain_connection_fee: props.settings.custom_domain_connection_fee ?? '500',
  payment_accounts: typeof props.settings.payment_accounts === 'string' ? JSON.parse(props.settings.payment_accounts) : (props.settings.payment_accounts ?? []),

  email_smtp_host: props.settings.email_smtp_host ?? '',
  email_smtp_port: props.settings.email_smtp_port ?? '587',
  email_smtp_user: props.settings.email_smtp_user ?? '',
  email_smtp_pass: props.settings.email_smtp_pass ?? '',
  email_from_address: props.settings.email_from_address ?? '',

  seo_meta_title: props.settings.seo_meta_title ?? '',
  seo_meta_description: props.settings.seo_meta_description ?? '',
  seo_og_image: null,

  maintenance_mode_enabled: props.settings.maintenance_mode_enabled ?? '0',
  maintenance_disable_register: props.settings.maintenance_disable_register ?? '0',
})

function addAccount() {
  form.payment_accounts.push({ bank_name: '', account_title: '', account_number: '' })
}

function removeAccount(index) {
  form.payment_accounts.splice(index, 1)
}

function submit() {
  form.post(route('admin.settings.update'), { preserveScroll: true })
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <h1 class="text-base font-semibold text-white">Global Settings</h1>
    </template>

    <div class="space-y-5">
      <div class="page-header">
        <div>
          <h2 class="page-title">Platform Configuration</h2>
          <p class="page-subtitle">Manage branding, landing page, and integrations.</p>
        </div>
      </div>

      <div class="flex flex-col md:flex-row gap-5 md:items-start">
        <!-- Tabs -->
        <div class="w-full md:w-64 shrink-0 flex flex-row md:flex-col gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
          <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
            class="whitespace-nowrap md:whitespace-normal text-left px-4 py-2.5 rounded-xl text-sm font-bold transition-all"
            :class="activeTab === tab.id ? 'bg-indigo-500 text-white' : 'text-gray-400 hover:text-white hover:bg-white/5'">
            {{ tab.label }}
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 card p-4 sm:p-6 w-full overflow-hidden">
          <form @submit.prevent="submit" class="space-y-6">
            
            <!-- BRAND TAB -->
            <div v-show="activeTab === 'brand'" class="space-y-5 sm:space-y-6">
              <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Brand & Display</h2>
              
              <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <label class="form-label">Platform Name</label>
                  <input v-model="form.brand_platform_name" type="text" class="form-input" />
                </div>
                
                <div>
                  <label class="form-label">Typography (Google Font)</label>
                  <input v-model="form.brand_typography" type="text" placeholder="e.g. Inter, Roboto, Outfit" class="form-input" />
                </div>

                <div class="sm:col-span-2">
                  <label class="form-label">Admin WhatsApp Number</label>
                  <input v-model="form.admin_whatsapp_number" type="text" class="form-input" />
                </div>

                <div>
                  <label class="form-label">Primary Color</label>
                  <div class="flex gap-3">
                    <input v-model="form.brand_primary_color" type="color" class="w-12 h-10 rounded-lg cursor-pointer bg-transparent border-0 p-0 shrink-0" />
                    <input v-model="form.brand_primary_color" type="text" class="form-input font-mono" />
                  </div>
                </div>

                <div>
                  <label class="form-label">Secondary Color</label>
                  <div class="flex gap-3">
                    <input v-model="form.brand_secondary_color" type="color" class="w-12 h-10 rounded-lg cursor-pointer bg-transparent border-0 p-0 shrink-0" />
                    <input v-model="form.brand_secondary_color" type="text" class="form-input font-mono" />
                  </div>
                </div>

                <div>
                  <label class="form-label">Logo</label>
                  <input type="file" @input="form.brand_logo = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                </div>

                <div>
                  <label class="form-label">Favicon</label>
                  <input type="file" @input="form.brand_favicon = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                </div>
              </div>
            </div>

            <!-- LANDING TAB -->
            <div v-show="activeTab === 'landing'" class="space-y-8 sm:space-y-10">
              <div class="border-b border-white/10 pb-3 flex justify-between items-center">
                <h2 class="text-lg font-bold text-white">App Landing Page Builder</h2>
              </div>

              <!-- Section Toggles -->
              <div class="bg-black/20 p-5 rounded-xl border border-white/5 space-y-4">
                <h3 class="text-sm font-bold text-white mb-2">Visible Sections</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-300">
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_hero" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Hero Section</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_trusted" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Trusted Logos</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_features" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Features</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_analytics" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Analytics</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_notifications" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Notifications</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_stats" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Stats</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_use_cases" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Themes/Use Cases</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_faq" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> FAQ</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_testimonials" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Testimonials</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_pricing" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Pricing</label>
                  <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" v-model="form.landing_show_cta" true-value="1" false-value="0" class="rounded bg-black/50 border-white/20 text-indigo-500" /> Bottom CTA</label>
                </div>
              </div>

              <!-- Hero Section -->
              <div class="space-y-4">
                <h3 class="text-indigo-400 font-bold border-b border-indigo-500/20 pb-2">Hero Section</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                  <div class="sm:col-span-2">
                    <label class="form-label">Offer Badge</label>
                    <input v-model="form.landing_hero_offer" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Headline (Start)</label>
                    <input v-model="form.landing_hero_headline" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Headline (Gradient End)</label>
                    <input v-model="form.landing_hero_headline_end" type="text" class="form-input" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="form-label">Subheadline</label>
                    <textarea v-model="form.landing_hero_subheadline" rows="2" class="form-input"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Button 1 Text</label>
                    <input v-model="form.landing_hero_btn1_text" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Button 2 Text</label>
                    <input v-model="form.landing_hero_btn2_text" type="text" class="form-input" />
                  </div>
                  <div class="sm:col-span-2 grid sm:grid-cols-3 gap-4">
                    <div>
                      <label class="form-label">Hero App Image (Left)</label>
                      <input type="file" @input="form.landing_hero_image_1 = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                    </div>
                    <div>
                      <label class="form-label">Hero App Image (Center)</label>
                      <input type="file" @input="form.landing_hero_image_2 = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                    </div>
                    <div>
                      <label class="form-label">Hero App Image (Right)</label>
                      <input type="file" @input="form.landing_hero_image_3 = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notifications Section -->
              <div class="space-y-4">
                <h3 class="text-indigo-400 font-bold border-b border-indigo-500/20 pb-2">WhatsApp Notifications Section</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                  <div class="sm:col-span-2">
                    <label class="form-label">Title</label>
                    <input v-model="form.landing_notifications_title" type="text" class="form-input" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea v-model="form.landing_notifications_text" rows="2" class="form-input"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Checkpoints (JSON Array of strings)</label>
                    <textarea :value="JSON.stringify(form.landing_notifications_bullets_json, null, 2)" @input="e => { try { form.landing_notifications_bullets_json = JSON.parse(e.target.value) } catch (err) {} }" rows="4" class="form-input font-mono text-xs"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Notification Image</label>
                    <input type="file" @input="form.landing_notifications_image = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                  </div>
                </div>
              </div>

              <!-- Analytics Section -->
              <div class="space-y-4">
                <h3 class="text-indigo-400 font-bold border-b border-indigo-500/20 pb-2">Analytics Section</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                  <div class="sm:col-span-2">
                    <label class="form-label">Title</label>
                    <input v-model="form.landing_analytics_title" type="text" class="form-input" />
                  </div>
                  <div class="sm:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea v-model="form.landing_analytics_text" rows="2" class="form-input"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Checkpoints (JSON Array of strings)</label>
                    <textarea :value="JSON.stringify(form.landing_analytics_bullets_json, null, 2)" @input="e => { try { form.landing_analytics_bullets_json = JSON.parse(e.target.value) } catch (err) {} }" rows="4" class="form-input font-mono text-xs"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Analytics Image</label>
                    <input type="file" @input="form.landing_analytics_image = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400" />
                  </div>
                </div>
              </div>

              <!-- General Content Sections -->
              <div class="space-y-4">
                <h3 class="text-indigo-400 font-bold border-b border-indigo-500/20 pb-2">Other Text Sections</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label">Trusted By Title</label>
                    <input v-model="form.landing_trusted_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Features Title</label>
                    <input v-model="form.landing_features_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Themes / Use Cases Title</label>
                    <input v-model="form.landing_use_cases_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Pricing Title</label>
                    <input v-model="form.landing_pricing_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Testimonials Title</label>
                    <input v-model="form.landing_testimonials_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">FAQ Title</label>
                    <input v-model="form.landing_faq_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Bottom CTA Title</label>
                    <input v-model="form.landing_cta_title" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Bottom CTA Text</label>
                    <input v-model="form.landing_cta_text" type="text" class="form-input" />
                  </div>
                </div>
              </div>

              <!-- JSON Arrays -->
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="form-label">Stats Data (JSON Array)</label>
                  <textarea :value="JSON.stringify(form.landing_stats_json, null, 2)" @input="e => { try { form.landing_stats_json = JSON.parse(e.target.value) } catch (err) {} }" rows="4" class="form-input font-mono text-xs resize-y bg-black/30"></textarea>
                </div>
                <div>
                  <label class="form-label">Testimonials Data (JSON Array)</label>
                  <textarea :value="JSON.stringify(form.landing_testimonials_json, null, 2)" @input="e => { try { form.landing_testimonials_json = JSON.parse(e.target.value) } catch (err) {} }" rows="4" class="form-input font-mono text-xs resize-y bg-black/30"></textarea>
                </div>
                <div>
                  <label class="form-label">Features Data (JSON Array)</label>
                  <textarea :value="JSON.stringify(form.landing_features_json, null, 2)" @input="e => { try { form.landing_features_json = JSON.parse(e.target.value) } catch (err) {} }" rows="6" class="form-input font-mono text-xs resize-y bg-black/30"></textarea>
                </div>
                <div>
                  <label class="form-label">FAQ Data (JSON Array)</label>
                  <textarea :value="JSON.stringify(form.landing_faq_json, null, 2)" @input="e => { try { form.landing_faq_json = JSON.parse(e.target.value) } catch (err) {} }" rows="6" class="form-input font-mono text-xs resize-y bg-black/30"></textarea>
                </div>
              </div>

              <!-- Contact Info -->
              <div class="space-y-4">
                <h3 class="text-indigo-400 font-bold border-b border-indigo-500/20 pb-2">Footer Contact Info</h3>
                <div class="grid sm:grid-cols-3 gap-4">
                  <div>
                    <label class="form-label">Contact Email</label>
                    <input v-model="form.landing_contact_email" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Contact Phone</label>
                    <input v-model="form.landing_contact_phone" type="text" class="form-input" />
                  </div>
                  <div>
                    <label class="form-label">Support WhatsApp</label>
                    <input v-model="form.landing_support_whatsapp" type="text" class="form-input" />
                  </div>
                </div>
              </div>
            </div>

            <!-- PAYMENT TAB -->
            <div v-show="activeTab === 'payment'" class="space-y-5 sm:space-y-6">
              <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Payment Settings</h2>
              
              <div class="grid sm:grid-cols-2 gap-6">
                <div>
                  <label class="form-label">Platform Currency</label>
                  <input v-model="form.payment_currency" type="text" placeholder="e.g. PKR, USD" class="form-input font-mono uppercase" />
                </div>
                <div>
                  <label class="form-label">Custom Domain Connection Fee</label>
                  <input v-model="form.custom_domain_connection_fee" type="number" min="0" placeholder="e.g. 500" class="form-input" />
                </div>
              </div>

              <div class="pt-4 border-t border-white/10">
                <div class="flex items-center justify-between gap-4 mb-4">
                  <label class="form-label mb-0">Manual Bank/Wallet Accounts</label>
                  <button type="button" @click="addAccount" class="btn-secondary btn-sm shrink-0">+ Add Account</button>
                </div>
                
                <div v-if="form.payment_accounts.length === 0" class="text-sm text-gray-500 text-center py-6 bg-black/20 rounded-xl border border-white/10 border-dashed">
                  No accounts added.
                </div>

                <div v-else class="space-y-3">
                  <div v-for="(acc, i) in form.payment_accounts" :key="i" class="flex flex-col sm:flex-row gap-3 items-start bg-black/20 p-4 rounded-xl border border-white/10">
                    <div class="flex-1 w-full space-y-3">
                      <input v-model="acc.bank_name" type="text" placeholder="Bank Name" class="form-input" />
                      <input v-model="acc.account_title" type="text" placeholder="Account Title" class="form-input" />
                      <input v-model="acc.account_number" type="text" placeholder="Account Number" class="form-input font-mono" />
                    </div>
                    <button type="button" @click="removeAccount(i)" class="btn-danger btn-sm w-full sm:w-auto flex justify-center py-2 shrink-0">Remove</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- EMAIL TAB -->
            <div v-show="activeTab === 'email'" class="space-y-5 sm:space-y-6">
              <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Email SMTP Settings</h2>
              
              <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <label class="form-label">SMTP Host</label>
                  <input v-model="form.email_smtp_host" type="text" class="form-input" />
                </div>
                <div>
                  <label class="form-label">SMTP Port</label>
                  <input v-model="form.email_smtp_port" type="text" class="form-input" />
                </div>
                <div>
                  <label class="form-label">SMTP Username</label>
                  <input v-model="form.email_smtp_user" type="text" class="form-input" />
                </div>
                <div>
                  <label class="form-label">SMTP Password</label>
                  <input v-model="form.email_smtp_pass" type="password" class="form-input" />
                </div>
                <div class="sm:col-span-2">
                  <label class="form-label">From Email Address</label>
                  <input v-model="form.email_from_address" type="email" class="form-input" />
                </div>
              </div>
            </div>

            <!-- SEO TAB -->
            <div v-show="activeTab === 'seo'" class="space-y-5 sm:space-y-6">
              <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Search Engine Optimization</h2>
              
              <div class="space-y-4">
                <div>
                  <label class="form-label">Meta Title</label>
                  <input v-model="form.seo_meta_title" type="text" class="form-input" />
                </div>
                
                <div>
                  <label class="form-label">Meta Description</label>
                  <textarea v-model="form.seo_meta_description" rows="3" class="form-input resize-none"></textarea>
                </div>

                <div>
                  <label class="form-label">Open Graph Image (For Social Sharing)</label>
                  <input type="file" @input="form.seo_og_image = $event.target.files[0]" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-400 hover:file:bg-indigo-500/20" />
                </div>
              </div>
            </div>

            <!-- MAINTENANCE TAB -->
            <div v-show="activeTab === 'maintenance'" class="space-y-5 sm:space-y-6">
              <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Maintenance Mode</h2>

              <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/20 text-sm text-amber-300">
                ⚠️ Use these settings carefully. Enabling them will affect all users of the merchant app.
              </div>

              <!-- Login Block -->
              <div class="flex items-center justify-between gap-4 p-5 bg-black/20 rounded-xl border border-white/10">
                <div>
                  <p class="text-sm font-bold text-white">Block Merchant Login</p>
                  <p class="text-xs text-gray-400 mt-1">When enabled, all merchants will be unable to log in to the app. Show a maintenance message instead.</p>
                </div>
                <button
                  type="button"
                  @click="form.maintenance_mode_enabled = form.maintenance_mode_enabled === '1' ? '0' : '1'"
                  :class="form.maintenance_mode_enabled === '1' ? 'bg-red-500' : 'bg-white/10'"
                  class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none"
                >
                  <span
                    :class="form.maintenance_mode_enabled === '1' ? 'translate-x-5' : 'translate-x-1'"
                    class="inline-block h-5 w-5 mt-1 transform rounded-full bg-white shadow transition-transform duration-200"
                  />
                </button>
              </div>

              <!-- Registration Block -->
              <div class="flex items-center justify-between gap-4 p-5 bg-black/20 rounded-xl border border-white/10">
                <div>
                  <p class="text-sm font-bold text-white">Disable New Registrations</p>
                  <p class="text-xs text-gray-400 mt-1">When enabled, new merchants will be unable to create an account. Existing users can still log in.</p>
                </div>
                <button
                  type="button"
                  @click="form.maintenance_disable_register = form.maintenance_disable_register === '1' ? '0' : '1'"
                  :class="form.maintenance_disable_register === '1' ? 'bg-red-500' : 'bg-white/10'"
                  class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none"
                >
                  <span
                    :class="form.maintenance_disable_register === '1' ? 'translate-x-5' : 'translate-x-1'"
                    class="inline-block h-5 w-5 mt-1 transform rounded-full bg-white shadow transition-transform duration-200"
                  />
                </button>
              </div>

              <!-- Status Summary -->
              <div class="p-4 rounded-xl bg-black/20 border border-white/10">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Current Status</p>
                <div class="space-y-2">
                  <div class="flex items-center gap-2 text-sm">
                    <span :class="form.maintenance_mode_enabled === '1' ? 'bg-red-500' : 'bg-green-500'" class="w-2 h-2 rounded-full"></span>
                    <span class="text-gray-300">Login: <span :class="form.maintenance_mode_enabled === '1' ? 'text-red-400 font-bold' : 'text-green-400 font-bold'">{{ form.maintenance_mode_enabled === '1' ? 'BLOCKED' : 'Open' }}</span></span>
                  </div>
                  <div class="flex items-center gap-2 text-sm">
                    <span :class="form.maintenance_disable_register === '1' ? 'bg-red-500' : 'bg-green-500'" class="w-2 h-2 rounded-full"></span>
                    <span class="text-gray-300">Registrations: <span :class="form.maintenance_disable_register === '1' ? 'text-red-400 font-bold' : 'text-green-400 font-bold'">{{ form.maintenance_disable_register === '1' ? 'DISABLED' : 'Open' }}</span></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Global Submit -->
            <div class="pt-6 border-t border-white/10">
              <button type="submit" :disabled="form.processing" class="btn-primary w-full sm:w-auto px-8 py-3">
                {{ form.processing ? 'Saving...' : 'Save All Settings' }}
              </button>
            </div>
            
          </form>
        </div>
      </div>
      
    </div>
  </AdminLayout>
</template>
