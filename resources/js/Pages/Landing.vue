<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

const props = defineProps({ plans: Array, settings: Object, themes: Array })

const platformName = computed(() => props.settings?.brand_platform_name || 'AsaanDukan')
const primaryColor  = computed(() => props.settings?.brand_primary_color  || '#1F7A4D')
const s = computed(() => props.settings || {})
const getJson = (key, def) => { 
  if (!s.value[key]) return def;
  try { 
    const parsed = Array.isArray(s.value[key]) ? s.value[key] : JSON.parse(s.value[key]);
    return parsed || def;
  } catch { 
    return def;
  } 
}

// Detect Urdu/Arabic script characters
const URDU_RE = /[\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF]+/g

// Check if text is predominantly Urdu (>50% Urdu characters, ignoring spaces/HTML)
const isPredomUrdu = (text) => {
  const stripped = text.replace(/<[^>]*>/g, '').replace(/\s+/g, '')
  const urduChars = (stripped.match(/[\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF]/g) || []).length
  return stripped.length > 0 && urduChars / stripped.length > 0.5
}

/**
 * renderHtml(text, fallback)
 * - Uses the provided text (or fallback if empty)
 * - Wraps any Urdu/Arabic script runs in <span class="urdu"> for Nastaliq font + RTL
 * - If the whole text is predominantly Urdu, wraps it in .urdu-block for full RTL alignment
 * - Returns raw HTML string — use with v-html
 * - Also allows HTML tags like <br>, <strong>, <em> in the text
 */
const renderHtml = (text, fallback = '') => {
  const raw = text || fallback
  if (!raw) return ''
  const processed = raw.replace(URDU_RE, match => `<span class="urdu">${match}</span>`)
  if (isPredomUrdu(raw)) {
    return `<span class="urdu-block">${processed}</span>`
  }
  return processed
}

const mobileMenuOpen = ref(false)
const billingCycle   = ref('monthly')
const isScrolled     = ref(false)
const testimonialIdx = ref(0)

const menuLinks = computed(() => getJson('landing_menu', [
  { title: 'Features',     url: '#features' },
  { title: 'How it Works', url: '#how-it-works' },
  { title: 'Themes',       url: '#themes' },
  { title: 'Pricing',      url: '#pricing' },
  { title: 'FAQ',          url: '#faq' },
]))

const ICONS = { MessageCircle:'💬', Palette:'🎨', ClipboardList:'📋', Link:'🔗', Shield:'🛡️', BarChart:'📊', Lock:'🔒', Sparkle:'✨' }
const iconFor = k => ICONS[k] || '✨'

const steps = computed(() => getJson('landing_steps_json', [
  { title: 'Create Your Account',   text: 'Sign up with your shop name and WhatsApp number — no paperwork, no waiting.' },
  { title: 'Set Up Your Dukan',     text: 'Add products, pick a theme that suits your shop and set your delivery areas.' },
  { title: 'Start Taking Orders',   text: 'Share your store link anywhere and orders start landing straight on WhatsApp.' },
]))

const features = computed(() => getJson('landing_features_json', [
  { title: 'WhatsApp Orders',      text: 'Every order lands straight in your WhatsApp — no extra app needed.',          icon: 'MessageCircle' },
  { title: 'Beautiful Themes',     text: 'Fashion, electronics, decor — pick a storefront that fits your brand.',        icon: 'Palette' },
  { title: 'Order Dashboard',      text: 'Every order, customer, and payment in one clean dashboard.',                   icon: 'ClipboardList' },
  { title: 'Your Own Store Link',  text: 'A shareable link customers can bookmark and return to anytime.',               icon: 'Link' },
]))

const analyticsBullets = computed(() => getJson('landing_analytics_bullets_json', [
  'One dashboard for every order and customer',
  'Daily sales totals without touching a calculator',
  'Know your best-selling products at a glance',
]))

const notificationBullets = computed(() => getJson('landing_notifications_bullets_json', [
  'Instant order pings on WhatsApp',
  'Customer name, items and total in one message',
  'Works with the number you already use',
  'Optional updates for your customers too',
]))

const stats = computed(() => getJson('landing_stats_json', [
  { value: '2,400+', label: 'Dukaans Launched' },
  { value: '18',     label: 'Cities in Pakistan' },
  { value: '60k+',   label: 'Orders Monthly' },
  { value: '4.8★',   label: 'Merchant Rating' },
]))

const faq = ref(getJson('landing_faq_json', [
  { question: 'Do I need my own domain or hosting?',  answer: 'No! Your store is hosted on our platform immediately. On upgraded plans you can use a free subdomain or your own custom domain.' },
  { question: 'How do customers place orders?',        answer: 'Customers browse your store, add to cart, and checkout. The order is automatically sent to your WhatsApp number.' },
  { question: 'Can I change my theme later?',          answer: 'Yes, themes can be changed later depending on your plan.' },
  { question: 'Is there a setup fee?',                 answer: 'No setup fee — your 7-day free trial starts the moment you register. No credit card required.' },
]).map(f => ({ ...f, open: false })))

const testimonials = computed(() => getJson('landing_testimonials_json', [
  { name: 'Ayesha', shop: 'Noor Boutique',      rating: 5, text: 'Orders come straight to my WhatsApp now — I never miss a sale anymore.' },
  { name: 'Bilal',  shop: 'Bilal Electronics',  rating: 5, text: 'Set up my store in an afternoon. Customers love how easy it is to order.' },
  { name: 'Sana',   shop: 'Home & Decor Co.',   rating: 5, text: 'The themes actually look like my shop, not some generic template.' },
]))
const activeTm = computed(() => testimonials.value[testimonialIdx.value] || testimonials.value[0])
const nextTm = () => testimonialIdx.value = (testimonialIdx.value + 1) % testimonials.value.length
const prevTm = () => testimonialIdx.value = (testimonialIdx.value - 1 + testimonials.value.length) % testimonials.value.length

/* ── Plan features ── */
const FEATURE_DEFS = [
  { key: 'product_limit',                   icon: '📦', label: 'Products',                    fmt: 'num' },
  { key: 'order_limit_monthly',             icon: '📝', label: 'Orders / month',               fmt: 'num' },
  { key: 'staff_logins_limit',              icon: '👥', label: 'Staff Logins',                 fmt: 'num' },
  { key: 'message_quota_monthly',           icon: '💬', label: 'WhatsApp Messages / mo',        fmt: 'num' },
  { key: 'analytics_enabled',               icon: '📊', label: 'Analytics Dashboard',           fmt: 'bool' },
  { key: 'customer_whatsapp_notifications', icon: '🔔', label: 'Customer Notifications',        fmt: 'bool' },
  { key: 'subdomain_allowed',               icon: '🌐', label: 'Free Subdomain',               fmt: 'bool' },
  { key: 'custom_domain_allowed',           icon: '🔗', label: 'Custom Domain',                fmt: 'bool' },
  { key: 'premium_themes_allowed',          icon: '🎨', label: 'Premium Themes',               fmt: 'bool' },
  { key: 'theme_change_allowed',            icon: '🖌️', label: 'Theme Changes',                fmt: 'num_or_bool' },
  { key: 'customer_crm',                    icon: '🗂️', label: 'Customer CRM',                 fmt: 'bool' },
  { key: 'bulk_import_export',              icon: '⬆️', label: 'Bulk Import / Export',          fmt: 'bool' },
  { key: 'store_info_change_allowed',       icon: '✏️', label: 'Store Info Edits',              fmt: 'num_or_bool' },
]
const getFeatVal = (plan, key) => plan.features?.find(f => f.feature_key === key)?.feature_value ?? null
const fmtFeat = (val, fmt) => {
  if (!val && val !== '0') return null
  if (val === 'unlimited') return { t: 'text', v: '∞' }
  if (fmt === 'bool')        return { t: val === '1' ? 'yes' : 'no' }
  if (fmt === 'num')         return val === '0' ? { t: 'no' } : { t: 'text', v: val }
  if (fmt === 'num_or_bool') return val === '0' ? { t: 'no' } : val === '1' ? { t: 'yes' } : { t: 'text', v: val }
  return { t: 'text', v: val }
}

