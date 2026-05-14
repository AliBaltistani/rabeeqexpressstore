# Phase 12: Frontend Implementation Tracking & Plan

This document tracks the progress of the Vue.js Frontend dynamic migration (Phase 12) according to the `Eseven_Store_Complete_Project_Blueprint.md` and outlines the step-by-step plan for the remaining work.

> [!IMPORTANT]
> **Strict Requirement:** We must strictly follow the *current design pattern*. We will *not* introduce extra styling or design changes. Our focus is exclusively on connecting dynamic data via APIs and ensuring no hardcoded data remains.

---

## 📊 Current Status

### 🟢 Fully Implemented & Dynamic
*   **Project Setup:** Vite + Vue 3 + TS, Pinia, Axios, Vue Router, i18n (EN/AR).
*   **Core Stores:** `settingsStore`, `cartStore`, `authStore` fully connected to the backend.
*   **Authentication Flow:** `LoginPage`, `RegisterPage`, `ForgotPasswordPage`.
*   **Catalog & Shopping:**
    *   `HomePage` (using dynamic settings/init API).
    *   `CategoryPage` / `ShopPage` (filters, sorting, pagination, multi-language/currency).
    *   `ProductDetailPage` (reviews, related products, variant sizing, gallery).
*   **Checkout Flow:**
    *   `CartPage` (dynamic item calculation, quantity updates).
    *   `CheckoutPage` (address collection, dynamic shipping rates, payment selection).
    *   `OrderSuccessPage` (dynamic order confirmation).
*   **Account Basics:**
    *   `AccountDashboardPage` (Recent orders, profile summary).

### 🔴 Pending Implementation (To-Do)
*   **Remaining Public Pages:**
    *   `BrandPage` (`/brand/{slug}`)
    *   `SearchResultsPage` (`/search`)
    *   `FlashSalePage` (`/flash-sale`)
    *   `BlogListPage` (`/blog`) & `BlogPostPage` (`/blog/{slug}`)
    *   `CmsPage` (`/{slug}` for About Us, Privacy Policy, etc.)
*   **Remaining Auth Pages:**
    *   `ResetPasswordPage` (`/reset-password`)
*   **Remaining Account Pages:**
    *   `ProfilePage` (`/account/profile`)
    *   `OrdersListPage` (`/account/orders`)
    *   `OrderDetailPage` (`/account/orders/{orderNumber}`)
    *   `WishlistPage` (`/account/wishlist`)
    *   `AddressesPage` (`/account/addresses`)
*   **Remaining Features:**
    *   `wishlistStore` (Pinia state for adding/removing wishlist items dynamically).
    *   Global features: Stripe payment handling, WhatsApp floating button, SEO dynamic meta tags, dynamic Announcement Banner ticker.

---

## 🗺️ Step-by-Step Execution Plan

We will proceed iteratively through the remaining tasks to ensure type-safety and visual consistency without breaking the current UI design.

### Step 1: User Account & Profile Completion
1.  **Wishlist System**: Implement `wishlistStore.ts`, bind the heart icons on `ProductDetailPage` and `CategoryPage`, and create the `WishlistPage` to render saved items.
2.  **Addresses Management**: Create `AddressesPage` allowing users to view, add, edit, and delete their saved addresses via the `/api/v1/addresses` endpoints.
3.  **Profile Editing**: Create `ProfilePage` linking to the `/profile` and `/profile/password` PUT endpoints.
4.  **Order History**: Build `OrdersListPage` with pagination and `OrderDetailPage` for deep-dive tracking of specific orders.

### Step 2: Extended Catalog & Search
1.  **Search Functionality**: Create `SearchResultsPage`. Implement the top navbar search bar to route here and fetch products using `?search=` query parameters.
2.  **Brand Pages**: Build `BrandPage`. It will reuse the structure of `CategoryPage` but filter specifically by the `brand` parameter.
3.  **Flash Sales**: Build `FlashSalePage` to display currently active flash sale products with their respective countdown timers.

### Step 3: Content & CMS
1.  **Blog**: Implement `BlogListPage` and `BlogPostPage` connecting to the `/blog/posts` APIs.
2.  **CMS Pages**: Create a generic `CmsPage` component that dynamically catches unhandled slugs and fetches content from `/pages/{slug}` (e.g., Privacy Policy).

### Step 4: Global Features & Final Polish
1.  **Announcement & WhatsApp**: Bind the scrolling announcement ticker and the floating WhatsApp button to `settingsStore` data.
2.  **Password Reset Flow**: Implement `ResetPasswordPage` to catch password reset tokens from email links.
3.  **Checkout Payment Gateway**: Implement Stripe.js logic inside `CheckoutPage` for processing actual card tokens when "Stripe" is selected.
4.  **SEO Optimization**: Inject `@vueuse/head` to dynamically update the `<title>` and `<meta>` tags based on the current product, category, or CMS page.

---
*Ready to begin Step 1 (Wishlist System & User Account Completion)?*
