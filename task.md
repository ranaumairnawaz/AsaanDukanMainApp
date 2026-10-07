# Tasks: MeriDukan E-Commerce SaaS Build

## Phase 1: Foundation
- [x] Initialize Laravel 11 app structure in the workspace
- [x] Implement database migrations for core schema (`tenants`, `products`, etc.)
- [x] Create `BelongsToTenant` trait and `ResolveTenant` middleware
- [x] Build the platform admin panel skeleton and super-admin subdomain requests queue
- [x] Build the `PlanFeatureService` and `FeatureRegistry` implementation
- [x] Create public landing page with static pricing table (pulled dynamically later)
- [x] Implement merchant onboarding wizard and auth (Breeze/Fortify) using WhatsApp OTP scaffold

## Phase 2: Store Builder & Themes
- [x] Create Product CRUD dashboard for merchants (name, description, images, categories)
- [x] Implement `ThemeRegistry` service and theme settings storage per tenant
- [x] Build storefront template structure rendering products using the active theme
- [x] Implement the first theme: **Fashion**
- [x] Implement other themes: **Cosmetics**, **Electronics**, **Decor**
- [x] Add theme selection/lock-unlock model logic (`theme_change_allowed` checks)

## Phase 3: Orders & WhatsApp Gateway
- [ ] Design customer storefront cart and checkout pages
- [ ] Create Node.js standalone service with Express + Baileys for WhatsApp connection
- [ ] Implement WhatsApp pool management API endpoints (QR pairing, status, failover)
- [ ] Integrate Laravel queues with Redis to handle asynchronous message dispatching
- [ ] Set up webhooks for gateway connection alerts, replies, and active number ban event
- [ ] Wire Laravel to gateway and implement plan-gated notifications for order states

## Phase 4: Subscriptions, Plans & Billing
- [ ] Create super-admin interfaces to manage subscription plans and feature variables
- [ ] Implement pricing tables pulling dynamically from database plans
- [ ] Set up billing dashboard for tenants including JazzCash/Easypaisa integrations
- [ ] Add manual bank transfer support and super-admin manual approval queue
- [ ] Write background command/job to monitor subscription expiration, grace periods, and auto-suspension

## Phase 5: Polish & Analytics
- [ ] Add basic dashboard analytics for merchants (orders, total sales, top products)
- [ ] Build multi-staff login features with basic role authorization
- [ ] Add delivery tracking fields (manual inputs first)
- [ ] Perform end-to-end user testing (onboarding -> store setup -> product add -> checkout -> WhatsApp notification)