const resolveImage = key => s.value[key] ? `/storage/${s.value[key]}` : null
const newsletterEmail = ref('')
const subscribed = ref(false)
const subscribeNewsletter = () => { if (newsletterEmail.value) subscribed.value = true }
const whatsappHref = computed(() => `https://wa.me/${(s.value.landing_contact_phone || '+923001234567').replace(/\D/g, '')}`)

onMounted(() => {
  const obs = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && e.target.classList.add('in')),
    { threshold: 0.08, rootMargin: '0px 0px -40px 0px' })
  document.querySelectorAll('.sa').forEach(el => obs.observe(el))
  window.addEventListener('scroll', () => isScrolled.value = window.scrollY > 24, { passive: true })
})
</script>

<template>
  <Head :title="s.seo_meta_title || platformName" />

  <div class="page-root">

    <!-- ══════════ TOPBAR ══════════ -->
    <div class="topbar">
      <span class="topbar-dot"></span>
      <span v-html="renderHtml(s.landing_topbar_text, '🎉 7-day free trial — no credit card required. Start selling today!')"></span>
    </div>

    <!-- ══════════ NAVBAR ══════════ -->
    <nav :class="['navbar', isScrolled && 'navbar--scrolled']">
      <div class="container navbar__inner">
        <!-- Logo -->
        <a href="/" class="logo">
          <img v-if="s.brand_logo" :src="`/storage/${s.brand_logo}`" class="logo__img" />
          <span v-else class="logo__text">
            <span class="logo__mark">✦</span>{{ platformName }}
          </span>
        </a>

        <!-- Desktop links -->
        <div class="navbar__links">
          <a v-for="l in menuLinks" :key="l.title" :href="l.url" class="navbar__link">{{ l.title }}</a>
        </div>

        <!-- CTA -->
        <div class="navbar__actions">
          <a href="https://merchant.asaandukan.online/" class="btn-ghost-sm">Sign In</a>
          <a href="https://merchant.asaandukan.online/" class="btn-pill">
            Get Started <span class="btn-arrow">→</span>
          </a>
        </div>

        <!-- Hamburger -->
        <button class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>

    <!-- Mobile overlay -->
    <div :class="['mobile-menu', mobileMenuOpen && 'mobile-menu--open']">
      <a v-for="l in menuLinks" :key="l.title" :href="l.url" @click="mobileMenuOpen=false" class="mobile-menu__link">{{ l.title }}</a>
      <a href="https://merchant.asaandukan.online/" class="btn-pill mt-6 justify-center">Get Started →</a>
    </div>

    <!-- ══════════ HERO ══════════ -->
    <section v-if="s.landing_show_hero !== '0'" class="hero">
      <!-- Background decoration -->
      <div class="hero__bg" aria-hidden="true">
        <div class="glow glow--teal"></div>
        <div class="glow glow--amber"></div>
        <div class="hero__grid"></div>
        <!-- Animated rings -->
        <div class="ring ring-1"></div>
        <div class="ring ring-2"></div>
        <div class="ring ring-3"></div>
      </div>

      <div class="container hero__inner">
        <!-- Left: Copy -->
        <div class="hero__copy sa fade-up">
          <div class="hero__badge">
            <span class="badge-pulse"></span>
            <span v-html="renderHtml(s.landing_hero_offer, 'New — WhatsApp Order Alerts')"></span>
          </div>

          <h1 class="hero__h1" v-html="renderHtml(
            (s.landing_hero_headline || 'Asaan Dukan,') + '<br>' +
            '<span class=\'gradient-text\'>' + (s.landing_hero_headline_end || 'Asaan Zindagi') + '</span>'
          )"></h1>

          <p class="hero__sub" v-html="renderHtml(s.landing_hero_subheadline, 'Sirf kuch minutes mein apna store tayar karein — no coding, no hassle. Orders seedha WhatsApp par.')"></p>

          <div class="hero__ctas">
            <a href="https://merchant.asaandukan.online/" class="btn-primary-lg">
              {{ s.landing_hero_btn1_text || 'Start Free Trial' }}
              <span class="btn-arrow">→</span>
            </a>
            <a href="#how-it-works" class="btn-outline-lg">
              {{ s.landing_hero_btn2_text || 'See How It Works' }}
            </a>
          </div>

          <div class="hero__proof">
            <div class="avatar-stack">
              <div v-for="(l,i) in ['A','B','S','R']" :key="i" class="avatar" :style="`--i:${i}`">{{l}}</div>
            </div>
            <p class="proof-text">
              Joined by <strong>2,400+ merchants</strong> across Pakistan
            </p>
          </div>
        </div>

        <!-- Right: Mockup (3 Phones) -->
        <div class="hero__visual sa fade-left">
          <div class="mockup-wrap">
            <div class="mockup-glow"></div>
            
            <div class="hero-phones">
              <div class="phone-wrapper phone-left">
                <div class="phone-frame">
                  <div class="phone-frame__notch"></div>
                  <img :src="resolveImage('landing_hero_image_1') || '/storage/platform/mockup_hero.png'" class="phone-frame__img" alt="App Screen 1" />
                </div>
              </div>

              <div class="phone-wrapper phone-center">
                <div class="phone-frame">
                  <div class="phone-frame__notch"></div>
                  <img :src="resolveImage('landing_hero_image_2') || '/storage/platform/mockup_hero.png'" class="phone-frame__img" alt="App Screen 2" />
                </div>
              </div>

              <div class="phone-wrapper phone-right">
                <div class="phone-frame">
                  <div class="phone-frame__notch"></div>
                  <img :src="resolveImage('landing_hero_image_3') || '/storage/platform/mockup_hero.png'" class="phone-frame__img" alt="App Screen 3" />
                </div>
              </div>
            </div>
            
            <!-- Floating cards -->
            <div class="float-card float-card--top-left sa fade-up" style="--d:.4s">
              <span class="fc-icon">📦</span>
              <div><p class="fc-label">New Order</p><p class="fc-val">Rs. 2,400</p></div>
            </div>
            <div class="float-card float-card--bottom-right sa fade-up" style="--d:.6s">
              <span class="fc-icon">✅</span>
              <div><p class="fc-label">Order Confirmed</p><p class="fc-val">WhatsApp sent</p></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ TRUSTED BY ══════════ -->
    <section v-if="s.landing_show_trusted !== '0'" class="trusted">
      <div class="container">
        <p class="trusted__label" v-html="renderHtml(s.landing_trusted_title, 'Trusted by shopkeepers across Pakistan')"></p>
        <div class="trusted__logos">
          <div v-for="(c,i) in ['Karachi','Lahore','Islamabad','Faisalabad','Multan','Peshawar']" :key="i" class="trusted__chip">{{ c }}</div>
        </div>
      </div>
    </section>

    <!-- ══════════ HOW IT WORKS ══════════ -->
    <section v-if="s.landing_show_steps !== '0'" id="how-it-works" class="section">
      <div class="container">
        <div class="section__head sa fade-up">
          <div class="label-badge">How It Works</div>
          <h2 class="section__title" v-html="renderHtml(s.landing_steps_title, 'Open your dukan in three steps')"></h2>
        </div>

        <div class="steps-grid">
          <div v-for="(step, i) in steps" :key="i"
               class="step-card sa fade-up"
               :style="`--d:${i * 100}ms`">
            <div class="step-card__num">{{ String(i+1).padStart(2,'0') }}</div>
            <div class="step-card__connector" v-if="i < steps.length - 1"></div>
            <h3 class="step-card__title" v-html="renderHtml(step.title)"></h3>
            <p class="step-card__text" v-html="renderHtml(step.text)"></p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FEATURES ══════════ -->
    <section v-if="s.landing_show_features !== '0'" id="features" class="section section--alt">
      <div class="container">
        <div class="section__head sa fade-up">
          <div class="label-badge">Features</div>
          <h2 class="section__title" v-html="renderHtml(s.landing_features_title, 'Everything your dukan needs')"></h2>
        </div>

        <div class="features-grid">
          <div v-for="(feat, i) in features" :key="i"
               class="feat-card sa fade-up"
               :style="`--d:${i * 80}ms`">
            <div class="feat-card__icon">{{ iconFor(feat.icon) }}</div>
            <h3 class="feat-card__title" v-html="renderHtml(feat.title)"></h3>
            <p class="feat-card__text" v-html="renderHtml(feat.text)"></p>
            <div class="feat-card__glow"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ ANALYTICS ══════════ -->
    <section v-if="s.landing_show_analytics !== '0'" class="section">
      <div class="container split">
        <div class="split__visual sa fade-right">
          <div class="phone-frame">
            <div class="phone-frame__notch"></div>
            <img :src="resolveImage('landing_analytics_image') || '/storage/platform/mockup_analytics.png'"
                 class="phone-frame__img" alt="Analytics" />
          </div>
        </div>
        <div class="split__copy sa fade-left">
          <div class="label-badge">Dashboard</div>
          <h2 class="split__title" v-html="renderHtml(s.landing_analytics_title, 'One Dashboard For Your Whole Dukan')"></h2>
          <p class="split__body" v-html="renderHtml(s.landing_analytics_text, 'Every order, product and customer, tracked in one place — check from your phone between customers.')"></p>
          <ul class="check-list">
            <li v-for="(b, i) in analyticsBullets" :key="i">
              <span class="check-list__icon">✓</span>{{ b }}
            </li>
          </ul>
          <a href="https://merchant.asaandukan.online/" class="btn-primary-lg mt-6">Start Free Trial →</a>
        </div>
      </div>
    </section>

    <!-- ══════════ ORDER ALERTS ══════════ -->
    <section v-if="s.landing_show_notifications !== '0'" class="section section--alt">
      <div class="container split split--flip">
        <div class="split__copy sa fade-right">
          <div class="label-badge label-badge--green">Order Alerts</div>
          <h2 class="split__title" v-html="renderHtml(s.landing_notifications_title, 'Get Order Alerts Straight To WhatsApp')"></h2>
          <p class="split__body" v-html="renderHtml(s.landing_notifications_text, 'The moment a customer checks out, you get a WhatsApp message — no dashboard refreshing required.')"></p>
          <ul class="check-list check-list--green">
            <li v-for="(b,i) in notificationBullets" :key="i">
              <span class="check-list__icon">✓</span>{{ b }}
            </li>
          </ul>
          <a :href="whatsappHref" target="_blank" rel="noopener" class="btn-wa mt-6">💬 Chat on WhatsApp</a>
        </div>
        <div class="split__visual sa fade-left">
          <div class="phone-frame">
            <div class="phone-frame__notch"></div>
            <img :src="resolveImage('landing_notifications_image') || '/storage/platform/mockup_notifications.png'"
                 class="phone-frame__img" alt="Notifications" />
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ STATS BANNER ══════════ -->
    <section v-if="s.landing_show_stats !== '0'" class="stats-section">
      <div class="container">
        <div class="stats-band sa fade-up">
          <div v-for="(stat, i) in stats" :key="i" class="stat-item">
            <div class="stat-item__value" v-html="renderHtml(stat.value)"></div>
            <div class="stat-item__label" v-html="renderHtml(stat.label)"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ THEMES ══════════ -->
    <section v-if="s.landing_show_use_cases !== '0'" id="themes" class="section">
      <div class="container">
        <div class="section__head sa fade-up">
          <div class="label-badge">Themes</div>
          <h2 class="section__title" v-html="renderHtml(s.landing_use_cases_title, 'A storefront for every kind of shop')"></h2>
          <p class="section__sub">Pick a beautiful theme built for your product category and go live in minutes.</p>
        </div>

        <div class="themes-grid">
          <div v-for="(theme, i) in themes" :key="i"
               class="theme-card sa fade-up"
               :style="`--d:${i * 70}ms`">
            <div class="theme-card__window">
              <div class="theme-card__chrome">
                <span class="dot dot--red"></span>
                <span class="dot dot--yellow"></span>
                <span class="dot dot--green"></span>
              </div>
              <div class="theme-card__preview group">
                <img v-if="theme.screenshot_path"
                     :src="`/storage/${theme.screenshot_path}`"
                     class="theme-card__img group-hover:translate-y-[-60%]" />
                <div v-else class="theme-card__placeholder">{{ theme.name }}</div>
              </div>
            </div>
            <div class="theme-card__foot">
              <div>
                <p class="theme-card__name">{{ theme.name }}</p>
                <p class="theme-card__slug">/{{ theme.slug }}</p>
              </div>
              <a v-if="theme.demo_url" :href="theme.demo_url" class="btn-preview">Preview →</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ TESTIMONIALS ══════════ -->
    <section v-if="s.landing_show_testimonials !== '0' && testimonials.length" class="section section--alt">
      <div class="container">
        <div class="section__head sa fade-up">
          <div class="label-badge">Testimonials</div>
          <h2 class="section__title" v-html="renderHtml(s.landing_testimonials_title, 'What our shopkeepers say')"></h2>
        </div>

        <div class="testimonial-track sa fade-up">
          <div v-for="(t, i) in testimonials" :key="i"
               class="testimonial-card"
               :class="i === testimonialIdx ? 'testimonial-card--active' : ''">
            <div class="tc-stars">
              <span v-for="n in t.rating" :key="n">★</span>
            </div>
            <p class="tc-quote" v-html="'&ldquo;' + renderHtml(t.text) + '&rdquo;'"></p>
            <div class="tc-author">
              <div class="tc-avatar">{{ t.name?.charAt(0) }}</div>
              <div>
                <p class="tc-name" v-html="renderHtml(t.name)"></p>
                <p class="tc-shop" v-html="renderHtml(t.shop)"></p>
              </div>
            </div>
          </div>
        </div>

        <div class="testimonial-nav sa fade-up">
          <button class="tn-btn" @click="prevTm">‹</button>
          <button v-for="(_, i) in testimonials" :key="i"
                  class="tn-dot"
                  :class="i === testimonialIdx ? 'tn-dot--active' : ''"
                  @click="testimonialIdx = i"></button>
          <button class="tn-btn" @click="nextTm">›</button>
        </div>
      </div>
    </section>

    <!-- ══════════ PRICING ══════════ -->
    <section v-if="s.landing_show_pricing !== '0'" id="pricing" class="section">
      <div class="container">
        <div class="section__head sa fade-up">
          <div class="label-badge">Pricing</div>
          <h2 class="section__title" v-html="renderHtml(s.landing_pricing_title, 'Simple, honest pricing')"></h2>
          <p class="section__sub">Start free. Scale as you grow.</p>

          <div class="billing-toggle">
            <button :class="['bt-btn', billingCycle === 'monthly' && 'bt-btn--active']" @click="billingCycle='monthly'">Monthly</button>
            <button :class="['bt-btn', billingCycle === 'yearly' && 'bt-btn--active']" @click="billingCycle='yearly'">
              Yearly <span class="bt-save">–15%</span>
            </button>
          </div>
        </div>

        <div class="pricing-grid">
          <div v-for="(plan, i) in plans" :key="plan.id"
               class="price-card sa fade-up"
               :class="plan.slug === 'growth' ? 'price-card--featured' : ''"
               :style="`--d:${i * 100}ms`">

            <div v-if="plan.slug === 'growth'" class="price-card__badge">Most Popular</div>

            <div class="price-card__header">
              <h3 class="price-card__name">{{ plan.name }}</h3>
              <div class="price-card__price">
                <span class="price-card__amount">Rs.{{ Number(billingCycle === 'monthly' ? plan.price_monthly : plan.price_yearly / 12).toLocaleString() }}</span>
                <span class="price-card__period">/ month</span>
              </div>
              <p v-if="plan.description" class="price-card__desc">{{ plan.description }}</p>
            </div>

            <div class="price-card__divider"></div>

            <ul class="price-feat-list">
              <li v-for="def in FEATURE_DEFS" :key="def.key" class="pfl-item">
                <span class="pfl-label"><span class="pfl-icon">{{ def.icon }}</span>{{ def.label }}</span>
                <span class="pfl-val">
                  <template v-if="!fmtFeat(getFeatVal(plan, def.key), def.fmt)">
                    <span class="pfl-dash">—</span>
                  </template>
                  <template v-else-if="fmtFeat(getFeatVal(plan, def.key), def.fmt).t === 'yes'">
                    <span class="pfl-yes">✓</span>
                  </template>
                  <template v-else-if="fmtFeat(getFeatVal(plan, def.key), def.fmt).t === 'no'">
                    <span class="pfl-no">✕</span>
                  </template>
                  <template v-else>
                    <span class="pfl-num">{{ fmtFeat(getFeatVal(plan, def.key), def.fmt).v }}</span>
                  </template>
                </span>
              </li>
            </ul>

            <div class="price-card__divider"></div>

            <a href="https://merchant.asaandukan.online/"
               :class="['price-card__cta', plan.slug === 'growth' ? 'btn-primary-lg' : 'btn-outline-lg']">
              Start Free Trial
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FAQ ══════════ -->
    <section v-if="s.landing_show_faq !== '0'" id="faq" class="section section--alt">
      <div class="container">
        <div class="faq-layout">
          <div class="faq-left sa fade-right">
            <div class="label-badge">FAQ</div>
            <h2 class="section__title mt-4" v-html="renderHtml(s.landing_faq_title, 'Frequently asked questions')"></h2>
            <p class="split__body">Can't find your answer? <a :href="whatsappHref" class="link-green">Chat with us on WhatsApp →</a></p>
          </div>
          <div class="faq-right sa fade-left">
            <div v-for="(item, i) in faq" :key="i" class="faq-item">
              <button @click="item.open = !item.open" class="faq-item__trigger">
                <span>{{ item.question || item.q }}</span>
                <span :class="['faq-item__icon', item.open && 'faq-item__icon--open']">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
              </button>
              <div v-show="item.open" class="faq-item__body" v-html="renderHtml(item.answer || item.a)"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FINAL CTA ══════════ -->
    <section class="cta-section">
      <div class="cta-glow cta-glow--left" aria-hidden="true"></div>
      <div class="cta-glow cta-glow--right" aria-hidden="true"></div>
      <div class="container cta-inner sa fade-up">
        <div class="label-badge label-badge--light mx-auto">Get Started Today</div>
        <h2 class="cta-title" v-html="renderHtml(s.landing_cta_title, 'Ready to open your dukan tonight?')"></h2>
        <p class="cta-body" v-html="renderHtml(s.landing_cta_text, 'No coding, no credit card. Set up your store and share your link before the shop even closes.')"></p>
        <div class="cta-btns">
          <a href="https://merchant.asaandukan.online/" class="btn-primary-xl">
            Start Free Trial <span class="btn-arrow">→</span>
          </a>
          <a :href="whatsappHref" target="_blank" rel="noopener" class="btn-wa-xl">
            💬 Chat on WhatsApp
          </a>
        </div>
      </div>
    </section>

    <!-- ══════════ NEWSLETTER ══════════ -->
    <section v-if="s.landing_show_newsletter !== '0'" class="newsletter-section">
      <div class="container">
        <div class="newsletter-card sa fade-up">
          <div class="newsletter-card__copy">
            <h3 class="newsletter-card__title" v-html="renderHtml(s.landing_newsletter_title, 'Subscribe For Dukan Tips')"></h3>
            <p class="newsletter-card__sub" v-html="renderHtml(s.landing_newsletter_text, 'One email a month — new themes, features and merchant stories. No spam.')"></p>
          </div>
          <form v-if="!subscribed" @submit.prevent="subscribeNewsletter" class="newsletter-card__form">
            <input v-model="newsletterEmail" type="email" required placeholder="you@yourshop.com" class="nl-input" />
            <button type="submit" class="btn-primary-lg">Subscribe</button>
          </form>
          <p v-else class="newsletter-card__thanks">✓ You're on the list — shukriya! 🎉</p>
        </div>
      </div>
    </section>

    <!-- ══════════ FOOTER ══════════ -->
    <footer class="footer">
      <div class="container footer__inner">
        <div class="footer__brand">
          <div class="logo">
            <span class="logo__mark">✦</span>
            <span class="logo__text">{{ platformName }}</span>
          </div>
          <p class="footer__tagline">{{ s.landing_hero_subheadline || 'Building the future of commerce for Pakistani merchants.' }}</p>
          <div class="footer__contacts">
            <span>📧 {{ s.landing_contact_email || 'support@asaandukan.online' }}</span>
            <span>📱 {{ s.landing_contact_phone || '+92 300 1234567' }}</span>
          </div>
        </div>

        <div class="footer__cols">
          <div class="footer__col">
            <h4 class="footer__col-title">Product</h4>
            <ul class="footer__links">
              <li v-for="l in menuLinks" :key="l.title"><a :href="l.url">{{ l.title }}</a></li>
            </ul>
          </div>
          <div class="footer__col">
            <h4 class="footer__col-title">Legal</h4>
            <ul class="footer__links">
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Terms of Service</a></li>
              <li><a href="#">Refund Policy</a></li>
            </ul>
          </div>
          <div class="footer__col">
            <h4 class="footer__col-title">Get Started</h4>
            <p class="footer__col-body">Join thousands of merchants already selling online.</p>
            <a href="https://merchant.asaandukan.online/" class="btn-pill mt-3 inline-flex">Create Store →</a>
          </div>
        </div>
      </div>

      <div class="footer__bottom">
        <div class="container footer__bottom-inner">
          <span v-html="s.landing_footer_text || `© 2026 ${platformName}. All rights reserved.`"></span>
          <div class="footer__socials">
            <a v-for="s in ['Twitter','LinkedIn','Instagram']" :key="s" href="#">{{ s }}</a>
          </div>
        </div>
      </div>
    </footer>

  </div><!-- /page-root -->
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600;700&display=swap');

