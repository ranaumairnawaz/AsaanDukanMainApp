<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: Object,
  plan: Object,
  all_plans: Array,
  billing: Object,
  stats: Object,
})

const planColors = {
  basic:      { from: '#6366f1', to: '#8b5cf6' },
  growth:     { from: '#f59e0b', to: '#ef4444' },
  enterprise: { from: '#10b981', to: '#06b6d4' },
}

function getPlanColor(slug) {
  return planColors[slug] ?? planColors.basic
}

const planColor = computed(() => {
  const slug = props.plan?.slug ?? 'basic'
  return getPlanColor(slug)
})

const usageColor = computed(() => {
  if (props.billing.usage_pct >= 90) return '#ef4444'
  if (props.billing.usage_pct >= 70) return '#f59e0b'
  return '#10b981'
})

const circumference = 2 * Math.PI * 54
const dashOffset = computed(() => circumference - (props.billing.usage_pct / 100) * circumference)

const isExpired = computed(() => props.billing.subscription_status === 'suspended')

const isExpiringSoon = computed(() => {
  if (isExpired.value) return false
  if (!props.billing.subscription_expires_at) return false
  const expiry = new Date(props.billing.subscription_expires_at)
  const now = new Date()
  const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24))
  return diffDays <= 3 && diffDays > 0
})

const daysUntilExpiry = computed(() => {
  if (!props.billing.subscription_expires_at) return null
  const expiry = new Date(props.billing.subscription_expires_at)
  const now = new Date()
  return Math.max(0, Math.ceil((expiry - now) / (1000 * 60 * 60 * 24)))
})

const isCurrentPlan = (planItem) => planItem.id === props.plan?.id

function planTierBadge(slug) {
  if (slug === 'enterprise') return { label: '🚀 Enterprise', bg: '#10b98120', border: '#10b98140', text: '#34d399' }
  if (slug === 'growth')     return { label: '⚡ Growth', bg: '#f59e0b20', border: '#f59e0b40', text: '#fcd34d' }
  return { label: '✨ Starter', bg: '#6366f120', border: '#6366f140', text: '#a5b4fc' }
}

function getFeatureVal(plan, key) {
  const feat = plan.features?.find(f => f.feature_key === key)
  return feat?.feature_value ?? null
}
</script>

