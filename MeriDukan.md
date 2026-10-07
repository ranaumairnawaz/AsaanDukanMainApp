# AI Build Prompt: WhatsApp-First E-Commerce Storefront Builder for Pakistani SMBs

> Copy everything below into your AI coding tool (Claude Code, Cursor, etc.) as the project brief. It's written to be handed over section by section if the tool has context limits — start with Sections 1–4, then feed the rest as you build each module.

---

## 1. Product Overview

Build a multi-tenant SaaS platform that lets small and medium businesses in Pakistan create an online storefront **without buying a domain or hosting**, and manage orders through **WhatsApp** instead of email or a separate customer-facing app.

**Core value proposition:**
- Merchant signs up, picks a store name, adds products via a simple dashboard — live storefront in minutes.
- Orders trigger automatic WhatsApp messages from the platform's own WhatsApp number — to the merchant always, and to end customers as a paid upgrade.
- Merchant picks a professional storefront theme suited to their business type (fashion, cosmetics, electronics, decor, etc.).
- Merchant pays a monthly or yearly subscription, with feature access controlled entirely by configurable plans.
- A polished public landing page explains the product and pricing to prospective merchants.

**Primary users:**
1. **Merchant (tenant)** — kiryana stores, boutiques, home bakers, electronics sellers, etc. Low technical literacy assumed; UI must be extremely simple, ideally usable on a phone.
2. **End customer** — browses storefront on mobile web, places order, optionally receives WhatsApp updates (plan-dependent).
3. **Platform admin (you)** — manages tenants, subscription plans and their features, the WhatsApp gateway, and the landing page content/pricing.

---

## 2. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend framework | **Laravel 11** | Multi-tenant via `tenant_id` scoping (see §4) |
| Frontend | **Vue 3 + Inertia.js** | SPA-like feel without a separate API-only frontend; use Vite |
| Admin/Merchant dashboard | Vue 3 + Inertia, Tailwind CSS | Mobile-first, English + Urdu toggle |
| Public storefront | Server-rendered Blade or lightweight Vue islands, theme-driven (see §9) | Fast on 3G/4G, SEO-friendly |
| Landing page | Blade + Tailwind, static-feeling but served from the same Laravel app | See §8 |
| Database | **MySQL 8** | Primary transactional store |
| Cache/Queue | **Redis** | Laravel queues for WhatsApp dispatch, session store |
| WhatsApp Gateway | **Node.js microservice using Baileys**, single shared platform connection | See §6 — architecture changed from per-tenant to platform-owned number |
| File/media storage | S3-compatible object storage or local disk + CDN for MVP | Product images, theme assets |
| Payments | JazzCash & Easypaisa APIs, COD as default fallback | See §7 |
| Hosting | Hostinger — Laravel app on shared/Business hosting; WhatsApp gateway as one of your 5 available Node.js app slots on the same Hostinger plan (see §5B) | No separate VPS needed to start |

---

## 3. Multi-Tenancy Architecture

Single database, shared schema, `tenant_id` foreign key on every business table, enforced via a Laravel trait (`BelongsToTenant`) applied as a global model scope. Every tenant-owned model must use it — no exceptions, since a scoping bug here is a data-leak bug.

**Store URL resolution — hybrid approach, with subdomains achievable sooner than initially assumed (see §5A):**
- Default / works everywhere, zero setup: **path-based** — `platform.com/store/[store-name]`.
- **Subdomain** — `[store-name].platform.com`, offered as a plan feature (`subdomain_allowed` in §10). Since the whole platform is one Laravel app resolving tenants by the `Host` header, every subdomain just needs to be *created once* in Hostinger's hPanel pointing to the **same** `public_html` document root as the main app — no per-tenant folder, no code change, Laravel's tenant-resolution middleware handles it exactly like it already does for path-based stores. See §5A for the provisioning workflow (semi-manual now, automatable later).
- **Custom domain** — merchant points their own domain via CNAME, offered as a higher-plan feature later.

Build the tenant-resolution middleware to check, in order: custom domain match → subdomain match → path prefix match. The resolved `tenant_id` is what matters downstream, not how it was resolved — this is what makes offering all three URL types simultaneously, per plan tier, straightforward rather than three separate code paths.

---

## 4. Core Feature Set (MVP scope — build in this order)