/* Urdu Nastaliq auto-font — inline within mixed text */
.urdu {
  font-family: 'Noto Nastaliq Urdu', serif;
  line-height: 2.2;
  direction: rtl;
  unicode-bidi: embed;
  display: inline-block;
}

/* Urdu Nastaliq — full block when entire content is Urdu */
.urdu-block {
  font-family: 'Noto Nastaliq Urdu', serif;
  line-height: 2.2;
  direction: rtl;
  unicode-bidi: embed;
  display: block;
  text-align: right;
  width: 100%;
}
/* ══════════════════════════════════════
   TOKENS
══════════════════════════════════════ */
html {
  scroll-behavior: smooth;
}
:root {
  --bg:          #0B0E1A;
  --bg-alt:      #0F1221;
  --surface:     #141828;
  --surface-2:   #1a1f35;
  --border:      rgba(255,255,255,0.07);
  --border-2:    rgba(255,255,255,0.12);
  --green:       #1F7A4D;
  --green-light: #4ade80;
  --green-glow:  rgba(31,122,77,0.18);
  --amber:       #E8A23D;
  --amber-glow:  rgba(232,162,61,0.12);
  --text-1:      #FFFFFF;
  --text-2:      rgba(255,255,255,0.55);
  --text-3:      rgba(255,255,255,0.30);
  --radius-sm:   10px;
  --radius-md:   16px;
  --radius-lg:   24px;
  --radius-xl:   32px;
  --shadow-card: 0 4px 24px rgba(0,0,0,0.35), 0 1px 0 rgba(255,255,255,0.04) inset;
  --shadow-glow-g: 0 0 40px rgba(31,122,77,0.25);
}

