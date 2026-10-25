# 🛍️ AsaanDukan — Multi-Tenant E-Commerce SaaS Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Inertia.js-Vue%203-9553E9?style=for-the-badge&logo=inertia" alt="Inertia Vue">
  <img src="https://img.shields.io/badge/TailwindCSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss" alt="TailwindCSS">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

---

## 📖 What is AsaanDukan?

**AsaanDukan** is a cloud-ready, **multi-tenant e-commerce SaaS engine** built with **Laravel 12, Inertia.js, and Vue 3**. It allows independent entrepreneurs, retailers, and merchants to launch branded online digital storefronts in minutes under custom domains or automated subdomains.

The platform provides a centralized, multi-tenant administrative ecosystem alongside customizable customer-facing storefronts with shopping carts, coupon engines, order tracking, and payment gateways.

---

## 🎯 Why AsaanDukan? Problem & Solution

| The Challenge | How AsaanDukan Solves It |
| :--- | :--- |
| **High Barrier to Entry:** Setting up bespoke e-commerce websites requires substantial engineering, server setup, and maintenance. | Provides instant self-service digital storefront provisioning with subdomains (`store.domain.com`) or custom vanity domains. |
| **Monolithic Store Management:** Managing thousands of stores on isolated databases is difficult to scale and maintain. | Employs scalable multi-tenancy middleware that seamlessly isolates store data, settings, products, and analytics per tenant. |
| **Poor Mobile Checkout UX:** Slow, multi-page page reloads lead to high cart abandonment rates. | Delivers a Single Page Application (SPA) experience powered by **Inertia.js and Vue 3** for fluid, app-like navigation and rapid order checkout. |

---

## 🌟 Key Features

### 🛒 Customer Storefront Experience
- **Fluid Single-Page Navigation:** Powered by Inertia.js & Vue 3 with seamless zero-reload transitions.
- **Dynamic Product Showcase:** Multi-variant inventory, rich product descriptions, image galleries, and pricing badges.
- **Smart Cart & Coupon Engine:** Instant discount computation, coupon code validation, and persistent cart sessions.
- **One-Page Checkout:** Streamlined guest or customer checkout with shipping address validation and order notes.
- **Live Order Tracking:** Public tracking terminal allowing customers to check fulfillment, dispatch, and delivery stages by order number.

### 🏢 Multi-Tenant Architecture & Domain Engine
- **Custom Domain Mapping:** Automatic resolution and SSL routing for custom merchant domains (`mybrand.com`).
- **Subdomain Routing:** Dedicated path and subdomain handling (`{store}.asaandukan.com`).
- **Subscription Lifecycle:** Tiered merchant subscription plans with automated expiration safeguards and feature gates.

### 🛡️ Super-Admin Command Center
- **Merchant Management:** Provision, suspend, activate, and review store owners.
- **Domain Approval Workflow:** Review vanity domain purchase/pointing requests and issue quotes.
- **Plan & Subscription Billing:** Manage pricing tiers, payment receipt verification, and manual billing extensions.
- **WhatsApp Broadcast Suite:** Send notifications, alerts, and promotional announcements directly to merchants.
- **Admin Tenant Impersonation:** Secure 1-click admin login into merchant instances for technical troubleshooting.

---

## 🛠️ Technical Stack

- **Backend Framework:** Laravel 12.x (PHP 8.2+)
- **Frontend Reactive Layer:** Inertia.js v2, Vue 3.4 (Composition API), Ziggy
- **UI & Styling:** TailwindCSS v4, Autoprefixer, VueQuill Editor
- **Database & Cache:** MySQL 8.0+ / MariaDB, Redis (Predis)
- **Authentication:** Laravel Breeze, Laravel Sanctum

---

## 🚀 Getting Started

### 1. Prerequisites
- PHP 8.2 or 8.3+ with extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `redis`
- Composer 2.x
- Node.js 18+ and NPM
- MySQL 8.0+

### 2. Installation Steps
```bash
# Clone the repository
git clone https://github.com/ranaumairnawaz/AsaanDukanMainApp.git
cd AsaanDukanMainApp

# Install backend dependencies
composer install

# Install frontend dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate
```

### 3. Database & Assets
```bash
# Run database migrations and seeders
php artisan migrate --seed

# Build frontend assets
npm run build
```

### 4. Serve the Application
```bash
# Start local PHP server
php artisan serve
```

---

## 👨‍💻 Author
**Rana Umair Nawaz**  
- [GitHub Profile](https://github.com/ranaumairnawaz)  
- [LinkedIn Profile](https://linkedin.com/in/ranaumairnawaz)
