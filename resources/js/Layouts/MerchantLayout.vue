<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const pageTitle = computed(() => {
  const parts = page.component.split('/')
  if (parts.length > 1 && parts[parts.length - 1] === 'Index') {
    return parts[parts.length - 2]
  }
  return parts[parts.length - 1]
})
const isImpersonating = computed(() => !!page.props.auth?.impersonating_tenant_id)
const sidebarOpen = ref(false)
const moreSheetOpen = ref(false)

const THEME_KEY = 'AssanDukan-merchant-theme'
const theme = ref('dark')

function applyTheme(value) {
  theme.value = value
  localStorage.setItem(THEME_KEY, value)
}

function toggleTheme() {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark')
}

onMounted(() => {
  const saved = localStorage.getItem(THEME_KEY)
  if (saved === 'dark' || saved === 'light') {
    theme.value = saved
  } else if (window.matchMedia?.('(prefers-color-scheme: light)').matches) {
    theme.value = 'light'
  }
})

const allNav = [
  { label: 'Dashboard', icon: '🏠', route: 'merchant.dashboard',     roles: ['owner', 'manager', 'staff'] },
  { label: 'Orders',    icon: '📦', route: 'merchant.orders.index',   roles: ['owner', 'manager', 'staff'] },
  { label: 'Products',  icon: '🛍️', route: 'merchant.products.index', roles: ['owner', 'manager'] },
  { label: 'Coupons',   icon: '🎟️', route: 'merchant.coupons.index',  roles: ['owner', 'manager'] },
  { label: 'Theme',     icon: '🎨', route: 'merchant.theme',          roles: ['owner', 'manager'] },
  { label: 'Sections',  icon: '⚙️', route: 'merchant.sections.index', roles: ['owner', 'manager'] },
  { label: 'Settings',  icon: '⚙️', route: 'merchant.settings.index', roles: ['owner', 'manager'] },
  { label: 'Domains',   icon: '🌐', route: 'merchant.domain.index',   roles: ['owner', 'manager'] },
  { label: 'Staff',     icon: '👥', route: 'merchant.staff.index',    roles: ['owner'] },
  { label: 'Billing',   icon: '💳', route: 'merchant.billing',        roles: ['owner'] },
]

const userRole = computed(() => page.props.auth?.user?.tenant_role ?? 'staff')
const nav = computed(() => allNav.filter(item => item.roles.includes(userRole.value)))

const coreTabRoutes = ['merchant.dashboard', 'merchant.orders.index', 'merchant.products.index']
const tabNav = computed(() => nav.value.filter(item => coreTabRoutes.includes(item.route)))
const moreNav = computed(() => nav.value.filter(item => !coreTabRoutes.includes(item.route)))
</script>