html { scroll-behavior: smooth; }
*, *::before, *::after { box-sizing: border-box; margin: 0; }

/* ══════ PAGE ROOT ══════ */
.page-root {
  background: var(--bg);
  color: var(--text-1);
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow-x: hidden;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* ══════ TOPBAR ══════ */
.topbar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  font-size: 12px;
  font-family: 'IBM Plex Mono', monospace;
  letter-spacing: .04em;
  background: linear-gradient(90deg, rgba(31,122,77,0.8) 0%, rgba(31,122,77,1) 50%, rgba(31,122,77,0.8) 100%);
  color: rgba(255,255,255,0.9);
}
.topbar-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 6px #4ade80;
  animation: blink 1.6s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:.3} 50%{opacity:1} }

/* ══════ NAVBAR ══════ */
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  transition: background .3s, border-color .3s, backdrop-filter .3s;
  border-bottom: 1px solid transparent;
}
.navbar--scrolled {
  background: rgba(11,14,26,0.82);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-color: var(--border);
}
.navbar__inner {
  display: flex;
  align-items: center;
  height: 64px;
  gap: 24px;
}
.navbar__links {
  display: none;
  gap: 4px;
  flex: 1;
  justify-content: center;
}
@media (min-width:768px) { .navbar__links { display: flex; } }
.navbar__link {
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-2);
  border-radius: 8px;
  text-decoration: none;
  transition: color .2s, background .2s;
}
.navbar__link:hover { color: var(--text-1); background: rgba(255,255,255,0.05); }
.navbar__actions { display: none; gap: 10px; align-items: center; }
@media (min-width:768px) { .navbar__actions { display: flex; } }