### Phase 1 — Foundation
1. Platform admin panel: create/suspend tenants, manage subscription plans and their features (see §10), view WhatsApp gateway health.
2. Public **landing page** (see §8) — this is customer-facing marketing, build early since it's your acquisition tool from day one.
3. Merchant onboarding wizard: business name → store-name/slug picker (live availability check) → category (used to suggest a matching theme) → logo upload → done. Under 5 steps.
4. Merchant auth (Laravel Breeze/Fortify), password reset via WhatsApp OTP sent from the platform number.

### Phase 2 — Store Builder & Themes
5. Product catalog CRUD: name, price, images, stock, variants, category tags.
6. **Theme system** (see §9): merchant picks from available themes matching or unrelated to their category; theme choice + light customization (colors, logo, banner) stored per tenant.
7. Public storefront: product grid → detail → cart → checkout (name, phone, address, payment method), rendered through the selected theme.

### Phase 3 — Orders & WhatsApp Gateway
8. Order creation flow: checkout → order record (`pending`) → WhatsApp message to **merchant** always (from the platform number) — "New order #1234 from Ali — Rs. 2,500."
9. **Customer-facing WhatsApp messages are plan-gated** (see §10) — only sent if the tenant's active plan includes this feature. Build the notification dispatcher to check plan features before every send, not just at feature-toggle time, since plans can change mid-cycle.
10. Order status state machine: `pending → confirmed → processing → shipped → delivered → cancelled`, each transition optionally firing a customer message per plan.
11. WhatsApp gateway service (Node/Baileys), single platform-owned connection — see §6 for full spec, including the concentrated-risk considerations of this approach.

### Phase 4 — Subscriptions, Plans & Billing
12. **Configurable subscription plans** (see §10) — build this as data, not code, from day one: platform admin can create/edit plans and toggle every feature/limit without a deploy.
13. Recurring billing via JazzCash/Easypaisa where possible; manual bank transfer + admin approval queue as fallback.
14. Grace period + auto-suspend logic for missed payments, with reminders sent from the platform WhatsApp number.

### Phase 5 — Polish
15. Merchant analytics: orders this week, revenue, top products.
16. Delivery tracking (manual field first, courier API integration later).
17. Multi-staff logins per tenant with role permissions.
18. Add 1–2 more themes; confirm the theme system truly supports this without core code changes (see §9).

---

## 5. A Note on Hosting Constraints (Shared Hosting vs. Subdomains vs. the Node Gateway)

You mentioned you currently use shared hosting, which affects two separate things — read both parts:

**A. Store URLs — subdomains via a deliberately semi-manual workflow:** Your Hostinger plan supports creating up to 100 subdomains per domain through hPanel. Since your Laravel app already resolves every tenant by its `Host` header (§3), a new subdomain doesn't need its own folder or app instance — it just needs to be created once in hPanel and pointed at the **same** `public_html` document root your main app already uses. DNS is automatic if your domain uses Hostinger's nameservers.

**Chosen approach: semi-manual, not API-automated.** When a merchant on a subdomain-enabled plan requests their subdomain, it goes into a `subdomain_status = pending` state on the tenant record and surfaces in a super-admin queue ("Subdomain requests"). You (or whoever manages hosting) do the one-time hPanel step — create the subdomain, point it at the shared `public_html` root — then mark it `active` in the admin panel, which is all Laravel needs to start resolving that tenant by subdomain immediately. At realistic early volumes this is a low-friction, few-minutes task per merchant, and it keeps you in direct control of hosting-side changes rather than depending on a third-party API's coverage and reliability.

Build the `subdomain_status` field and the admin queue as first-class, permanent parts of the product (not a stopgap) — a clear "pending → active" queue with visible turnaround time is good operationally regardless of whether it's ever automated.

**B. The WhatsApp gateway itself — confirmed feasible directly on Hostinger:** you have access to **5 Node.js app slots** on your Hostinger plan, which supports real persistent server-side Node apps (Express/NestJS-style), not just static builds — these run continuously rather than spinning up per-request, and Hostinger's dashboard gives you a restart control and resource-usage graphs per app. This means the Baileys gateway can run as **one of your 5 Node.js app slots**, directly alongside your Laravel app on the same Hostinger plan — no separate VPS needed to get started. This also leaves you 4 more Node app slots free for future services (e.g. a separate worker process, a future official-API adapter if you ever add one, etc.).

