<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const pageTitle = computed(() => {
  const parts = page.component.split('/')
  if (parts.length > 1 && parts[parts.length - 1] === 'Index') {
    return parts[parts.length - 2]
  }
  return parts[parts.length - 1]
})
const sidebarOpen = ref(false)

// Full nav — shown in the sidebar (desktop) and the "More" drawer (mobile)
const nav = [
  { label: 'Dashboard',       icon: '🏠', route: 'admin.dashboard' },
  { label: 'Tenants',         icon: '🏪', route: 'admin.tenants.index' },
  { label: 'Domains',         icon: '🌐', route: 'admin.domains.index' },
  { label: 'Plans',           icon: '💳', route: 'admin.plans.index' },
  { label: 'Subscriptions',   icon: '📅', route: 'admin.subscriptions.index' },
  { label: 'Payments',        icon: '💰', route: 'admin.payments.index' },
  { label: 'Unlock Requests', icon: '🔓', route: 'admin.unlock_payments.index' },
  { label: 'Coupons',         icon: '🏷️', route: 'admin.coupons.index' },
  { label: 'Themes',          icon: '🎨', route: 'admin.themes.index' },
  { label: 'Settings',        icon: '⚙️', route: 'admin.settings.index' },
  { label: 'Community',       icon: '📢', route: 'admin.community.index' },
  { label: 'Broadcast',       icon: '💬', route: 'admin.broadcast.index' },
]

// The 4 most-used items get a thumb-reachable bottom tab on mobile;
// everything else lives one tap away behind "More".
const bottomTabs = [
  { label: 'Home',     icon: '🏠', route: 'admin.dashboard' },
  { label: 'Tenants',  icon: '🏪', route: 'admin.tenants.index' },
  { label: 'Payments', icon: '💰', route: 'admin.payments.index' },
  { label: 'Unlocks',  icon: '🔓', route: 'admin.unlock_payments.index' },
]
</script>

<template>
  <Head :title="pageTitle" />
  <div class="min-h-screen bg-[#141B2E] text-gray-100 font-['Plus_Jakarta_Sans'] flex">

    <!-- Mobile backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0" leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-black/70 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- Sidebar / mobile "More" drawer -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-72 max-w-[82vw] bg-[#101526] border-r border-white/10 flex flex-col transition-transform duration-300 ease-out lg:translate-x-0 lg:static lg:inset-auto lg:w-64 lg:max-w-none"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Brand -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-white/10 shrink-0">
        <div class="w-8 h-8 bg-[#1F7A4D] rounded-lg flex items-center justify-center font-['Fraunces'] font-bold text-white text-sm">M</div>
        <div>
          <p class="font-['Fraunces'] font-semibold text-white text-sm leading-none">AssanDukan</p>
          <p class="text-[10px] text-white/40 mt-1 font-['IBM_Plex_Mono']">Admin Console</p>
        </div>
        <button @click="sidebarOpen = false" class="ml-auto text-white/40 hover:text-white lg:hidden p-2 -mr-2 rounded-lg active:bg-white/10" aria-label="Close menu">✕</button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <Link
          v-for="item in nav"
          :key="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm transition-all active:scale-[0.98]"
          :class="route().current(item.route + '*')
            ? 'bg-[#1F7A4D]/15 text-[#2FA968] font-semibold'
            : 'text-white/60 hover:text-white hover:bg-white/5'"
          @click="sidebarOpen = false"
        >
          <span class="text-lg w-6 text-center shrink-0">{{ item.icon }}</span>
          <span class="truncate">{{ item.label }}</span>
        </Link>
      </nav>

      <!-- User + logout -->
      <div class="p-3 border-t border-white/10 shrink-0" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
        <div class="flex items-center gap-3 px-3.5 py-2 mb-1">
          <div class="w-8 h-8 rounded-full bg-[#1F7A4D] flex items-center justify-center text-xs font-bold text-white shrink-0">
            {{ ($page.props.auth?.user?.name?.[0] ?? 'A').toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-xs font-medium text-white truncate">{{ $page.props.auth?.user?.name ?? 'Admin' }}</p>
            <p class="text-[10px] text-white/40 truncate">Super Admin</p>
          </div>
        </div>
        <Link
          :href="route('admin.logout')"
          method="post"
          as="button"
          class="flex items-center gap-3 w-full px-3.5 py-2.5 rounded-xl text-sm text-white/50 hover:text-[#FF8A75] hover:bg-[#C4432E]/10 transition-all"
        >
          <span class="w-6 text-center">🚪</span> Logout
        </Link>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="sticky top-0 z-20 bg-[#141B2E]/90 backdrop-blur border-b border-white/10 px-4 sm:px-6 py-3 flex items-center gap-3 shrink-0" style="padding-top: max(0.75rem, env(safe-area-inset-top));">
        <button
          @click="sidebarOpen = true"
          class="p-2 -ml-1 rounded-lg text-white/60 hover:text-white hover:bg-white/5 active:bg-white/10 transition-colors lg:hidden"
          aria-label="Open menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="font-['Fraunces'] text-sm font-semibold text-white">Admin Panel</h1>
        <span class="ml-auto text-xs text-white/40 hidden sm:block font-['IBM_Plex_Mono']">{{ $page.props.auth?.user?.email }}</span>
      </header>

      <!-- Flash messages -->
      <div v-if="flash.success || flash.error" class="px-4 sm:px-6 pt-4 space-y-2">
        <div v-if="flash.success" class="flex items-center gap-2 bg-[#1F7A4D]/10 border border-[#1F7A4D]/30 text-[#2FA968] px-4 py-3 rounded-xl text-sm">
          ✅ {{ flash.success }}
        </div>
        <div v-if="flash.error" class="flex items-center gap-2 bg-[#C4432E]/10 border border-[#C4432E]/30 text-[#FF8A75] px-4 py-3 rounded-xl text-sm">
          ❌ {{ flash.error }}
        </div>
      </div>

      <!-- Page content — bottom padding clears the mobile tab bar -->
      <main class="flex-1 p-4 sm:p-6 pb-24 lg:pb-6">
        <slot />
      </main>
    </div>

    <!-- Mobile bottom tab bar -->
    <nav
      class="fixed bottom-0 inset-x-0 z-30 lg:hidden bg-[#101526]/95 backdrop-blur border-t border-white/10 flex items-stretch"
      style="padding-bottom: env(safe-area-inset-bottom);"
    >
      <Link
        v-for="item in bottomTabs"
        :key="item.route"
        :href="route(item.route)"
        class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] transition-colors relative"
        :class="route().current(item.route + '*') ? 'text-[#2FA968]' : 'text-white/45 hover:text-white/70'"
      >
        <span v-if="route().current(item.route + '*')" class="absolute top-0 inset-x-4 h-0.5 rounded-full bg-[#2FA968]"></span>
        <span class="text-lg leading-none">{{ item.icon }}</span>
        <span class="font-medium">{{ item.label }}</span>
      </Link>
      <button
        @click="sidebarOpen = true"
        class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] text-white/45 hover:text-white/70 transition-colors"
        aria-label="More options"
      >
        <span class="text-lg leading-none">☰</span>
        <span class="font-medium">More</span>
      </button>
    </nav>
  </div>
</template>