/* ══════ LOGO ══════ */
.logo {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  flex-shrink: 0;
}
.logo__img  { height: 28px; }
.logo__mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px; height: 26px;
  border-radius: 7px;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  font-size: 12px;
  color: white;
}
.logo__text {
  font-family: 'Fraunces', Georgia, serif;
  font-size: 20px;
  font-weight: 800;
  color: white;
  display: flex;
  align-items: center;
  gap: 7px;
}

/* ══════ HAMBURGER ══════ */
.hamburger {
  display: flex;
  flex-direction: column;
  gap: 5px;
  padding: 8px;
  border: none;
  background: none;
  cursor: pointer;
  margin-left: auto;
}
@media (min-width:768px) { .hamburger { display: none; } }
.hamburger span {
  display: block; width: 22px; height: 2px;
  background: rgba(255,255,255,0.7); border-radius: 2px;
}

/* ══════ MOBILE MENU ══════ */
.mobile-menu {
  position: fixed;
  inset: 0;
  z-index: 90;
  background: rgba(11,14,26,0.97);
  backdrop-filter: blur(24px);
  display: flex;
  flex-direction: column;
  padding: 88px 32px 32px;
  transform: translateX(100%);
  transition: transform .35s cubic-bezier(0.4,0,0.2,1);
}
.mobile-menu--open { transform: translateX(0); }
.mobile-menu__link {
  padding: 18px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-2);
  border-bottom: 1px solid var(--border);
  text-decoration: none;
  transition: color .2s;
}
.mobile-menu__link:hover { color: white; }
.mt-6 { margin-top: 24px; }

/* ══════ BUTTONS ══════ */
.btn-primary-lg, .btn-outline-lg, .btn-primary-xl, .btn-wa-xl,
.btn-pill, .btn-ghost-sm, .btn-wa, .btn-preview {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  border-radius: 10px;
  text-decoration: none;
  cursor: pointer;
  transition: all .2s ease;
  border: none;
  white-space: nowrap;
}

.btn-primary-lg {
  padding: 13px 24px;
  font-size: 14px;
  color: white;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  box-shadow: 0 0 0 1px rgba(31,122,77,0.5), 0 6px 20px rgba(31,122,77,0.3);
}
.btn-primary-lg:hover { transform: translateY(-2px); box-shadow: 0 0 0 1px rgba(31,122,77,0.6), 0 10px 30px rgba(31,122,77,0.4); }

.btn-outline-lg {
  padding: 12px 24px;
  font-size: 14px;
  color: var(--text-2);
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border-2);
}
.btn-outline-lg:hover { color: white; background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); }

.btn-primary-xl {
  padding: 16px 32px;
  font-size: 16px;
  color: white;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  box-shadow: 0 8px 30px rgba(31,122,77,0.4);
  border-radius: 12px;
}
.btn-primary-xl:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(31,122,77,0.5); }

.btn-wa-xl {
  padding: 16px 32px;
  font-size: 16px;
  color: white;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.14);
  border-radius: 12px;
}
.btn-wa-xl:hover { background: rgba(255,255,255,0.1); transform: translateY(-1px); }

.btn-pill {
  padding: 10px 20px;
  font-size: 14px;
  color: white;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  border-radius: 999px;
  box-shadow: 0 4px 15px rgba(31,122,77,0.3);
}
.btn-pill:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(31,122,77,0.4); }

.btn-ghost-sm {
  padding: 9px 16px;
  font-size: 13px;
  color: var(--text-2);
  background: none;
  border: 1px solid transparent;
  border-radius: 8px;
}
.btn-ghost-sm:hover { color: white; background: rgba(255,255,255,0.05); border-color: var(--border); }

.btn-wa {
  padding: 12px 22px;
  font-size: 14px;
  color: white;
  background: #128C7E;
  border-radius: 999px;
  box-shadow: 0 4px 15px rgba(18,140,126,0.35);
}
.btn-wa:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(18,140,126,0.5); }

.btn-preview {
  padding: 6px 14px;
  font-size: 11px;
  font-family: 'IBM Plex Mono', monospace;
  color: var(--green-light);
  background: rgba(31,122,77,0.1);
  border: 1px solid rgba(31,122,77,0.25);
  border-radius: 999px;
}
.btn-preview:hover { background: rgba(31,122,77,0.18); }

.btn-arrow { font-size: 16px; }

/* ══════ HERO ══════ */
.hero {
  position: relative;
  padding: 64px 0 80px;
  overflow: hidden;
}
@media (min-width:768px) { .hero { padding: 100px 0 120px; } }

.hero__bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
}
.glow--teal {
  top: -10%; right: -5%;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(31,122,77,0.20) 0%, transparent 70%);
}
.glow--amber {
  bottom: 0; left: -10%;
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(232,162,61,0.10) 0%, transparent 70%);
}

.hero__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 52px 52px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 40%, black 0%, transparent 100%);
}

.ring {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.04);
  animation: expand 8s linear infinite;
}
.ring-1 { width: 400px; height: 400px; animation-delay: 0s; }
.ring-2 { width: 700px; height: 700px; animation-delay: -2.6s; }
.ring-3 { width: 1000px; height: 1000px; animation-delay: -5.2s; }
@keyframes expand { from { opacity: 0.6; } to { opacity: 0; } }