Two things to build defensively given this hosting choice:
1. **Resource limits are real** on shared/Business plans (RAM and CPU caps per app). Keep the gateway process lean — it's just managing WhatsApp sessions and relaying messages, not doing heavy computation, so this should fit comfortably, but monitor the resource-usage graphs Hostinger provides and be ready to upgrade the plan tier if you approach limits as message volume grows.
2. **Managed Node hosting platforms can restart/recycle app processes** during maintenance or updates. Since Baileys sessions are just an in-memory socket plus persisted auth state, an unexpected restart means a brief reconnect, not a full re-pair — as long as the session data is being read from persistent storage on boot (already specced in §6.2's `whatsapp_number_pool.session_data`). Build the gateway to reconnect automatically and resume from stored session state every time the process starts, and treat "cold start reconnect" as a normal, expected event rather than an edge case.

If message volume or reliability needs eventually outgrow what a shared/Business Node app slot can comfortably handle, moving just the gateway to a small VPS later is a drop-in change — the internal API contract between Laravel and the gateway (§6.2) doesn't change either way, only where the gateway process physically runs.

---

## 6. WhatsApp Gateway — Single Platform-Owned Number

**Architecture change from a per-merchant model:** all messages — to merchants and, where the plan allows, to their customers — are sent from **one WhatsApp number owned by the platform**, not from each merchant's own number.

### 6.1 What this means operationally
- One **active** Baileys session sends platform-wide traffic at any given time, but the gateway is built to manage a **pool of numbers** (active + 2–3 backups), not hardcoded to a single connection.
- **All ban risk is concentrated on whichever number is currently active.** If it gets banned, you need to fail over to a backup within minutes, not rebuild from scratch — this is exactly why the pool/failover design in §6.2–6.3 is core, not optional polish.
- Messages should be clearly branded so recipients understand who's messaging them and why (e.g. "Message from [Merchant Store Name] via [Platform Name]") — this also reduces spam-report likelihood since recipients aren't confused about an unfamiliar number.

### 6.2 Responsibilities & Number Pool Management
- Maintain a **pool of WhatsApp numbers** in the gateway, each with its own Baileys session, one flagged `active`, the rest `standby`/`warming`/`banned`/`disconnected`.
- Expose an internal REST API for Laravel: `POST /gateway/send` (always targets whichever number is currently `active`), `GET /gateway/status` (status of every number in the pool), `POST /gateway/reconnect`, plus number-management endpoints: `POST /gateway/numbers` (register a new number, returns a QR code to pair), `POST /gateway/numbers/:id/activate` (promote a standby/backup number to active — the failover action), `DELETE /gateway/numbers/:id`.
- **Super-admin UI** (in the platform admin panel, not merchant-facing) to: view all numbers in the pool and their live status/health, add a new number at any time (scan QR to pair), manually trigger failover to a backup number, and see which number is currently serving live traffic.
- Emit webhooks to Laravel on: incoming replies, connection status changes per number, delivery receipts, and — critically — a **ban/disconnect event on the active number**, which should trigger an immediate admin alert (SMS/email, not WhatsApp) and, ideally, an automatic or one-click failover to the next healthy backup.
- Queue-based sending (via Redis + Laravel queues) rather than direct synchronous calls, so gateway hiccups or a mid-flight failover don't block order processing — orders always succeed even if a WhatsApp send temporarily fails; failed sends retry with backoff (against the now-active number) and are logged.
- **Warm backups, not cold ones:** keep standby numbers lightly active (occasional low-volume sends, e.g. a small slice of real traffic rotated through them) rather than fully dormant, since a number with zero history activated suddenly at full volume is itself a ban-risk pattern. Build this warming behavior into the number-pool logic from the start.

### 6.3 Ban-risk mitigation (critical given the concentrated-risk architecture)
- **Strict rate limiting**, tuned conservatively and adjusted based on observed delivery/block rates — this is your single point of failure, so err toward slower sending over faster.
- **Transactional-only discipline:** never send marketing/promotional blasts through this number — order updates and OTPs only. This is both good practice for deliverability and directly relevant to whether customers report the number as spam.
- **Monitor delivery and block signals continuously**; build an admin alert (email/SMS, not WhatsApp) for connection drops or a spike in failed sends on the active number.
- **The number pool (§6.2) is your contingency plan** — 2–3 backup numbers registered and warmed in advance, with a super-admin-triggered (or eventually automatic) failover to promote a backup to active the moment the current one is banned or disconnected. Treat keeping at least one backup healthy and warmed at all times as an operational SLA for the business, not just an engineering nice-to-have.
- **Escape hatch:** keep the internal gateway API contract (`send`, `status`) stable so a future move to the official Meta Cloud API — likely worth revisiting once you have paying customers depending on this — only touches the gateway's internals, not Laravel or the rest of the app.

### 6.4 Message templates to build first
- New order alert (to merchant, always)
- Order confirmed / processing / shipped / delivered (to customer, plan-gated)
- Payment received confirmation (plan-gated for customer-facing; always sent to merchant)
- Payment/subscription reminder (to merchant)
- OTP for password reset (to merchant)

---

## 7. Payments

- **COD** as the universal default — must work with zero configuration.
- **JazzCash** and **Easypaisa** merchant APIs for online checkout — confirm current integration docs directly with each provider before building, since terms/endpoints change.
- **Subscription billing** (platform charging the merchant): support both automated card/wallet charging where the merchant opts in, and manual bank transfer + admin-approval queue as a fallback, since automated recurring payment adoption is still low among small merchants in this market.

---

## 8. Landing Page & Marketing Site

Build a genuinely polished, conversion-focused landing page — this is the platform's own storefront, and it's the first impression prospective merchants get.

**Sections to include:**
1. Hero — clear one-line value prop ("Launch your WhatsApp store in minutes — no website, no hosting needed") + primary CTA (Start Free Trial / See Pricing).
2. How it works — 3–4 step visual (create store → add products → share your link → get orders on WhatsApp).
3. **Live theme showcase with real demo stores** — for every available theme (Fashion, Cosmetics/Perfumes, Electronics, Decor), show a card with a preview image/description and a "View Demo Store" link that opens an actual working storefront (seeded with sample products) rendered in that theme, so prospective merchants can browse it exactly as their customers would. Include one additional card marked **"Coming Soon"** for a theme currently in development, with no working link yet — this signals ongoing investment in the platform and can be used to gauge interest (e.g. an "notify me" button) before you build it. Build the demo stores as regular tenants flagged `is_demo = true` so they use the exact same rendering path as real merchant stores — no separate demo-only code path to maintain.
4. **Pricing/plan comparison table** — pulled dynamically from the same `subscription_plans` data structure the admin manages (§10), not hardcoded, so plan changes reflect on the landing page automatically.
5. Testimonials/social proof (add once you have real merchants).
6. FAQ section addressing common concerns (do I need a website? what if I don't have a domain? how does WhatsApp messaging work?).
7. Footer with contact/support info.

Keep it fast-loading and mobile-first — your actual buyers (small shop owners) will very often view this on a phone.

---

## 9. Storefront Theme System

Build this as a **registry-based, extensible system** from day one so adding new themes later is a content task, not a code-rewrite.

**Initial themes to build:**
1. **Fashion** — large lifestyle imagery, editorial feel, prominent product photography.
2. **Cosmetics / Perfumes** — elegant, soft color palette, emphasis on product close-ups and branding.
3. **Electronics / Mobile & Accessories** — clean, spec-focused, grid-heavy layout with clear pricing and stock status.
4. **Decor** — warm, lifestyle-in-context imagery, category-led browsing.

**Architecture:**
- Each theme is a self-contained set of Blade/Vue components + a config file (`theme.json`) declaring: color palette variables, font choices, layout variant (grid vs. list product display), which sections are shown (banner, featured products, categories) and in what order.
- Store the merchant's selected theme + their customization overrides (logo, banner image, accent color) in a `tenant_theme_settings` table (see §10 schema addition) — never hardcode a merchant's storefront to a specific theme's code path; the storefront renderer looks up the tenant's theme choice and settings at request time.
- Adding a new theme later means adding a new folder to the theme registry and a `theme.json` — no changes to tenant records, order logic, or the rest of the platform.
- Theme availability can itself be plan-gated later (e.g. premium themes on higher tiers) since it's just another feature flag in §10.

**Theme selection & changing — lock/unlock model:**
- **All themes are available and freely browsable at initial signup** — during onboarding, a merchant picks any of the available themes for their first setup with no restriction, at no extra cost, regardless of plan.
- **Changing themes after the store is already set up and live is gated:** whether a merchant can switch themes later depends on their plan. This is a `theme_change_allowed` boolean feature flag on the plan (see §10) — plans without it require either a one-time fee (a simple paid add-on/upsell flow, tracked like a subscription payment but as a one-off charge) or an upgrade to a plan tier that includes free theme switching.
- Build this as: `PlanFeatureService->canChangeTheme($tenant)` → if true, allow the change directly from the dashboard; if false, show an upsell screen ("Upgrade to Growth plan for unlimited theme changes, or pay Rs. X to change your theme once") with both paths wired to actual billing, not just a message.
- This creates a clean upsell moment baked into the product itself, worth highlighting on the landing page's pricing table as a plan differentiator.

---

## 10. Data Model: Core Tables & Configurable Subscription Plans

This section covers both the core business schema and the plan/feature configuration system — treat plan configuration as first-class data the admin can edit without a deploy, since you asked to be able to adjust everything.

```
tenants
  id, name, store_slug, subdomain (nullable), subdomain_status (none/pending/active),
  custom_domain (nullable), category, logo_path,
  status (active/suspended/trial), subscription_plan_id,
  subscription_expires_at, created_at, updated_at

products
  id, tenant_id, name, description, price, compare_at_price,
  stock_quantity, sku, images (JSON array of paths), is_active,
  created_at, updated_at

product_variants
  id, product_id, option_name, option_value, price_override, stock_quantity

customers
  id, tenant_id, name, phone (E.164 format), address, created_at
  -- scoped per tenant, not global, for MVP simplicity

orders
  id, tenant_id, customer_id, order_number, status, subtotal,
  delivery_fee, total, payment_method, payment_status,
  delivery_address, notes, created_at, updated_at

order_items
  id, order_id, product_id, product_variant_id (nullable),
  quantity, unit_price, subtotal

whatsapp_message_log
  id, tenant_id, order_id (nullable), direction (inbound/outbound),
  recipient_phone, template_used, status (queued/sent/delivered/failed),
  error_message (nullable), created_at

subscription_plans
  id, name, slug, price_monthly, price_yearly, is_active,
  sort_order, created_at, updated_at

subscription_payments
  id, tenant_id, plan_id, amount, payment_method, status,
  gateway_reference, billing_period_start, billing_period_end

plan_features
  id, plan_id, feature_key, feature_value
  -- Row-per-feature design (not fixed columns) is what makes plans fully
  -- dynamic: create as many plans as you want, and toggle/set any registered
  -- feature independently per plan, with no schema change per new plan.
  -- feature_key examples (this list will grow — see the feature registry
  -- pattern below):
  -- 'product_limit' (int), 'order_limit_monthly' (int, nullable = unlimited),
  -- 'staff_logins_limit' (int),
  -- 'customer_whatsapp_notifications' (bool), 'message_quota_monthly' (int),
  -- 'analytics_enabled' (bool) -- turns the stats/analytics dashboard on/off
  -- 'custom_domain_allowed' (bool), 'subdomain_allowed' (bool),
  -- 'available_themes' (JSON list of theme slugs), 'premium_themes_allowed' (bool),
  -- 'theme_change_allowed' (bool) -- free unlimited theme switching after initial setup
  -- 'theme_change_fee' (nullable decimal) -- one-time fee if not included free in this plan

tenant_theme_settings
  id, tenant_id, theme_slug, accent_color, logo_override_path,
  banner_image_path, custom_settings (JSON), theme_last_changed_at

tenant_message_usage
  id, tenant_id, billing_period_start, billing_period_end,
  messages_sent_count, quota_limit, quota_exceeded_action
  -- quota_exceeded_action: 'block' | 'allow_overage_billed' | 'queue_until_next_period'
  -- reset each billing cycle; surfaced in both merchant dashboard (usage meter)
  -- and super-admin panel (cross-tenant usage monitoring, since all sends
  -- share the one active platform number's total throughput)

whatsapp_number_pool
  id, phone_number, status (active/standby/warming/banned/disconnected),
  session_data (encrypted), paired_at, last_active_at,
  warmup_started_at, notes
```

**Enforcement pattern:** build a single `PlanFeatureService` (or similar) that every part of the app queries — "does this tenant's active plan allow X?" — rather than scattering plan checks across controllers. This is what makes "adjust everything for plans" actually maintainable: one place to check, one place to change when you add a new plan or a new gate-able feature.

**Feature registry pattern — so "add whatever I want" scales as the product grows:** maintain a single, central list in code (a `FeatureRegistry` — could be a simple config file or a `feature_definitions` table) declaring every feature key that exists, its type (bool/int/decimal/JSON), a human-readable label, and a default value. The **super-admin "Create/Edit Plan" screen reads from this registry automatically** — every registered feature shows up as a toggle, number input, or selector on that screen, for every plan, with no code change needed to use it on a new plan. This is the difference between "unlimited plans" (already true with the row-per-feature schema above) and "unlimited *kinds* of plan feature" — the registry is what lets you keep adding new gate-able capabilities (a future one might be "priority support," "bulk product import," whatever comes up) as your product evolves, and have them immediately available across every plan, past and future, from the same admin screen.

Examples of what should be gated this way from the start:
- Product count limit
- **Monthly order limit** (nullable = unlimited on higher tiers)
- **Analytics/stats dashboard** on or off entirely
- Whether customer-facing WhatsApp messages are sent at all
- Monthly message quota (even for merchant-facing messages, at high tiers)
- Subdomain / custom domain access
- Number of staff logins
- Which themes are available (basic set vs. premium themes)
- Whether a merchant can change their theme after initial setup, free vs. fee-gated (§9)
- Monthly WhatsApp message quota — tracked in `tenant_message_usage`, checked before every send alongside the `customer_whatsapp_notifications` toggle, since quota management doubles as a tool for controlling total send volume through the single shared number (§6)

---

## 11. Non-Functional Requirements

- **Performance:** storefront pages must load fast on budget Android phones over 3G/4G.
- **Language:** English + Urdu for merchant dashboard and landing page; WhatsApp templates in Urdu/Roman Urdu variants.
- **Security:** protect the internal gateway API (key/secret + IP allowlist per §5B), sanitize all storefront inputs, signed URLs for uploaded media.
- **Observability:** log every WhatsApp send with status; admin dashboard surfaces gateway health prominently given the single-point-of-failure architecture in §6.
- **Backups:** automated daily MySQL backups; back up the platform's Baileys session data too, since losing it takes down messaging for every tenant at once until re-paired.

---

## 12. Suggested Build Order for the AI Coding Tool

1. Laravel scaffold + tenant resolution middleware (path-based first, with `subdomain_status` field ready on the tenants table) + admin auth, including the super-admin "subdomain requests" queue for the semi-manual provisioning workflow in §5A.
2. Subscription plan + plan-feature data model and the `PlanFeatureService` enforcement pattern — build this early since almost everything else checks against it.
3. Landing page (can be built in parallel by a second work stream since it has minimal dependency on the rest).
4. Merchant onboarding + product catalog + theme system with the first theme (pick one, e.g. Fashion) fully working end-to-end before building the other three.
5. Remaining 3 themes, reusing the proven registry pattern.
6. Node/Baileys gateway as a standalone repo — define the internal REST API contract first, deploy it as one of your 5 Node.js app slots on Hostinger (§5B); make sure API auth (key/secret) is in place between it and Laravel even though both run on the same host.
7. Wire Laravel ↔ Gateway integration, order flow end-to-end, plan-gated customer notifications.
8. Billing + admin approval queue for manual payments.
9. Analytics, delivery tracking, multi-staff roles.

---

## 13. Open Questions to Resolve Before/During Build

- Confirmed: subdomain provisioning is deliberately semi-manual (§5A) — not pursuing the Hostinger API automation route; the super-admin "pending → active" queue is a permanent part of the product, not a stopgap. Still worth deciding: what turnaround-time expectation to set for merchants requesting a subdomain.
- Confirmed: the WhatsApp gateway will run as one of your 5 Node.js app slots on Hostinger, not a separate VPS — build in automatic reconnect-from-stored-session logic (§5B, §6.2) since managed Node hosting can recycle processes; keep an eye on per-app resource limits as volume grows.
- Confirmed: message quotas are plan-configurable and system-managed (`tenant_message_usage`, §10) — still to decide: what happens when a tenant hits their quota mid-cycle by default (block vs. bill overage vs. queue) — pick a sane default per plan, adjustable by admin.
- Confirmed: 2–3 backup WhatsApp numbers, managed from a super-admin number-pool view with the ability to add new numbers anytime (§6.2) — still to decide: automatic failover on ban-detection vs. always requiring a manual admin click to promote a backup (recommend starting manual, since automated failover to a number that itself might have issues needs more confidence in your ban-detection signal quality first).
- Confirmed: all themes browsable/selectable at signup via live demo stores on the landing page, with a "Coming Soon" card for themes in development; theme changes after setup are plan-gated (free on some plans, fee-gated on others) via `theme_change_allowed` / `theme_change_fee` (§9, §10) — still to decide: the actual fee amount and which plan tiers include free switching.
- Confirmed: pricing is fully dynamic — unlimited plans, each with any combination of registered features (product limit, monthly order limit, analytics on/off, theme-change allowed + fee, and more) via the row-per-feature schema plus the central feature registry that automatically surfaces every capability on the plan-builder screen (§10). Still to decide: the concrete number and names of your initial tiers and which features apply to each — worth doing as a spreadsheet before seeding plan data, since it directly becomes your landing-page pricing table.