<template>
  <MerchantLayout :tenant="tenant">
    <div class="space-y-6 sm:space-y-8">

      <!-- Page Header -->
      <div class="page-header flex-col items-start gap-1">
        <h1 class="page-title">Billing & Plans</h1>
        <p class="page-subtitle">Manage your subscription, usage, and upgrade your plan anytime.</p>
      </div>

      <!-- EXPIRED Banner -->
      <div v-if="isExpired"
        class="p-5 rounded-2xl border-2 border-red-500/60 flex flex-col sm:flex-row sm:items-center gap-4"
        style="background: linear-gradient(135deg, #450a0a, #7f1d1d);">
        <div class="text-3xl">🚫</div>
        <div class="flex-1">
          <p class="font-extrabold text-red-300 text-base sm:text-lg">Your subscription has expired</p>
          <p class="text-red-200/70 text-sm mt-0.5">Your store is currently suspended. Renew your subscription to restore access to all features.</p>
        </div>
        <a :href="route('merchant.payment.create')"
          class="shrink-0 px-6 py-3 rounded-xl font-bold text-sm text-white transition-all hover:scale-105 text-center"
          style="background: linear-gradient(135deg, #ef4444, #dc2626);">Renew Now</a>
      </div>

      <!-- EXPIRING SOON Banner -->
      <div v-else-if="isExpiringSoon"
        class="p-5 rounded-2xl border-2 border-amber-500/60 flex flex-col sm:flex-row sm:items-center gap-4"
        style="background: linear-gradient(135deg, #451a03, #78350f);">
        <div class="text-3xl">⚠️</div>
        <div class="flex-1">
          <p class="font-extrabold text-amber-300 text-base sm:text-lg">Subscription expiring in {{ daysUntilExpiry }} day{{ daysUntilExpiry === 1 ? '' : 's' }}</p>
          <p class="text-amber-200/70 text-sm mt-0.5">Renew before {{ billing.subscription_expires_at }} to avoid store suspension.</p>
        </div>
        <a :href="route('merchant.payment.create')"
          class="shrink-0 px-6 py-3 rounded-xl font-bold text-sm text-white transition-all hover:scale-105 text-center"
          style="background: linear-gradient(135deg, #f59e0b, #d97706);">Renew Now</a>
      </div>

      <!-- Current Plan Card -->
      <div class="rounded-2xl p-5 sm:p-6 relative overflow-hidden"
        :style="{ background: `linear-gradient(135deg, ${planColor.from}20, ${planColor.to}10)`, border: `1px solid ${planColor.from}30` }">
        <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full opacity-10 blur-2xl pointer-events-none"
          :style="{ background: planColor.to }"></div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 sm:gap-6">
          <div>
            <p class="text-[10px] sm:text-xs font-bold uppercase tracking-widest mb-1 sm:mb-2" :style="{ color: planColor.from }">
              {{ billing.subscription_status === 'trial' ? 'Free Trial' : (billing.subscription_status === 'suspended' ? '⛔ Suspended' : 'Active Plan') }}
            </p>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white">{{ plan?.name ?? 'No Plan' }}</h2>
            <p class="text-white/60 mt-1 sm:mt-2 text-sm">
              Rs. {{ Number(billing.plan_price_monthly).toLocaleString() }} / month
            </p>
            <p v-if="billing.subscription_expires_at" class="text-white/40 text-xs mt-1">
              {{ isExpired ? 'Expired on' : 'Expires' }}: {{ billing.subscription_expires_at }}
            </p>
          </div>
          <div class="sm:text-right space-y-1 mt-2 sm:mt-0 pt-4 sm:pt-0 border-t sm:border-t-0 border-white/10 sm:border-transparent">
            <div class="text-xs sm:text-sm text-white/50">Renewal Total</div>
            <div class="text-2xl sm:text-3xl font-extrabold" :style="{ color: planColor.from }">
              Rs. {{ Number(billing.renewal_total).toLocaleString() }}
            </div>
            <div v-if="billing.overage_cost > 0" class="text-[10px] sm:text-xs text-amber-400">
              Includes Rs. {{ Number(billing.overage_cost).toLocaleString() }} overage
            </div>
            <div class="mt-3 sm:mt-4">
              <a :href="route('merchant.payment.create')" class="inline-block w-full sm:w-auto text-center px-6 py-2.5 sm:py-2 rounded-xl text-sm font-bold text-white transition-all hover:scale-105"
                 :style="{ background: `linear-gradient(135deg, ${planColor.from}, ${planColor.to})` }">
                Pay / Renew
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-5">
        <div v-for="stat in [
          { label: 'Total Orders', value: stats.total_orders, icon: '📦' },
          { label: 'Pending Orders', value: stats.pending_orders, icon: '⏳' },
          { label: 'Total Revenue', value: 'Rs. ' + Number(stats.total_revenue).toLocaleString(), icon: '💰' },
        ]" :key="stat.label"
          class="card p-4 sm:p-5 text-center flex flex-row sm:flex-col items-center justify-between sm:justify-center gap-3">
          <div class="text-xl sm:text-2xl sm:mb-2 shrink-0">{{ stat.icon }}</div>
          <div class="flex-1 sm:w-full text-left sm:text-center min-w-0">
            <div class="text-lg sm:text-2xl font-bold text-white truncate">{{ stat.value }}</div>
            <div class="text-[10px] sm:text-xs text-white/50 sm:mt-1 truncate">{{ stat.label }}</div>
          </div>
        </div>
      </div>

      <!-- WhatsApp Usage Card -->
      <div class="card p-5 sm:p-6 grid sm:grid-cols-3 gap-6 sm:gap-8 items-center">
        <div class="flex flex-col items-center gap-3 sm:gap-4">
          <div class="relative w-28 h-28 sm:w-36 sm:h-36">
            <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="54" fill="none" stroke="#1f2937" stroke-width="8" />
              <circle cx="60" cy="60" r="54" fill="none" :stroke="usageColor" stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
                style="transition: stroke-dashoffset 0.8s ease;" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-2xl sm:text-3xl font-extrabold text-white">{{ billing.usage_pct }}%</span>
              <span class="text-[10px] sm:text-xs text-white/40">Used</span>
            </div>
          </div>
          <div class="text-center">
            <p class="text-xs sm:text-sm font-bold text-white">{{ billing.messages_sent }} / {{ billing.quota_limit }}</p>
            <p class="text-[10px] sm:text-xs text-white/50">Messages This Period</p>
          </div>
        </div>
        <div class="sm:col-span-2 space-y-4 sm:space-y-5">
          <h3 class="text-base sm:text-lg font-bold text-white">WhatsApp Usage</h3>
          <div class="space-y-2 sm:space-y-3">
            <div class="flex justify-between text-xs sm:text-sm">
              <span class="text-white/60">Plan Quota</span>
              <span class="text-white font-bold">{{ billing.quota_limit }} messages</span>
            </div>
            <div class="flex justify-between text-xs sm:text-sm">
              <span class="text-white/60">Messages Sent</span>
              <span class="text-white font-bold">{{ billing.messages_sent }}</span>
            </div>
            <div class="flex justify-between text-xs sm:text-sm">
              <span class="text-white/60">Overage Messages</span>
              <span class="font-bold" :class="billing.overage_count > 0 ? 'text-amber-400' : 'text-white'">{{ billing.overage_count }}</span>
            </div>
            <div class="flex justify-between text-xs sm:text-sm border-t border-white/10 pt-2 sm:pt-3">
              <span class="text-white/60">Overage Rate</span>
              <span class="text-white font-bold">Rs. {{ billing.overage_price }} / msg</span>
            </div>
            <div class="flex justify-between text-xs sm:text-sm font-bold">
              <span class="text-white">Overage Charges</span>
              <span class="text-amber-400">Rs. {{ Number(billing.overage_cost).toLocaleString() }}</span>
            </div>
          </div>
          <div v-if="billing.overage_cost > 0"
            class="p-3 rounded-xl text-[10px] sm:text-xs bg-amber-500/10 border border-amber-500/20 text-amber-400 leading-relaxed">
            💡 Overage charges of <strong class="text-amber-300">Rs. {{ Number(billing.overage_cost).toLocaleString() }}</strong> will be added to your next renewal invoice.
          </div>
          <div v-if="billing.period_start && billing.period_end" class="text-[10px] sm:text-xs text-white/30 text-center sm:text-left">
            Billing period: {{ new Date(billing.period_start).toLocaleDateString() }} —
            {{ new Date(billing.period_end).toLocaleDateString() }}
          </div>
        </div>
      </div>

      <!-- All Plans Section -->
      <div>
        <h2 class="text-lg font-bold text-white mb-4">Available Plans</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
          <div
            v-for="planItem in all_plans"
            :key="planItem.id"
            class="relative rounded-2xl p-5 flex flex-col gap-4 overflow-hidden transition-all duration-300"
            :class="isCurrentPlan(planItem) ? 'ring-2 ring-emerald-500 scale-[1.01]' : 'hover:scale-[1.01] hover:border-white/20'"
            :style="{
              background: isCurrentPlan(planItem)
                ? `linear-gradient(135deg, ${getPlanColor(planItem.slug).from}25, ${getPlanColor(planItem.slug).to}15)`
                : 'rgba(255,255,255,0.02)',
              border: `1px solid ${isCurrentPlan(planItem) ? getPlanColor(planItem.slug).from + '60' : 'rgba(255,255,255,0.06)'}`,
            }"
          >
            <!-- Current plan badge -->
            <div v-if="isCurrentPlan(planItem)"
              class="absolute top-3 right-3 text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase tracking-wide">
              Current Plan
            </div>

            <!-- Plan header -->
            <div>
              <div
                class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg mb-3"
                :style="{
                  background: planTierBadge(planItem.slug).bg,
                  border: `1px solid ${planTierBadge(planItem.slug).border}`,
                  color: planTierBadge(planItem.slug).text,
                }"
              >
                {{ planTierBadge(planItem.slug).label }}
              </div>
              <h3 class="text-xl font-extrabold text-white">{{ planItem.name }}</h3>
              <div class="flex items-baseline gap-1 mt-1">
                <span class="text-3xl font-extrabold" :style="{ color: getPlanColor(planItem.slug).from }">
                  Rs. {{ Number(planItem.price_monthly).toLocaleString() }}
                </span>
                <span class="text-white/40 text-xs">/mo</span>
              </div>
            </div>

            <!-- Features list -->
            <ul class="space-y-2 flex-1">
              <li v-for="feat in planItem.features?.slice(0, 6)" :key="feat.id"
                class="flex items-center gap-2 text-xs text-white/70">
                <span class="shrink-0 w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                  :style="{ background: getPlanColor(planItem.slug).from + '25', color: getPlanColor(planItem.slug).from }">✓</span>
                <span>
                  <span class="font-semibold text-white/90">{{ feat.feature_key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}</span>:
                  {{ feat.feature_value === '1' ? 'Enabled' : feat.feature_value === '0' ? 'Disabled' : feat.feature_value }}
                </span>
              </li>
            </ul>

            <!-- CTA -->
            <div>
              <div v-if="isCurrentPlan(planItem)"
                class="w-full py-3 rounded-xl text-sm font-bold text-center bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                ✓ Your Current Plan
              </div>
              <a v-else
                :href="`${route('merchant.payment.create')}?plan_id=${planItem.id}`"
                class="block w-full py-3 rounded-xl text-sm font-bold text-center text-white transition-all hover:scale-[1.02] hover:opacity-90"
                :style="{ background: `linear-gradient(135deg, ${getPlanColor(planItem.slug).from}, ${getPlanColor(planItem.slug).to})` }"
              >
                {{ planItem.price_monthly > (plan?.price_monthly ?? 0) ? '⬆ Upgrade to ' : '⬇ Switch to ' }}{{ planItem.name }}
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </MerchantLayout>
</template>