.hero__inner {
  display: grid;
  gap: 48px;
  align-items: center;
  position: relative;
  z-index: 2;
}
@media (min-width:960px) { .hero__inner { grid-template-columns: 1fr 1fr; } }

.hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid rgba(31,122,77,0.35);
  background: rgba(31,122,77,0.1);
  color: var(--green-light);
  font-size: 11px;
  font-family: 'IBM Plex Mono', monospace;
  letter-spacing: .1em;
  text-transform: uppercase;
  width: fit-content;
}
.badge-pulse {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 6px var(--green-light);
  animation: blink 1.4s ease-in-out infinite;
}

.hero__h1 {
  font-family: 'Fraunces', Georgia, serif;
  font-size: clamp(2.6rem, 5.5vw, 4.5rem);
  font-weight: 900;
  line-height: 1.06;
  letter-spacing: -0.03em;
  color: white;
  margin-top: 20px;
}

.gradient-text {
  background: linear-gradient(135deg, var(--amber) 0%, #f5c85a 40%, var(--amber) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero__sub {
  margin-top: 20px;
  font-size: 17px;
  line-height: 1.7;
  color: var(--text-2);
  max-width: 480px;
}

.hero__ctas {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 32px;
}

.hero__proof {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 28px;
}
.avatar-stack { display: flex; }
.avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  border: 2px solid var(--bg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  background: linear-gradient(135deg, var(--green) calc(var(--i)*25%), var(--amber) 100%);
  margin-left: calc(var(--i) * -8px);
}
.proof-text { font-size: 12px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; }
.proof-text strong { color: var(--text-2); }

.hero__visual { display: flex; justify-content: center; }

.mockup-wrap {
  position: relative;
  display: inline-block;
}
.mockup-glow {
  position: absolute;
  inset: -10%;
  background: radial-gradient(circle, rgba(31,122,77,0.22) 0%, transparent 70%);
  filter: blur(40px);
  pointer-events: none;
  border-radius: 50%;
}
.hero__mockup {
  width: 100%;
  max-width: 520px;
  object-fit: contain;
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 30px 60px rgba(0,0,0,0.5));
}

/* 3 Phones Layout */
.hero-phones {
  position: relative;
  width: 100%;
  max-width: 540px;
  height: 580px; /* fixed height container to allow stacking */
  margin: 0 auto;
}

.phone-wrapper {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 240px;
  transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform-origin: center center;
  z-index: 2;
}

/* Base angles: 12 degrees */
.phone-left {
  transform: translate(-90%, -35%) rotate(-12deg) scale(0.85);
  z-index: 1;
  opacity: 0.8;
  animation: float-left 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.phone-center {
  transform: translate(-50%, -50%) rotate(12deg);
  z-index: 3;
  box-shadow: 0 40px 100px rgba(0,0,0,0.7);
  animation: float-center 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.phone-right {
  transform: translate(-10%, -65%) rotate(32deg) scale(0.85);
  z-index: 1;
  opacity: 0.8;
  animation: float-right 1.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes float-left {
  0% { transform: translate(-120%, -20%) rotate(-20deg) scale(0.8); opacity: 0; }
  100% { transform: translate(-105%, -40%) rotate(-12deg) scale(0.85); opacity: 0.85; }
}
@keyframes float-center {
  0% { transform: translate(-50%, -20%) rotate(0deg); opacity: 0; }
  100% { transform: translate(-50%, -50%) rotate(12deg); opacity: 1; }
}
@keyframes float-right {
  0% { transform: translate(10%, -40%) rotate(40deg) scale(0.8); opacity: 0; }
  100% { transform: translate(-5%, -60%) rotate(32deg) scale(0.85); opacity: 0.85; }
}

@media (max-width: 640px) {
  .hero-phones { height: 420px; }
  .phone-wrapper { width: 180px; }
}

/* Floating info cards */
.float-card {
  position: absolute;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 12px;
  background: rgba(20,24,40,0.9);
  border: 1px solid var(--border-2);
  backdrop-filter: blur(16px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.4);
  white-space: nowrap;
}
.float-card--top-left  { top: 12%; left: -8%; }
.float-card--bottom-right { bottom: 18%; right: -8%; }
.fc-icon  { font-size: 20px; }
.fc-label { font-size: 10px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; }
.fc-val   { font-size: 13px; font-weight: 700; color: white; margin-top: 1px; }

/* ══════ TRUSTED ══════ */
.trusted {
  padding: 40px 0;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.trusted__label {
  font-size: 10px;
  font-family: 'IBM Plex Mono', monospace;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--text-3);
  text-align: center;
  margin-bottom: 20px;
}
.trusted__logos {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}
.trusted__chip {
  padding: 7px 16px;
  border-radius: 999px;
  border: 1px solid var(--border-2);
  background: rgba(255,255,255,0.03);
  font-size: 11px;
  font-weight: 700;
  font-family: 'IBM Plex Mono', monospace;
  letter-spacing: .08em;
  color: var(--text-3);
}

/* ══════ SECTIONS ══════ */
.section { padding: 96px 0; }
.section--alt { background: var(--bg-alt); }
@media (min-width:768px) { .section { padding: 128px 0; } }

.section__head {
  text-align: center;
  margin-bottom: 64px;
}
.section__title {
  font-family: 'Fraunces', Georgia, serif;
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 800;
  line-height: 1.12;
  letter-spacing: -0.025em;
  color: white;
  margin-top: 14px;
}
.section__sub {
  margin-top: 12px;
  font-size: 15px;
  color: var(--text-2);
  font-family: 'IBM Plex Mono', monospace;
  max-width: 440px;
  margin-left: auto;
  margin-right: auto;
}

/* ══════ LABEL BADGE ══════ */
.label-badge {
  display: inline-flex;
  align-items: center;
  font-size: 10px;
  font-family: 'IBM Plex Mono', monospace;
  font-weight: 700;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--green-light);
  background: rgba(31,122,77,0.1);
  border: 1px solid rgba(31,122,77,0.25);
  padding: 5px 14px;
  border-radius: 999px;
}
.label-badge--green {
  color: var(--amber);
  background: rgba(232,162,61,0.1);
  border-color: rgba(232,162,61,0.25);
}
.label-badge--light {
  color: rgba(255,255,255,0.7);
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.15);
}

/* ══════ STEPS ══════ */
.steps-grid {
  display: grid;
  gap: 32px;
}
@media (min-width:640px) { .steps-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }

.step-card {
  position: relative;
  text-align: center;
  padding: 40px 24px 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  transition: border-color .3s, transform .3s;
}
.step-card:hover { border-color: var(--border-2); transform: translateY(-4px); box-shadow: var(--shadow-card); }

.step-card__num {
  width: 56px; height: 56px;
  border-radius: 50%;
  margin: 0 auto 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Fraunces', serif;
  font-size: 18px;
  font-weight: 900;
  color: white;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  box-shadow: 0 0 0 8px rgba(31,122,77,0.1), 0 8px 24px rgba(31,122,77,0.3);
}
.step-card__title {
  font-family: 'Fraunces', serif;
  font-size: 17px;
  font-weight: 700;
  color: white;
  margin-bottom: 10px;
}
.step-card__text { font-size: 13px; color: var(--text-2); line-height: 1.7; }

/* ══════ FEATURES ══════ */
.features-grid {
  display: grid;
  gap: 16px;
}
@media (min-width:640px) { .features-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width:1024px) { .features-grid { grid-template-columns: repeat(4, 1fr); } }

.feat-card {
  position: relative;
  padding: 28px 24px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  transition: all .3s;
}
.feat-card:hover {
  border-color: rgba(31,122,77,0.35);
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.3), var(--shadow-glow-g);
}
.feat-card__glow {
  position: absolute;
  top: -60%; right: -40%;
  width: 160px; height: 160px;
  border-radius: 50%;
  background: var(--green-glow);
  filter: blur(40px);
  pointer-events: none;
  opacity: 0;
  transition: opacity .4s;
}
.feat-card:hover .feat-card__glow { opacity: 1; }

