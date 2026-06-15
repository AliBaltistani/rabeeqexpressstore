# Frontend Modifications — Implementation Plan

This plan covers 10 major frontend modification requests for the Rabeq Express Store Vue 3 + Pinia storefront. Changes are grouped into two phases: **Phase 1** (frontend-only, no new backend APIs needed) and **Phase 2** (requires new backend endpoints / 3rd-party integrations).

> [!IMPORTANT]
> **Items 9 (Passwordless Login Modal) and 10 (Notifications, Wallet, Loyalty Points)** require significant backend work — new Laravel API endpoints, database migrations, Filament admin resources, and third-party integrations (e.g., OTP service, Google OAuth). These are out of scope for this implementation pass and will be documented as stubs/placeholders on the frontend. A separate backend implementation plan should be created for those.

> [!WARNING]
> **Google Maps integration (Item 8)** requires a valid Google Maps API key configured in the environment. The implementation will use the Google Maps JavaScript SDK with Places Autocomplete. The key must be provided by the admin.

---

## Phase 1: Frontend-Only Changes [Completed]

### 1. Search Bar — Live Results with Loader & No-Results

#### [MODIFY] [SearchModal.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/common/SearchModal.vue)

**Current:** Search input only — submits to search results page on Enter. No live dropdown.

**Changes:**
- Add debounced `watch` on `searchQuery` (300ms delay) that calls [searchProducts(q)](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#89-92) from API services
- Show a **results dropdown** below the search input with product image, name, and price (matching the provided screenshot)
- Show a **loader spinner** while the API call is in progress
- Show **"No results found"** state when results come back empty
- Clicking a result navigates to `/product/:slug` and closes the modal
- Keep existing submit-on-Enter behavior to navigate to search results page
- Style: white dropdown card below input, each result row = thumbnail (60×60) + name + price

---

### 2. Mobile Nav Categories Fix [Completed]

#### [MODIFY] [MobileMenu.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/common/MobileMenu.vue)

**Current:** `MobileMenu` receives `menuCategories` as a prop. The issue is likely that the parent component isn't passing the categories. [DONE]
**Changes:**
- Investigate parent (`MainNavigation.vue` or `BottomNavigation.vue`) to see how `MobileMenu` is invoked
- If categories aren't passed, import [useMenuCategories](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/composables/useMenuCategories.ts#13-40) composable directly inside `MobileMenu` as a fallback
- Add a fallback: if `props.menuCategories` is empty, use [useMenuCategories().menuItems](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/composables/useMenuCategories.ts#13-40) directly
- Verify the categories render correctly with drill-down navigation

---

### 3. Product Card — Spinners & Add-to-Cart Confirmation [Pending]

#### [MODIFY] [ProductCard.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/home/ProductCard.vue)

**Changes:**
- Add loading states: `addingToCart`, `togglingWishlist`, `openingQuickView` refs
- Show **spinner icon** on each button while loading (replace icon SVG with a CSS spin animation)
- On successful add-to-cart: show an **"Added to Cart" confirmation popup/toast** that appears at the top of the page with:
  - Green check icon + "Added to Cart" title
  - Product image, name, price, variant info
  - "Submit order" and "View Cart" buttons
  - Auto-dismiss after 5 seconds
- Cart count in the header updates immediately via `cartStore.itemCount` reactivity
- **Fly-to-cart animation**: Create a floating clone of the product image that animates toward the cart icon position using CSS `transform` + `transition`. This is a lightweight CSS animation, not a physics simulation.

---

### 4. Scroll to Top — Left-Aligned Circular Progress Bar

#### [MODIFY] [ScrollToTop.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/common/ScrollToTop.vue)

**Changes:**
- Move button to **left side** of the browser (currently right on desktop, already left on mobile)
- Add **circular progress indicator** using SVG `<circle>` with `stroke-dasharray`/`stroke-dashoffset` based on scroll percentage
- Calculate scroll progress: `scrollY / (documentHeight - viewportHeight)`
- Arrow icon stays in the center of the progress ring
- Mobile: already on the left, just add the progress ring

---

### 5. Product Variants in QuickView & ProductDetail

#### [MODIFY] [QuickViewModal.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/product/QuickViewModal.vue)

**Changes:**
- Read `product.variants` (already available from API — the `ProductDetail` response includes `variants[]`)
- For each variant group (extracted from variant attributes), render a **dropdown selector** (label + `<select>`)
- When a variant is selected, update `selectedVariantId` to pass to [addToCart](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/ProductDetailPage.vue#263-266)
- Show variant name/attribute labels dynamically

#### [MODIFY] [ProductDetailPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/ProductDetailPage.vue)

**Changes:**
- Currently maps variants to just `sizes` array with `v.name || v.sku`. This loses attribute info.
- Refactor to store full variant objects: `product.value.variants = data.variants || []`
- Build variant attribute groups from variant data (e.g., "Measurement", "Color")
- Render each attribute group as a labeled dropdown (matching the screenshot showing "Measurement * Choose")
- On variant selection, update `selectedVariantId`, and optionally update price if variant has its own price

---

### 6. Product Detail Tabs & Comment System

#### [MODIFY] [ProductDetailPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/ProductDetailPage.vue)

**Layout fix:**
- Change `.pdp-tabs` to vertical layout with right-border active indicator (matching screenshot style)
- Use `border-right` instead of `border-bottom` for active tab

**Comment/Review form:**
- Add a review submission form inside the `rating` tab content:
  - Star rating selector (clickable 1-5 stars)
  - Comment textarea
  - Submit button (calls existing [submitReview](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#100-103) API)
  - Show success/error feedback
  
**Rating breakdown:**
- Compute rating counts per star (5★ = X, 4★ = Y, etc.) from reviews data
- Show horizontal bar chart with counts

**Reviews list enhancement:**
- Add user avatar placeholder (first letter of name in a circle)
- Show rating, username, date, and comment body

---

### 7. Cart Page — Product Attribute Display

#### [MODIFY] [CartPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/CartPage.vue)

**Changes:**
- Check if cart item has variant info (the [CartItem](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#122-125) type likely includes `variantName` — already rendered)
- If product has variant attributes (size, color), show them as read-only labels under the product name
- The API [CartItem](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/api/services.ts#122-125) already returns `variantName` — display this prominently if present

---

### 8. Checkout Page Improvements

#### [MODIFY] [CheckoutPage.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/pages/CheckoutPage.vue)

#### [MODIFY] [router/index.ts](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/router/index.ts)

**8a. Hide Header/Footer:**
- Move checkout route out of `DefaultLayout` children and give it its own route entry with no layout wrapper (or a minimal `CheckoutLayout.vue`)

**8b. Email OTP Verification (Registration):**
- After successful registration, show OTP input (4 digit boxes)
- Call new API endpoint `POST /auth/verify-email-otp` (needs backend — stub for now)
- Auto-focus next input on digit entry

**8c. Phone Country Code Dropdown:**
- Add a country code select (`+966`, `+1`, etc.) with flag emoji
- Use a built-in static list of country codes (JSON data)
- Prepend to the phone input field

**8d. Google Maps for Shipping Address:**
- Add a Google Maps embed with Places Autocomplete search box
- On address selection, auto-fill the address form fields
- Add "Current location" button
- Requires `VITE_GOOGLE_MAPS_KEY` env variable

**8e. Shipping Extras:**
- Add **Building Description** (optional) text input
- Add **"Deliver order to someone else?"** toggle with:
  - Recipient's Name, Phone, Email (Optional)
- Add **"Get order updates via SMS"** checkbox
- Add **"Enter Address Manually"** fallback with country dropdown + National Address input

---

## Phase 2: Backend-Required Features (Frontend Stubs Only)

### 9. Login Modal (Passwordless OTP)

> [!NOTE]
> This requires backend: OTP generation/verification endpoints, social OAuth providers. Frontend modal will be built with UI stubs.

#### [NEW] [LoginModal.vue](file:///c:/wamp64/www/laravel_pro/eseven-store/frontend/src/components/common/LoginModal.vue)

- Step 1: Email input → "Enter" button → social login (Google, Facebook, Apple)
- Step 2: 4-digit OTP → "Verify" → countdown timer
- Wired to new API endpoints (placeholder calls)

### 10. User Account Features

> [!NOTE]  
> Notifications, Pending Payments, Wallet, and Loyalty Points all require full backend implementation (models, migrations, admin resources, API endpoints). These will be built as frontend page shells with placeholder data.

---

## Verification Plan

### Browser Testing
Since there are no existing frontend tests, verification will be done via manual browser testing with the dev server (`npm run dev` already running on the frontend).

1. **Search Bar**: Open the app → click search icon → type "test" → verify dropdown shows products with images/prices, loader appears while fetching, "no results" shown for nonsense queries
2. **Mobile Nav**: Resize browser to mobile width (< 768px) → open mobile menu → verify categories appear and drill-down works
3. **Product Card**: Click "Add to Cart" on any product → verify spinner shows, cart count updates, confirmation popup appears
4. **Scroll to Top**: Scroll down on any page → verify circular progress button appears on the left side with progress ring
5. **Product Variants**: Navigate to a product with variants (e.g., shoes with sizes) → verify dropdown appears in both quick view and detail page
6. **Product Detail Tabs**: Go to a product detail page → click "Rating" tab → verify the form, rating breakdown, and reviews list
7. **Cart Attributes**: Add a product with variants to cart → go to cart page → verify variant info is displayed
8. **Checkout**: Go to checkout → verify no header/footer, country code dropdown on phone field, and extra shipping fields

### User Manual Testing Required
- Google Maps integration needs a valid API key — user should confirm key availability
- OTP/passwordless login needs backend endpoints — user should verify after backend is ready