<template>
  <Head :title="pageTitle" />
  <div
    class="merchant-shell min-h-screen flex overscroll-none font-['Plus_Jakarta_Sans']"
    :data-theme="theme"
    style="background: var(--surface-page); color: var(--text-primary);"
  >

    <!-- Sidebar — desktop only -->
    <aside class="hidden lg:flex lg:static w-64 flex-col shrink-0" style="background: var(--surface-panel); border-right: 1px solid var(--border-subtle);">
      <div class="flex items-center gap-3 p-4 shrink-0" style="border-bottom: 1px solid var(--border-subtle);">
        <div class="w-9 h-9 bg-[#1F7A4D] rounded-lg flex items-center justify-center font-['Fraunces'] font-bold text-white text-sm shrink-0">
          {{ ($page.props.auth?.user?.name?.[0] ?? 'M').toUpperCase() }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-['Fraunces'] font-semibold text-sm truncate" style="color: var(--text-primary);">{{ $page.props.auth?.user?.name ?? 'My Store' }}</p>
          <p class="text-xs truncate" style="color: var(--text-faint);">Merchant Panel</p>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <Link
          v-for="item in nav"
          :key="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all"
          :class="route().current(item.route + '*') ? 'bg-[#1F7A4D]/15 font-medium' : 'hover:bg-[var(--hover-surface)]'"
          :style="route().current(item.route + '*') ? 'color: var(--accent-green-text);' : 'color: var(--text-muted);'"
        >
          <span class="text-base w-5 text-center shrink-0">{{ item.icon }}</span>
          <span class="truncate">{{ item.label }}</span>
        </Link>
      </nav>

      <div class="p-3 space-y-0.5 shrink-0" style="border-top: 1px solid var(--border-subtle);">
        <!-- Theme toggle -->
        <button
          @click="toggleTheme"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm w-full transition-all hover:bg-[var(--hover-surface)]"
          style="color: var(--text-muted);"
          :aria-pressed="theme === 'light'"
        >
          <span class="w-5 text-center shrink-0">{{ theme === 'dark' ? '🌙' : '☀️' }}</span>
          <span class="truncate">{{ theme === 'dark' ? 'Dark mode' : 'Light mode' }}</span>
          <span class="ml-auto w-9 h-5 rounded-full relative transition-colors" :style="`background: ${theme === 'dark' ? 'rgba(255,255,255,0.15)' : '#1F7A4D'}`">
            <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white transition-all" :style="`left: ${theme === 'dark' ? '2px' : '18px'}`"></span>
          </span>
        </button>

        <a
          v-if="$page.props.auth?.tenant"
          :href="`/store/${$page.props.auth.tenant.store_slug}`"
          target="_blank"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all hover:bg-[var(--hover-surface)]"
          style="color: var(--text-muted);"
        >
          <span class="w-5 text-center shrink-0">🔗</span>
          <span class="truncate">View My Store</span>
        </a>
        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm w-full transition-all hover:bg-[#C4432E]/10"
          style="color: var(--text-faint);"
          onmouseover="this.style.color='var(--accent-red-text)'"
          onmouseout="this.style.color='var(--text-faint)'"
        >
          <span class="w-5 text-center shrink-0">🚪</span> Logout
        </Link>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 pb-[calc(4.25rem+env(safe-area-inset-bottom))] lg:pb-0">
      <!-- Header -->
      <header
        class="sticky top-0 z-10 backdrop-blur px-4 sm:px-6 py-3 flex items-center gap-3 shrink-0"
        style="background: color-mix(in srgb, var(--surface-page) 90%, transparent); border-bottom: 1px solid var(--border-subtle); padding-top: max(0.75rem, env(safe-area-inset-top));"
      >
        <slot name="header">
          <h1 class="font-['Fraunces'] text-base font-semibold" style="color: var(--text-primary);">Dashboard</h1>
        </slot>

        <!-- Mobile theme toggle -->
        <button
          @click="toggleTheme"
          class="ml-auto lg:hidden w-9 h-9 rounded-lg flex items-center justify-center text-base transition-all hover:bg-[var(--hover-surface)]"
          aria-label="Toggle light and dark mode"
        >
          {{ theme === 'dark' ? '🌙' : '☀️' }}
        </button>
        <div class="hidden lg:block text-xs truncate max-w-xs font-['IBM_Plex_Mono']" style="color: var(--text-faint);">{{ $page.props.auth?.user?.email }}</div>
      </header>

      <!-- Flash messages -->
      <div v-if="flash.success || flash.error" class="px-4 sm:px-6 pt-4 space-y-2">
        <div v-if="flash.success" class="flex items-center gap-2 bg-[#1F7A4D]/10 border border-[#1F7A4D]/30 px-4 py-3 rounded-xl text-sm" style="color: var(--accent-green-text);">
          ✅ {{ flash.success }}
        </div>
        <div v-if="flash.error" class="flex items-center gap-2 bg-[#C4432E]/10 border border-[#C4432E]/30 px-4 py-3 rounded-xl text-sm" style="color: var(--accent-red-text);">
          ❌ {{ flash.error }}
        </div>
      </div>

      <!-- Content -->
      <main class="flex-1 p-4 sm:p-6 overscroll-contain">
        <slot />
      </main>
    </div>

    <!-- 🔴 Impersonation Banner -->
    <div
      v-if="isImpersonating"
      class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-white"
      style="background: #C4432E; padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));"
    >
      <span>⚠️ You are impersonating this merchant as Admin.</span>
      <Link
        :href="route('admin.impersonate.leave')"
        method="post"
        as="button"
        class="px-4 py-1.5 bg-white text-[#C4432E] rounded-lg text-xs font-bold hover:bg-red-50 transition-colors shrink-0"
      >
        ← Return to Admin
      </Link>
    </div>

    <!-- Mobile Bottom Tab Bar -->
    <nav
      class="fixed bottom-0 left-0 right-0 z-20 lg:hidden backdrop-blur flex select-none"
      style="background: color-mix(in srgb, var(--surface-panel) 95%, transparent); border-top: 1px solid var(--border-subtle); padding-bottom: env(safe-area-inset-bottom);"
    >
      <Link
        v-for="item in tabNav"
        :key="item.route"
        :href="route(item.route)"
        class="flex-1 flex flex-col items-center justify-center py-2.5 gap-0.5 relative transition-colors touch-manipulation active:scale-95"
        :style="route().current(item.route + '*') ? 'color: var(--accent-green-text);' : 'color: var(--text-faint);'"
      >
        <span v-if="route().current(item.route + '*')" class="absolute top-0 inset-x-5 h-0.5 rounded-full bg-[#2FA968]"></span>
        <span class="text-xl leading-none">{{ item.icon }}</span>
        <span class="text-[9px] font-medium">{{ item.label }}</span>
      </Link>
      <button
        v-if="moreNav.length"
        @click="moreSheetOpen = true"
        class="flex-1 flex flex-col items-center justify-center py-2.5 gap-0.5 transition-colors touch-manipulation active:scale-95"
        :style="moreSheetOpen ? 'color: var(--accent-green-text);' : 'color: var(--text-faint);'"
        aria-label="More options"
      >
        <span class="text-xl leading-none">⋯</span>
        <span class="text-[9px] font-medium">More</span>
      </button>
    </nav>

    <!-- Native-style "More" bottom sheet -->
    <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="moreSheetOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden" @click="moreSheetOpen = false" />
    </Transition>
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      leave-active-class="transition-transform duration-200 ease-in"
      enter-from-class="translate-y-full" leave-to-class="translate-y-full"
    >
      <div
        v-if="moreSheetOpen"
        class="fixed bottom-0 left-0 right-0 z-50 lg:hidden rounded-t-3xl shadow-2xl"
        style="background: var(--surface-panel); border-top: 1px solid var(--border-subtle); padding-bottom: env(safe-area-inset-bottom);"
      >
        <div class="flex justify-center pt-2.5 pb-1">
          <div class="w-9 h-1 rounded-full" style="background: var(--border-subtle);"></div>
        </div>

        <div class="flex items-center gap-3 px-5 pt-2 pb-4" style="border-bottom: 1px solid var(--border-subtle);">
          <div class="w-9 h-9 bg-[#1F7A4D] rounded-lg flex items-center justify-center font-['Fraunces'] font-bold text-white text-sm shrink-0">
            {{ ($page.props.auth?.user?.name?.[0] ?? 'M').toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-['Fraunces'] font-semibold text-sm truncate" style="color: var(--text-primary);">{{ $page.props.auth?.user?.name ?? 'My Store' }}</p>
            <p class="text-xs truncate" style="color: var(--text-faint);">Merchant Panel</p>
          </div>
          <button
            @click="toggleTheme"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-base shrink-0"
            aria-label="Toggle light and dark mode"
          >
            {{ theme === 'dark' ? '🌙' : '☀️' }}
          </button>
        </div>

        <div class="p-3 space-y-1 max-h-[50vh] overflow-y-auto">
          <Link
            v-for="item in moreNav"
            :key="item.route"
            :href="route(item.route)"
            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm transition-all touch-manipulation active:scale-[0.98]"
            :class="route().current(item.route + '*') ? 'bg-[#1F7A4D]/15 font-medium' : 'hover:bg-[var(--hover-surface)]'"
            :style="route().current(item.route + '*') ? 'color: var(--accent-green-text);' : 'color: var(--text-muted);'"
            @click="moreSheetOpen = false"
          >
            <span class="text-lg w-6 text-center shrink-0">{{ item.icon }}</span>
            <span class="truncate">{{ item.label }}</span>
          </Link>

          <a
            v-if="$page.props.auth?.tenant"
            :href="`/store/${$page.props.auth.tenant.store_slug}`"
            target="_blank"
            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm transition-all touch-manipulation active:scale-[0.98] hover:bg-[var(--hover-surface)]"
            style="color: var(--text-muted);"
          >
            <span class="w-6 text-center shrink-0">🔗</span>
            <span class="truncate">View My Store</span>
          </a>
        </div>

        <div class="p-3 pt-1" style="border-top: 1px solid var(--border-subtle);">
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm w-full transition-all touch-manipulation active:scale-[0.98] hover:bg-[#C4432E]/10"
            style="color: var(--accent-red-text);"
          >
            <span class="w-6 text-center shrink-0">🚪</span> Logout
          </Link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.merchant-shell {
  --surface-page: #141B2E;
  --surface-panel: #101526;
  --hover-surface: rgba(255, 255, 255, 0.05);
  --border-subtle: rgba(255, 255, 255, 0.1);
  --text-primary: #ffffff;
  --text-muted: rgba(255, 255, 255, 0.6);
  --text-faint: rgba(255, 255, 255, 0.4);
  --accent-green-text: #2fa968;
  --accent-red-text: #ff8a75;
  transition: background 0.2s ease, color 0.2s ease;
}

.merchant-shell[data-theme='light'] {
  --surface-page: #fbf6ec;
  --surface-panel: #ffffff;
  --hover-surface: rgba(27, 27, 29, 0.05);
  --border-subtle: rgba(27, 27, 29, 0.12);
  --text-primary: #1b1b1d;
  --text-muted: rgba(27, 27, 29, 0.65);
  --text-faint: rgba(27, 27, 29, 0.45);
  --accent-green-text: #1f7a4d;
  --accent-red-text: #c4432e;
}
</style>