.feat-card__icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  background: rgba(31,122,77,0.1);
  border: 1px solid rgba(31,122,77,0.2);
  margin-bottom: 20px;
}
.feat-card__title { font-size: 15px; font-weight: 700; color: white; margin-bottom: 8px; }
.feat-card__text  { font-size: 13px; color: var(--text-2); line-height: 1.65; }

/* ══════ SPLIT LAYOUT ══════ */
.split {
  display: grid;
  gap: 64px;
  align-items: center;
}
.split--flip .split__visual { order: 2; }
.split--flip .split__copy   { order: 1; }
@media (min-width:768px) {
  .split { grid-template-columns: 1fr 1fr; }
  .split--flip .split__visual { order: unset; }
  .split--flip .split__copy   { order: unset; }
}

.split__title {
  font-family: 'Fraunces', serif;
  font-size: clamp(1.75rem, 3.5vw, 2.75rem);
  font-weight: 800;
  letter-spacing: -0.025em;
  line-height: 1.12;
  color: white;
  margin-top: 14px;
}
.split__body {
  margin-top: 16px;
  font-size: 15px;
  line-height: 1.75;
  color: var(--text-2);
}
.link-green { color: var(--green-light); text-decoration: none; }
.link-green:hover { text-decoration: underline; }

.check-list { list-style: none; padding: 0; margin-top: 24px; display: flex; flex-direction: column; gap: 14px; }
.check-list li { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: var(--text-2); }
.check-list__icon {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 900; flex-shrink: 0;
  background: rgba(232,162,61,0.15);
  color: var(--amber);
  border: 1px solid rgba(232,162,61,0.25);
  margin-top: 1px;
}
.check-list--green .check-list__icon {
  background: rgba(74,222,128,0.12);
  color: var(--green-light);
  border-color: rgba(74,222,128,0.2);
}

/* ══════ PHONE FRAME ══════ */
.phone-frame {
  position: relative;
  width: 100%;
  max-width: 320px;
  margin: 0 auto;
  border-radius: 36px;
  border: 1px solid var(--border-2);
  background: var(--surface);
  overflow: hidden;
  box-shadow: 0 40px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05) inset;
  aspect-ratio: 9/19;
}
.phone-frame__notch {
  position: absolute;
  top: 12px; left: 50%; transform: translateX(-50%);
  width: 80px; height: 22px;
  background: var(--bg);
  border-radius: 12px;
  z-index: 10;
}
.phone-frame__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
}

/* ══════ STATS ══════ */
.stats-section { padding: 32px 0; }
.stats-band {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1px;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--border);
  border: 1px solid rgba(31,122,77,0.3);
  box-shadow: 0 0 60px rgba(31,122,77,0.15);
}
@media (min-width:640px) { .stats-band { grid-template-columns: repeat(4, 1fr); } }

.stat-item {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, rgba(31,122,77,0.12) 0%, rgba(31,122,77,0.06) 100%);
}
.stat-item__value {
  font-family: 'Fraunces', serif;
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 900;
  color: white;
  letter-spacing: -0.02em;
}
.stat-item__label {
  font-size: 11px;
  font-family: 'IBM Plex Mono', monospace;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--text-3);
  margin-top: 6px;
}

/* ══════ THEMES ══════ */
.themes-grid {
  display: grid;
  gap: 20px;
}
@media (min-width:640px) { .themes-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width:1024px) { .themes-grid { grid-template-columns: repeat(3, 1fr); } }

.theme-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 14px;
  transition: all .3s;
}
.theme-card:hover { border-color: rgba(31,122,77,0.35); transform: translateY(-4px); box-shadow: var(--shadow-card); }

.theme-card__window {
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.05);
}
.theme-card__chrome {
  background: #1a1d2e;
  padding: 8px 10px;
  display: flex;
  gap: 5px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.dot { width: 8px; height: 8px; border-radius: 50%; }
.dot--red    { background: #FF5F57; }
.dot--yellow { background: #FEBC2E; }
.dot--green  { background: #28C840; }

.theme-card__preview {
  aspect-ratio: 4/3;
  overflow: hidden;
  background: #1a1d2e;
  position: relative;
}
.theme-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top;
  transition: object-position 4000ms ease-in-out;
}
.group:hover .theme-card__img { object-position: bottom; }
.theme-card__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-3);
  font-family: 'Fraunces', serif;
  font-size: 24px;
  font-weight: 700;
}
.theme-card__foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 4px 4px;
  gap: 8px;
}
.theme-card__name { font-family: 'Fraunces', serif; font-weight: 700; font-size: 15px; color: white; }
.theme-card__slug { font-size: 10px; font-family: 'IBM Plex Mono', monospace; color: var(--text-3); margin-top: 2px; }

/* ══════ TESTIMONIALS ══════ */
.testimonial-track {
  position: relative;
  max-width: 600px;
  margin: 0 auto;
}

.testimonial-card {
  display: none;
  padding: 36px;
  background: var(--surface);
  border: 1px solid rgba(31,122,77,0.35);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-glow-g);
  animation: fade-in-scale 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.testimonial-card--active { display: block; }

@keyframes fade-in-scale {
  0% { opacity: 0; transform: scale(0.95) translateY(10px); }
  100% { opacity: 1; transform: scale(1) translateY(0); }
}

.tc-stars  { color: var(--amber); font-size: 14px; margin-bottom: 14px; letter-spacing: 2px; }
.tc-quote  { font-family: 'Fraunces', serif; font-size: 15px; line-height: 1.65; color: var(--text-1); }
.tc-author { display: flex; align-items: center; gap: 10px; margin-top: 20px; }
.tc-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px;
  background: linear-gradient(135deg, var(--green) 0%, var(--amber) 100%);
  color: white;
  flex-shrink: 0;
}
.tc-name { font-size: 13px; font-weight: 700; color: white; }
.tc-shop  { font-size: 11px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; margin-top: 1px; }

.testimonial-nav { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 32px; }
.tn-btn {
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 1px solid var(--border-2);
  background: var(--surface);
  color: var(--text-2);
  font-size: 20px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: all .2s;
}
.tn-btn:hover { border-color: var(--green); color: var(--green-light); }
.tn-dot {
  width: 6px; height: 6px;
  border-radius: 999px;
  background: var(--border-2);
  border: none;
  cursor: pointer;
  transition: all .25s;
}
.tn-dot--active { background: var(--green-light); width: 20px; }

/* ══════ PRICING ══════ */
.billing-toggle {
  display: inline-flex;
  gap: 4px;
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 4px;
  margin-top: 24px;
}
.bt-btn {
  padding: 9px 20px;
  border-radius: 9px;
  font-size: 12px;
  font-family: 'IBM Plex Mono', monospace;
  font-weight: 700;
  color: var(--text-2);
  background: none;
  border: none;
  cursor: pointer;
  transition: all .2s;
  display: flex; align-items: center; gap: 6px;
}
.bt-btn--active { background: white; color: #0B0E1A; }
.bt-save { color: var(--green-light); font-size: 10px; }

.pricing-grid {
  display: grid;
  gap: 20px;
  max-width: 960px;
  margin: 0 auto;
}
@media (min-width:768px) { .pricing-grid { grid-template-columns: repeat(3, 1fr); } }

.price-card {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: 28px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  transition: all .3s;
}
.price-card:hover { border-color: var(--border-2); transform: translateY(-4px); box-shadow: var(--shadow-card); }
.price-card--featured {
  background: linear-gradient(160deg, rgba(31,122,77,0.14) 0%, var(--surface) 100%);
  border-color: rgba(31,122,77,0.4);
  box-shadow: 0 0 0 1px rgba(31,122,77,0.2), var(--shadow-glow-g);
}

.price-card__badge {
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  padding: 4px 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--green) 0%, #2bac6e 100%);
  font-size: 10px;
  font-weight: 700;
  font-family: 'IBM Plex Mono', monospace;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: white;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(31,122,77,0.4);
}

