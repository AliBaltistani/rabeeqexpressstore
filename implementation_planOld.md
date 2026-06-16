# Complete Remaining Dynamic Frontend Pages (Steps 2-4)

Complete all remaining placeholder pages to make the Raqeeb Express Store  frontend fully dynamic.

## Current State

Step 1 (Account pages) is ✅ complete: `WishlistPage`, `AddressesPage`, `ProfilePage`, `OrdersListPage`, `OrderDetailPage`.

A `SearchModal` component was created and wired into [DefaultLayout.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/layouts/DefaultLayout.vue). The [SearchResultsPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/SearchResultsPage.vue) was corrupted by a bad file copy and needs a full rewrite.

All backend API endpoints already exist. All [services.ts](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts) functions are ready.

## Proposed Changes

### Step 2: Extended Catalog & Search

---

#### [NEW] [SearchResultsPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/SearchResultsPage.vue)
- **Overwrite** the corrupted file with a clean search results page
- Reads `?q=` from the route query params
- Calls [fetchProducts({ search: q })](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#68-71) to get results
- Reuses the same product grid markup and [mapProduct](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/CategoryPage.vue#332-345) helper from `CategoryPage`
- Includes sort dropdown and pagination
- Shows "No results found" empty state

#### Brand Page — Already handled
- The router already maps `/brand/:slug` to [CategoryPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/CategoryPage.vue) — this works because `CategoryPage` detects the route name and passes the `brand` param. **No changes needed.**

#### [MODIFY] [FlashSalePage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/FlashSalePage.vue)
- Replace stub with a dynamic page
- Calls [fetchActiveFlashSale()](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#104-114) → product grid + countdown timer
- Shows product cards with sale prices and discount badges

---

### Step 3: Content & CMS

---

#### [MODIFY] [CmsPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/CmsPage.vue)
- Replace stub with dynamic page using [fetchCmsPage(slug)](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#263-269)
- Renders `page.title` and `page.content` (HTML body)
- Catches all unmatched slugs via the existing `/:slug` route

#### Blog Pages (new files + route registration)

#### [NEW] [BlogListPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/BlogListPage.vue)
- Paginated list of blog posts via [fetchBlogPosts(page)](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#248-254)
- Card layout with image, title, excerpt, date

#### [NEW] [BlogPostPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/BlogPostPage.vue)
- Single blog post via [fetchBlogPost(slug)](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#255-258)
- Full content rendering with title, image, date, body

#### [MODIFY] [router/index.ts](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/router/index.ts)
- Add `/blog` route → `BlogListPage`
- Add `/blog/:slug` route → `BlogPostPage`
- These must be placed **above** the `/:slug` catch-all CMS route

---

### Step 4: Global Features & Final Polish

---

#### [MODIFY] [ResetPasswordPage — NEW](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/ResetPasswordPage.vue)
- Create page to handle password reset tokens from email links
- Reads `token` and `email` from URL query
- Calls [resetPasswordApi()](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#165-168)

#### [MODIFY] [router/index.ts](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/router/index.ts)
- Add `/reset-password` route → `ResetPasswordPage`

#### [MODIFY] [en.json](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/i18n/en.json) / [ar.json](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/i18n/ar.json)
- Add translation keys for search, blog, CMS, flash-sale, and reset-password sections

#### [MODIFY] [task.md](file:///C:/Users/PMLS/.gemini/antigravity/brain/24e93f83-10b3-4a72-98c7-8a353853b88a/task.md)
- Track progress as items are completed

## Verification Plan

### Manual Verification
- Navigate to `/search?q=test` — should show product results or "no results"
- Navigate to `/flash-sale` — should show active flash sale products
- Navigate to `/blog` — should show blog post cards
- Navigate to `/blog/{slug}` — should show full blog post
- Navigate to `/about-us` or `/privacy-policy` — should show CMS content
- Navigate to `/reset-password?token=xxx&email=xxx` — should show reset form