.price-card__name { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 700; color: white; }
.price-card__price { display: flex; align-items: baseline; gap: 4px; margin-top: 14px; }
.price-card__amount { font-family: 'Fraunces', serif; font-size: 38px; font-weight: 900; color: white; letter-spacing: -0.02em; }
.price-card__period { font-size: 12px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; }
.price-card__desc { font-size: 12px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; margin-top: 8px; line-height: 1.5; }
.price-card__divider { height: 1px; background: var(--border); margin: 20px 0; }
.price-card__cta { margin-top: auto; width: 100%; justify-content: center; }

.price-feat-list { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.pfl-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.pfl-label { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--text-2); min-width: 0; }
.pfl-icon  { font-size: 14px; flex-shrink: 0; }
.pfl-val   { flex-shrink: 0; font-family: 'IBM Plex Mono', monospace; font-size: 12px; font-weight: 700; }
.pfl-dash  { color: var(--text-3); }
.pfl-yes   {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  background: rgba(74,222,128,0.12); color: var(--green-light);
  border: 1px solid rgba(74,222,128,0.2); font-size: 10px;
}
.pfl-no    {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%;
  background: rgba(255,255,255,0.04); color: var(--text-3);
  border: 1px solid var(--border); font-size: 10px;
}
.pfl-num   { color: white; }

/* ══════ FAQ ══════ */
.faq-layout {
  display: grid;
  gap: 48px;
  align-items: start;
}
@media (min-width:768px) { .faq-layout { grid-template-columns: 1fr 1.6fr; } }
.faq-left { position: sticky; top: 96px; }

.faq-right { display: flex; flex-direction: column; gap: 10px; }
.faq-item {
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--surface);
  overflow: hidden;
  transition: border-color .2s;
}
.faq-item:hover { border-color: var(--border-2); }
.faq-item__trigger {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  background: none;
  border: none;
  color: white;
  font-size: 14px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
  transition: color .2s;
}
.faq-item__trigger:hover { color: var(--green-light); }
.faq-item__icon {
  width: 26px; height: 26px; flex-shrink: 0;
  border-radius: 7px;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-2);
  transition: all .3s;
}
.faq-item__icon svg { width: 14px; height: 14px; transition: transform .3s; }
.faq-item__icon--open { background: rgba(31,122,77,0.1); border-color: rgba(31,122,77,0.3); color: var(--green-light); }
.faq-item__icon--open svg { transform: rotate(180deg); }
.faq-item__body { padding: 0 20px 18px; font-size: 13px; color: var(--text-2); line-height: 1.75; }

/* ══════ CTA ══════ */
.cta-section {
  position: relative;
  padding: 100px 0;
  overflow: hidden;
  border-top: 1px solid var(--border);
}
.cta-glow {
  position: absolute;
  top: 0; bottom: 0;
  width: 40%;
  background: radial-gradient(ellipse, rgba(31,122,77,0.14) 0%, transparent 70%);
  pointer-events: none;
  filter: blur(20px);
}
.cta-glow--left  { left: -10%; }
.cta-glow--right { right: -10%; }
.cta-inner {
  position: relative;
  z-index: 2;
  text-align: center;
}
.cta-title {
  font-family: 'Fraunces', serif;
  font-size: clamp(2rem, 4.5vw, 3.5rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: white;
  margin-top: 14px;
}
.cta-body {
  font-size: 16px;
  color: var(--text-2);
  max-width: 520px;
  margin: 18px auto 0;
  line-height: 1.7;
}
.cta-btns { display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; margin-top: 36px; }

/* ══════ NEWSLETTER ══════ */
.newsletter-section { padding: 32px 0 64px; }
.newsletter-card {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
}
@media (min-width:640px) {
  .newsletter-card { flex-direction: row; align-items: center; justify-content: space-between; }
}
.newsletter-card__title {
  font-family: 'Fraunces', serif;
  font-size: 20px;
  font-weight: 800;
  color: white;
}
.newsletter-card__sub { font-size: 13px; color: var(--text-2); margin-top: 4px; }
.newsletter-card__form { display: flex; gap: 8px; width: 100%; }
@media (min-width:640px) { .newsletter-card__form { width: auto; } }
.nl-input {
  flex: 1;
  min-width: 220px;
  padding: 11px 16px;
  border-radius: 9px;
  border: 1px solid var(--border-2);
  background: rgba(255,255,255,0.04);
  color: white;
  font-size: 13px;
  font-family: 'IBM Plex Mono', monospace;
  outline: none;
  transition: border-color .2s;
}
.nl-input::placeholder { color: var(--text-3); }
.nl-input:focus { border-color: rgba(31,122,77,0.5); }
.newsletter-card__thanks { font-size: 13px; color: var(--green-light); font-family: 'IBM Plex Mono', monospace; font-weight: 700; }

/* ══════ FOOTER ══════ */
.footer { border-top: 1px solid var(--border); background: #070A14; }
.footer__inner {
  display: grid;
  gap: 48px;
  padding-top: 60px;
  padding-bottom: 60px;
}
@media (min-width:768px) { .footer__inner { grid-template-columns: 1fr 1.6fr; } }
.footer__brand { display: flex; flex-direction: column; gap: 16px; }
.footer__tagline { font-size: 13px; color: var(--text-3); line-height: 1.7; max-width: 280px; font-family: 'IBM Plex Mono', monospace; }
.footer__contacts { display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; }

.footer__cols { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
@media (max-width:640px) { .footer__cols { grid-template-columns: repeat(2, 1fr); } }

.footer__col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: white; margin-bottom: 16px; }
.footer__links { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.footer__links a { font-size: 13px; color: var(--text-3); text-decoration: none; font-family: 'IBM Plex Mono', monospace; transition: color .2s; }
.footer__links a:hover { color: white; }
.footer__col-body { font-size: 12px; color: var(--text-3); font-family: 'IBM Plex Mono', monospace; line-height: 1.65; }
.inline-flex { display: inline-flex; }
.mt-3 { margin-top: 12px; }

.footer__bottom { border-top: 1px solid var(--border); padding: 20px 0; }
.footer__bottom-inner {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
  font-size: 11px;
  font-family: 'IBM Plex Mono', monospace;
  color: var(--text-3);
}
@media (min-width:640px) { .footer__bottom-inner { flex-direction: row; justify-content: space-between; } }
.footer__socials { display: flex; gap: 20px; }
.footer__socials a { color: var(--text-3); text-decoration: none; transition: color .2s; }
.footer__socials a:hover { color: white; }

/* ══════ SCROLL ANIMATIONS ══════ */
.sa {
  opacity: 0;
  transition: opacity .65s cubic-bezier(0.16, 1, 0.3, 1), transform .65s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: var(--d, 0ms);
}
.sa.fade-up    { transform: translateY(28px); }
.sa.fade-left  { transform: translateX(28px); }
.sa.fade-right { transform: translateX(-28px); }
.sa.in { opacity: 1; transform: translate(0); }

/* ══════ UTILITIES ══════ */
.mx-auto { margin-left: auto; margin-right: auto; }
</style>