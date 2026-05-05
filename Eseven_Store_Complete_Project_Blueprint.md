# 🛍️ Eseven Store — Complete Project Blueprint
### Laravel 13 + Filament V5 + Livewire 4 (Admin) · Vue.js (Frontend)
> **Strategy:** Build backend + Admin Panel first → then expose API → then plug in Vue.js frontend.

---

## 📐 TECH STACK OVERVIEW

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 13 |
| Admin Panel | Filament V5 |
| Admin Reactivity | Livewire 4 |
| Frontend (User Site) | Vue.js 3 (Composition API + Pinia + Vue Router) |
| API Layer | Laravel RESTful API (JSON, Sanctum auth) |
| Database | MySQL 8+ |
| Cache / Queue | Redis |
| File Storage | Laravel Storage (S3-compatible or local) |
| Search | Laravel Scout + Meilisearch (optional) |
| Payments | Stripe, PayPal, COD, Direct Bank Transfer |
| Localization | Arabic (RTL) + English (LTR) |
| Multi-Currency | SAR, AED, BHD, KWD, QAR |
| Email | Laravel Mail + Mailgun/SMTP |
| SMS (optional) | Twilio / Unifonic |

---

## 🗂️ PROJECT PHASES AT A GLANCE

```
Phase 1 → Environment & Laravel Project Setup
Phase 2 → Database Schema Design (all tables)
Phase 3 → Core Models & Relationships
Phase 4 → Admin Auth (Filament + Roles/Permissions)
Phase 5 → Admin Dashboard (Stats + Charts)
Phase 6 → Catalog Module (Products, Categories, Brands)
Phase 7 → Sales Module (Orders, Customers, Reviews)
Phase 8 → Promotions Module (Coupons, Flash Sales)
Phase 9 → Content Module (Blog, CMS Pages, Banners/Sliders)
Phase 10 → Settings Module (Global, Languages, Currencies, Payments, Shipping, Email, SEO)
Phase 11 → Laravel API Layer (for Vue.js frontend)
Phase 12 → Vue.js Frontend (User-facing site)
Phase 13 → Testing, Optimization & Deployment
```

---

# ═══════════════════════════════
# PHASE 1 — ENVIRONMENT & PROJECT SETUP
# ═══════════════════════════════

## PROMPT 1.1 — Initialize Laravel Project

```
Create a new Laravel 13 project named "eseven-store".
Install the following packages via Composer:
- filament/filament (V5)
- livewire/livewire (V4)
- spatie/laravel-permission (roles & permissions)
- spatie/laravel-translatable (model translations for EN/AR)
- spatie/laravel-medialibrary (product image management)
- spatie/laravel-sluggable (auto-slugs)
- spatie/laravel-activitylog (admin audit trail)
- laravel/sanctum (API token auth for Vue frontend)
- stripe/stripe-php
- srmklive/paypal (PayPal integration)
- intervention/image (image resizing)
- barryvdh/laravel-dompdf (invoice PDF generation)
- maatwebsite/laravel-excel (CSV export for orders/products)
- stichoza/google-translate-php (optional auto-translation helper)

Configure .env:
- Set APP_NAME=EsevenStore
- Set APP_LOCALE=en, APP_FALLBACK_LOCALE=en
- Configure MySQL database connection
- Configure Redis for CACHE_DRIVER and QUEUE_CONNECTION
- Add placeholder keys for: STRIPE_KEY, STRIPE_SECRET, PAYPAL_CLIENT_ID, PAYPAL_SECRET
- Configure MAIL_MAILER settings

Run: php artisan key:generate
Run: php artisan storage:link
```

## PROMPT 1.2 — Install & Configure Filament V5

```
Install Filament V5 admin panel:
- Run: php artisan filament:install --panels
- Create a panel named "admin" with path prefix "/admin"
- The admin panel brand name should be "Eseven Store Admin"
- Set the admin panel to use dark sidebar navigation style 
  (dark navy/charcoal sidebar, white content area — matching the Porto Shop reference)
- Configure the panel to support RTL layout toggling based on selected admin language
- Set up Filament's default avatar provider
- Register the AdminPanelProvider in bootstrap/providers.php
- Set login page route to: /admin/login
- Disable Filament's user registration (admin-only access)
```

---

# ═══════════════════════════════
# PHASE 2 — DATABASE SCHEMA DESIGN
# ═══════════════════════════════

## PROMPT 2.1 — Core & Auth Tables

```
Create migrations for the following tables in this exact order:

1. ADMINS table:
   - id, name, email (unique), password, avatar (nullable),
     email_verified_at, remember_token, is_active (boolean, default true),
     last_login_at, timestamps, softDeletes

2. ROLES & PERMISSIONS (via spatie/laravel-permission):
   - Run: php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   - Roles: Super Admin, Manager, Editor, Support

3. USERS table (for storefront customers):
   - id, name, email (unique), password, phone (nullable),
     email_verified_at, avatar (nullable), is_active (boolean, default true),
     is_banned (boolean, default false), ban_reason (nullable),
     remember_token, timestamps, softDeletes

4. USER_ADDRESSES table:
   - id, user_id (FK), label (Home/Work/Other), first_name, last_name,
     phone, address_line_1, address_line_2 (nullable), city, state,
     country, postal_code, is_default (boolean), timestamps
```

## PROMPT 2.2 — Catalog Tables

```
Create migrations for catalog tables:

5. CATEGORIES table:
   - id, parent_id (nullable, self-referencing FK for subcategories),
     slug (unique), image (nullable), is_active (boolean, default true),
     sort_order (integer, default 0), timestamps, softDeletes
   - Add JSON columns: name (translatable: en, ar), description (translatable: en, ar)

6. BRANDS table:
   - id, name, slug (unique), logo (nullable), description (text, nullable),
     is_active (boolean), sort_order (integer), timestamps

7. PRODUCTS table:
   - id, sku (unique), slug (unique), category_id (FK), brand_id (nullable, FK),
     product_type (enum: simple, variable), price (decimal 10,2),
     compare_price (decimal 10,2, nullable — for showing strikethrough price),
     cost_price (decimal 10,2, nullable),
     stock_quantity (integer, default 0), low_stock_threshold (integer, default 5),
     track_stock (boolean, default true), allow_backorders (boolean, default false),
     weight (decimal, nullable), is_active (boolean, default true),
     is_featured (boolean, default false), is_new (boolean, default false),
     sort_order (integer, default 0),
     meta_title (nullable), meta_description (nullable), meta_keywords (nullable),
     timestamps, softDeletes
   - Add JSON columns: name (translatable), short_description (translatable),
     description (translatable)

8. PRODUCT_IMAGES table:
   - id, product_id (FK), image_path, alt_text (nullable), sort_order, is_primary (boolean)

9. PRODUCT_ATTRIBUTES table (for sizes, colors etc.):
   - id, name (JSON translatable), timestamps

10. PRODUCT_ATTRIBUTE_VALUES table:
    - id, attribute_id (FK), value (JSON translatable), timestamps

11. PRODUCT_VARIANTS table:
    - id, product_id (FK), sku (unique), price (decimal), compare_price (nullable),
      stock_quantity (integer), is_active (boolean), timestamps

12. PRODUCT_VARIANT_ATTRIBUTE_VALUES table (pivot):
    - variant_id (FK), attribute_value_id (FK)

13. TAGS table:
    - id, name (JSON translatable), slug (unique), timestamps

14. PRODUCT_TAGS table (pivot):
    - product_id (FK), tag_id (FK)
```

## PROMPT 2.3 — Sales Tables

```
15. ORDERS table:
    - id, order_number (unique, generated: ORD-YYYYMMDD-XXXXX),
      user_id (nullable FK — allows guest checkout),
      guest_email (nullable), guest_name (nullable), guest_phone (nullable),
      status (enum: pending, processing, shipped, delivered, cancelled, refunded),
      payment_status (enum: unpaid, paid, refunded, partially_refunded),
      payment_method (enum: stripe, paypal, cod, bank_transfer),
      transaction_id (nullable),
      subtotal (decimal), discount_amount (decimal, default 0),
      shipping_amount (decimal, default 0), tax_amount (decimal, default 0),
      total (decimal),
      currency_code (varchar 3, default SAR),
      currency_rate (decimal — exchange rate at time of order),
      coupon_id (nullable FK), coupon_code (nullable), notes (text, nullable),
      ip_address (nullable),
      timestamps, softDeletes

16. ORDER_ITEMS table:
    - id, order_id (FK), product_id (FK), variant_id (nullable FK),
      product_name (stored snapshot), product_sku, product_image (nullable),
      quantity, unit_price, compare_price (nullable), total, timestamps

17. ORDER_ADDRESSES table:
    - id, order_id (FK), type (enum: billing, shipping),
      first_name, last_name, phone, address_line_1, address_line_2,
      city, state, country, postal_code

18. ORDER_STATUS_HISTORY table:
    - id, order_id (FK), status, comment (nullable),
      is_customer_notified (boolean, default false),
      created_by (nullable FK to admins), timestamps

19. ORDER_TRACKING table:
    - id, order_id (FK), tracking_number (nullable), carrier (nullable),
      tracking_url (nullable), timestamps

20. REVIEWS table:
    - id, product_id (FK), user_id (FK), order_item_id (nullable FK),
      rating (tinyint 1-5), title (nullable), body (text, nullable),
      status (enum: pending, approved, rejected),
      admin_reply (text, nullable), timestamps, softDeletes
```

## PROMPT 2.4 — Promotions & Content Tables

```
21. COUPONS table:
    - id, code (unique), name, description (nullable),
      type (enum: percentage, fixed, free_shipping),
      value (decimal), max_discount_amount (decimal, nullable),
      min_order_amount (decimal, default 0),
      usage_limit (integer, nullable — total uses allowed),
      usage_limit_per_user (integer, nullable),
      usage_count (integer, default 0),
      applies_to (enum: all, categories, products),
      exclude_sale_items (boolean, default false),
      is_active (boolean, default true),
      starts_at (timestamp, nullable), expires_at (timestamp, nullable),
      timestamps, softDeletes

22. COUPON_PRODUCTS table (pivot): coupon_id, product_id
23. COUPON_CATEGORIES table (pivot): coupon_id, category_id
24. COUPON_USAGES table: id, coupon_id, user_id, order_id, used_at

25. FLASH_SALES table:
    - id, name (JSON translatable), starts_at, ends_at,
      is_active (boolean), timestamps

26. FLASH_SALE_PRODUCTS table:
    - id, flash_sale_id (FK), product_id (FK), variant_id (nullable FK),
      sale_price (decimal), original_price (decimal),
      quantity_limit (integer, nullable), sold_count (integer, default 0)

27. BLOG_CATEGORIES table:
    - id, name (JSON translatable), slug (unique), timestamps

28. BLOG_POSTS table:
    - id, category_id (FK), admin_id (FK), slug (unique),
      title (JSON translatable), excerpt (JSON translatable),
      content (JSON translatable), featured_image (nullable),
      status (enum: draft, published), published_at (timestamp nullable),
      meta_title (nullable), meta_description (nullable),
      view_count (integer, default 0), timestamps, softDeletes

29. CMS_PAGES table:
    - id, title (JSON translatable), slug (unique), content (JSON translatable),
      excerpt (JSON translatable), template (enum: default, legal, landing),
      status (enum: active, inactive), featured_image (nullable),
      custom_css (text, nullable), meta_title, meta_description,
      sort_order, timestamps, softDeletes

30. BANNERS table (Homepage sliders/promotional banners):
    - id, title (JSON translatable, nullable), subtitle (JSON translatable, nullable),
      image, link_url (nullable), position (enum: hero, promo, category_top),
      is_active (boolean), sort_order, starts_at (nullable), ends_at (nullable),
      timestamps
```

## PROMPT 2.5 — Settings & Localization Tables

```
31. SETTINGS table (key-value store for all admin-controllable settings):
    - id, group (varchar — general, payment, shipping, seo, social, email),
      key (varchar, unique), value (text, nullable), timestamps

32. CURRENCIES table:
    - id, name, code (unique, 3-char), symbol, exchange_rate (decimal 10,6),
      is_default (boolean, default false), is_active (boolean, default true),
      decimal_places (integer, default 2), timestamps

    Seed with: SAR (default), AED, BHD, KWD, QAR

33. LANGUAGES table:
    - id, name, code (unique — en, ar), direction (enum: ltr, rtl),
      flag_icon (nullable), is_default (boolean), is_active (boolean), timestamps

    Seed with: English (en, ltr) [default], Arabic (ar, rtl)

34. SHIPPING_ZONES table:
    - id, name, countries (JSON array), timestamps

35. SHIPPING_RATES table:
    - id, zone_id (FK), name (JSON translatable), method (enum: flat, free, weight_based),
      price (decimal), min_order_for_free (decimal, nullable),
      min_weight, max_weight (nullable), is_active (boolean), timestamps

36. WISHLISTS table:
    - id, user_id (FK), product_id (FK), variant_id (nullable FK), timestamps
    - Unique constraint: user_id + product_id + variant_id

37. CART_ITEMS table (for persistent/session carts):
    - id, session_id (nullable), user_id (nullable FK),
      product_id (FK), variant_id (nullable FK),
      quantity, timestamps
```

---

# ═══════════════════════════════
# PHASE 3 — MODELS & RELATIONSHIPS
# ═══════════════════════════════

## PROMPT 3.1 — Create All Eloquent Models

```
Create Eloquent models for every table created in Phase 2.
For each model:

ADMIN model:
- Use HasFactory, Notifiable, HasRoles (spatie permission)
- Guard: 'admin'
- fillable: name, email, password, avatar, is_active
- Cast: is_active to boolean, last_login_at to datetime

USER model:
- Use HasFactory, Notifiable, HasRoles, HasApiTokens (Sanctum)
- fillable: name, email, password, phone, avatar, is_active, is_banned
- Hidden: password, remember_token
- Relationships: hasMany(Address), hasMany(Order), hasMany(Review), hasMany(Wishlist)

PRODUCT model:
- Use HasFactory, SoftDeletes, HasTranslations, HasSlug (sluggable), HasMedia
- translatable fields: ['name', 'short_description', 'description']
- Relationships:
  - belongsTo(Category), belongsTo(Brand)
  - hasMany(ProductImage), hasMany(ProductVariant)
  - belongsToMany(Tag), belongsToMany(Attribute)
  - hasMany(Review), hasMany(OrderItem)
  - belongsToMany(FlashSale) via flash_sale_products

CATEGORY model:
- Use HasTranslations, HasSlug, SoftDeletes
- translatable: ['name', 'description']
- Self-referencing: parent() belongsTo(Category), children() hasMany(Category)
- Relationships: hasMany(Product)

ORDER model:
- Use SoftDeletes
- Relationships:
  - belongsTo(User, nullable), belongsTo(Coupon, nullable)
  - hasMany(OrderItem), hasMany(OrderAddress)
  - hasMany(OrderStatusHistory), hasOne(OrderTracking)
- Accessors: statusLabel(), paymentStatusLabel(), formattedTotal()
- Scopes: pending(), processing(), shipped(), delivered()

COUPON model:
- Relationships: hasMany(CouponUsage), belongsToMany(Product), belongsToMany(Category)
- Methods: isValid(), isValidForUser(User), calculateDiscount(amount)

SETTING model:
- Static helper method: get(key, default), set(key, value), getGroup(group)
- Cache settings using Redis — clear cache on update

CURRENCY model:
- Static helpers: getDefault(), getActive(), convert(amount, from, to)

All models should have proper $fillable, $casts, $hidden arrays.
Add global scopes where appropriate (e.g., active scope on Product, Category).
```

---

# ═══════════════════════════════
# PHASE 4 — ADMIN AUTHENTICATION & ROLES
# ═══════════════════════════════

## PROMPT 4.1 — Filament Admin Auth System

```
Configure Filament V5 to use the Admin model (not User model) for authentication:

1. Create a separate 'admin' guard in config/auth.php:
   - Driver: session
   - Provider: admins (using Admin model)

2. Configure Filament AdminPanelProvider:
   - auth()->guard('admin')
   - Login page: custom branded login page with Eseven Store logo
   - Login fields: email + password
   - "Remember me" checkbox enabled
   - Password reset flow enabled (emails admin reset link)
   - After login redirect to: /admin/dashboard
   - After logout redirect to: /admin/login

3. Admin Login Page Design (via Filament custom login):
   - Background: dark navy (#1a2234) matching Porto Shop reference
   - Logo centered at top
   - White card with shadow for the login form
   - "Eseven Store — Admin Panel" heading
   - Input fields with rounded corners
   - Primary button color: #3b82f6 (blue)
   - "Forgot password?" link below password field
   - Footer text: "© 2026 Eseven Store. All rights reserved."

4. Admin Password Reset:
   - Use Filament's built-in password reset
   - Email template: branded with Eseven Store logo
   - Reset link expires in 60 minutes

5. Admin Profile Page:
   - Edit name, email, avatar (image upload)
   - Change password (requires current password confirmation)
   - Two-factor authentication toggle (optional, Filament 2FA plugin)
```

## PROMPT 4.2 — Roles & Permissions Setup

```
Set up Spatie Laravel Permission integrated with Filament V5.

Define these admin ROLES:
- Super Admin: all permissions (bypass all checks)
- Manager: everything except Settings and deleting other admins
- Editor: Catalog (Products, Categories, Brands, Blog) only
- Support: Orders, Customers, Reviews (read + limited write)

Define these PERMISSIONS (create the seeder for all):

Catalog permissions:
  products.view, products.create, products.edit, products.delete
  categories.view, categories.create, categories.edit, categories.delete
  brands.view, brands.create, brands.edit, brands.delete

Sales permissions:
  orders.view, orders.edit, orders.delete, orders.export
  customers.view, customers.edit, customers.ban, customers.email
  reviews.view, reviews.approve, reviews.delete

Promotions permissions:
  coupons.view, coupons.create, coupons.edit, coupons.delete
  flash_sales.view, flash_sales.create, flash_sales.edit, flash_sales.delete

Content permissions:
  blog.view, blog.create, blog.edit, blog.delete
  pages.view, pages.create, pages.edit, pages.delete
  banners.view, banners.create, banners.edit, banners.delete

Settings permissions:
  settings.view, settings.edit

Admin management permissions:
  admins.view, admins.create, admins.edit, admins.delete

In Filament, apply canAccess() on each Resource and Page using the admin's permissions.
Super Admin should bypass all checks using a Filament policy or Gate::before().

Create a DatabaseSeeder that:
1. Creates all permissions
2. Creates all roles and assigns correct permissions
3. Creates first Super Admin account:
   Name: Super Admin, Email: admin@eseven-store.com, Password: (from .env ADMIN_DEFAULT_PASSWORD)
```

---

# ═══════════════════════════════
# PHASE 5 — ADMIN DASHBOARD
# ═══════════════════════════════

## PROMPT 5.1 — Dashboard Stats Widgets

```
Create the Admin Dashboard page in Filament with these widgets
(matching the Porto Shop dashboard reference exactly):

TOP ROW — 4 stat cards (using Filament StatsOverviewWidget):
1. "Today's Revenue" — sum of today's paid orders (formatted in default currency)
   Icon: dollar/money icon, Color: blue
2. "New Orders Today" — count of orders placed today
   Icon: shopping bag icon, Color: green
3. "New Customers Today" — count of users registered today
   Icon: person-add icon, Color: teal
4. "Low Stock Items" — count of products where stock_quantity <= low_stock_threshold
   Icon: warning/alert icon, Color: red

MIDDLE ROW — 2 widgets side by side:
1. "Revenue — Last 30 Days" (line chart):
   - X-axis: dates for past 30 days
   - Y-axis: revenue amount
   - Data: sum of order totals grouped by date (paid orders only)
   - Use Filament Charts Widget (filament-chart.js integration)
   - Line color: blue, filled area below line

2. "Orders by Status" (donut/pie chart):
   - Segments: Pending (yellow), Processing (blue), Shipped (teal), Delivered (green), Cancelled (red)
   - Count of orders in each status
   - Legend shown below chart

BOTTOM ROW — 2 widgets side by side:
1. "Recent Orders" table:
   - Columns: Order#, Customer, Status (badge), Payment (badge), Total, Date
   - Show last 10 orders, most recent first
   - Order# is clickable → goes to order detail page
   - "View All" button → links to Orders list
   - Status badges: Pending=yellow, Processing=blue, Shipped=teal, Delivered=green, Cancelled=red
   - Payment badges: Paid=green, Unpaid=red

2. "Low Stock Alert" table:
   - Columns: Product, SKU, Stock (shown in red if ≤ threshold)
   - Show top 10 lowest stock products
   - If no low stock: show "All products stocked." message

Dashboard page should load data efficiently using:
- Cached queries (cache for 5 minutes, tagged 'dashboard')
- Eager loading to avoid N+1
```

---

# ═══════════════════════════════
# PHASE 6 — CATALOG MODULE
# ═══════════════════════════════

## PROMPT 6.1 — Categories Resource

```
Create a Filament Resource for Categories with full CRUD.

LIST PAGE (CategoryResource/Pages/ListCategories):
- Table columns: Image (thumbnail), Name (EN/AR toggle), Parent Category, Products Count, Sort Order, Status (toggle badge), Actions
- Filters: Parent Category, Status (active/inactive)
- Bulk actions: Activate, Deactivate, Delete
- Reorderable rows (drag-and-drop sort order)
- Search: by name (both EN and AR)

CREATE/EDIT FORM:
Organize into a two-column layout:

Left column (main):
- Parent Category: select dropdown (shows "None" for root categories), nested display
- Category Image: file upload with preview (recommended 600x400px)
- Sort Order: number input

Right column (sidebar card):
- Status toggle (Active/Inactive)
- "Save Category" button

Tabbed sections below:
  Tab 1 — "English":
    - Name (required): text input
    - Description: rich text editor (TipTap or Quill)
    - SEO: Meta Title, Meta Description, Meta Keywords, Slug (auto-generated, editable)

  Tab 2 — "Arabic":
    - Name (required): text input (RTL direction)
    - Description: rich text editor (RTL)
    - Slug for Arabic (optional)

Validation:
- Name required in at least English
- Slug unique
- Cannot set a category as its own parent
- Cannot set a child as its own grandparent (prevent circular references)
```

## PROMPT 6.2 — Brands Resource

```
Create a Filament Resource for Brands.

LIST PAGE:
- Columns: Logo (thumbnail), Name, Slug, Products Count, Status toggle, Sort Order, Actions (Edit, Delete)
- Search by name
- Filter by status

FORM:
- Name: text (required)
- Slug: auto-generated, editable
- Logo: image upload with preview
- Description: textarea
- Sort Order: number
- Is Active: toggle
- SEO: Meta Title, Meta Description

Simple single-page form, no tabs needed.
```

## PROMPT 6.3 — Products Resource (Core)

```
Create the Products Filament Resource — the most complex resource.
Match the Porto Shop "Add New Product" reference exactly.

LIST PAGE:
- Top stats: Total Products count
- Filters bar: Search (name or SKU), Category dropdown, Brand dropdown, Status (All/Active/Inactive), Stock (All/In Stock/Low Stock/Out of Stock)
- "Export CSV" button: exports filtered product list
- "+ Add Product" primary button
- Table columns:
  Image thumbnail | Name + badges (Featured = gold, New = blue) | SKU | Category | Price (+ compare price strikethrough if set) | Stock (quantity, red if low) | Status (Active badge = green, Inactive = grey) | Actions (Edit pencil, Delete trash)
- Bulk actions: Activate, Deactivate, Feature, Unfeature, Delete
- Reorderable via sort_order

FORM — Layout: wide main area + right sidebar card

RIGHT SIDEBAR CARD ("Publish"):
- [✓] Active (visible on store) — toggle
- [ ] Featured Product — checkbox
- [ ] Mark as New — checkbox
- [Create Product] / [Update Product] primary blue button

RIGHT SIDEBAR CARD ("Tags"):
- Multi-select of existing tags + ability to create new tag inline
- Show: Bestseller, Eco-Friendly, Handmade, Limited Edition, New Arrival, Organic, etc.

RIGHT SIDEBAR CARD ("Homepage Placement"):
- [ ] Featured Products section
- [ ] Product Widget Columns

MAIN CONTENT SECTIONS (stacked cards):

--- Section: General Information ---
Row 1: Product Name (EN, required) | Product Name (AR)
Row 2: Slug (auto-generated, editable) | SKU (unique, auto-generate button)
Row 3: Category (required, searchable select with hierarchy) | Brand (optional select)
Row 4: Product Type (Simple / Variable)

--- Section: Description ---
Tab: English
  - Short Description: textarea
  - Full Description: Rich text editor (Tiptap with toolbar: bold, italic, underline, list, table, image, link)
Tab: Arabic
  - Short Description: textarea (RTL)
  - Full Description: Rich text editor (RTL)

--- Section: Pricing ---
Row: Price* (required) | Compare Price (optional, shown as strikethrough on frontend) | Cost Price (internal only)
Note below: "Compare price must be higher than price for sale badge to appear"

--- Section: Inventory ---
- [ ] Track stock quantity (toggle)
- If track stock ON show:
  Row: Stock Quantity | Low Stock Threshold (default 5) | Weight (kg)
  Row: [ ] Allow backorders

--- Section: Images ---
- Drag-and-drop multiple image upload
- First image = primary
- Drag to reorder
- Each image: preview thumbnail + delete button + alt text field
- Max 2MB each, accepted: JPG, PNG, WebP, GIF
- Recommended dimensions note

--- Section: SEO ---
- Meta Title (0/60 chars counter)
- Meta Description (0/160 chars counter)
- Meta Keywords (comma-separated)
- Live Google Search Preview: shows Title, URL, Description as it would appear in Google

Show "Back to List" button top right.
```

## PROMPT 6.4 — Product Variants (Variable Products)

```
When Product Type = "Variable", show an additional section:

--- Section: Attributes & Variants ---

Step 1 — Define Attributes:
- "Add Attribute" button → opens row:
  - Select Attribute (e.g., Size, Color) or create new
  - Values: chip/tag input to add multiple values (e.g., 38, 39, 40, 41 for size)
  - Example attributes for Eseven Store: Size (EU), Color, Width

Step 2 — Variants Table (auto-generated from attribute combinations):
Show a table with columns:
  Variant (attribute combination label) | SKU | Price | Compare Price | Stock | Active | Actions

Each variant row is inline-editable:
- SKU (auto-generated e.g. PROD-001-38-BLK, editable)
- Price (inherits from parent, overridable)
- Compare Price (optional)
- Stock Quantity
- Is Active toggle
- Delete button

"Generate All Variants" button: creates all combinations.
"Add Single Variant" button: manually add one.

Validation: Each variant SKU must be unique globally.
```

## PROMPT 6.5 — Tags Resource

```
Create a simple Tags resource:
- List: Tag Name (EN), Tag Name (AR), Slug, Products Count, Actions
- Form: Name EN (required), Name AR, Slug (auto-generated)
- Allow inline creation from Product form
```

---

# ═══════════════════════════════
# PHASE 7 — SALES MODULE
# ═══════════════════════════════

## PROMPT 7.1 — Orders Resource

```
Create Orders Filament Resource (read-heavy, update statuses).

LIST PAGE:
- Columns: Order# (clickable link), Customer Name/Guest, Status badge, Payment badge, Total (formatted currency), Payment Method icon, Date
- Filters: Status (multi-select), Payment Status, Payment Method, Date Range (from/to), Currency
- Search: by order number, customer name, customer email
- "Export CSV" button (filtered results)
- Bulk actions: Mark as Processing, Mark as Shipped, Mark as Delivered, Mark as Cancelled
- No "Create Order" from admin (orders come from frontend only)

ORDER DETAIL PAGE (View/Edit):
Match Porto Shop order detail reference exactly.

MAIN LEFT COLUMN:
  Card: "Order Items" table
  - Columns: Product (image + name + variant), SKU, Price, Qty, Total
  - Bottom: Subtotal, Discount (if coupon applied), Shipping, Tax, Grand Total
  - All amounts shown in order's currency

  Card: "Billing Address"
  - Customer full name, address lines, city, country, phone

  Card: "Shipping Address"
  - Same fields as billing

  Card: "Status History" timeline
  - Each entry: Status badge, Comment text, Date/time, "Notified" green badge if customer was notified

RIGHT SIDEBAR:
  Card: "Update Status"
  - Order Status dropdown (pending, processing, shipped, delivered, cancelled, refunded)
  - Comment textarea (optional, shown in history + email to customer)
  - [✓] Notify Customer checkbox (sends email notification)
  - [Update Status] blue button

  Card: "Tracking"
  - Tracking Number text input
  - Carrier dropdown (DHL, Aramex, SMSA, USPS, FedEx, UPS, Other)
  - Tracking URL text input
  - [Save Tracking] button

  Card: "Order Info"
  - Payment Method: (icon + label)
  - Transaction ID (if online payment)
  - IP Address
  - Placed: date and time

TOP RIGHT ACTIONS:
  - [🖨 Invoice] button: generates PDF invoice using DomPDF (branded with Eseven logo)
  - [← Back] button

Invoice PDF should include:
  - Eseven Store logo + address
  - Order number, date, customer info
  - Itemized products table
  - Totals breakdown
  - Payment method
  - Powered by footer
```

## PROMPT 7.2 — Customers Resource

```
Create Customers Filament Resource.

LIST PAGE:
- Columns: Avatar, Name, Email, Phone, Orders Count, Total Spent (formatted), Status (Active/Banned badge), Joined Date, Actions
- Filters: Status (active/banned), Date Joined range
- Search: by name, email, phone
- Bulk actions: Ban, Unban, Delete

CUSTOMER DETAIL PAGE:
Match Porto Shop customer detail reference.

LEFT CARD — "Customer Info":
- Large avatar (initials if no photo)
- Full name, email, phone
- Stats row: Total Orders count | Total Spent amount | Member Since date
- [Ban Customer] amber/warning button (if active) OR [Unban Customer] (if banned)
- On ban: modal asks for ban reason (stored in users.ban_reason)

LEFT CARD — "Send Email":
- Subject: text input
- Message: textarea
- [✈ Send] button → uses Laravel Mail to send email to customer

RIGHT CARD — "Order History" table:
- Columns: Order#, Status badge, Payment badge, Total, Date
- Empty state: "No orders."
- Clickable order numbers → go to Order detail page

CUSTOMER ADDRESSES section (below):
- List all saved addresses with labels (Home/Work)
- Admin can view but not edit customer addresses
```

## PROMPT 7.3 — Reviews Resource

```
Create Reviews Filament Resource.

LIST PAGE (match Porto Shop reviews reference):
- Columns: Product (name), Customer (name), Rating (star display 1-5), Title, Status badge (Approved=green, Pending=yellow, Rejected=red), Date, Actions
- Filters: Status (pending/approved/rejected), Rating (1-5), Date range
- Search: by product name, customer name
- Quick actions on each row:
  - ✓ Approve (green check) — sets status to approved
  - ✗ Reject (red x) — sets status to rejected
  - 🗑 Delete
- Bulk actions: Approve, Reject, Delete
- Pagination: 20 per page

REVIEW DETAIL/EDIT:
- View full review body text
- Admin Reply field: textarea for admin response (shown on frontend product page)
- Status select
- [Save] button
```

---

# ═══════════════════════════════
# PHASE 8 — PROMOTIONS MODULE
# ═══════════════════════════════

## PROMPT 8.1 — Coupons Resource

```
Create Coupons Filament Resource.
Match Porto Shop Coupon Management reference exactly.

LIST PAGE:
Top stats row (4 cards):
1. Total Coupons (count)
2. Active (count of is_active=true AND not expired)
3. Expired (count past expires_at)
4. Total Uses (sum of usage_count)

Filters: Search (code or name), Status (all/active/inactive/expired), Type (all/percentage/fixed/free_shipping)
Bulk Actions: Activate, Deactivate, Delete
"+ Create Coupon" primary blue button top right

Table columns:
- Code (styled in red uppercase monospace font, like "WELCOME20")
- Name
- Type badge (e.g., "20.00% OFF" in teal, "$10.00 OFF" in blue, "Free Shipping" in yellow)
- Value (formatted by type)
- Usage (used/limit e.g., "45/1000")
- Status (Active=green, Inactive=grey, Expired=red)
- Expires date
- Actions: View (eye), Edit (pencil), Duplicate (copy), Pause/Resume (pause icon), Delete (trash)

COUPON CREATE/EDIT FORM:
Match Porto Shop create coupon reference.

Section: "Coupon Details":
Row 1: Coupon Code* + [Generate] auto-generate button | Name (display name)
Row 2: Description (internal only, textarea)
Row 3: Type* (Percentage Discount / Fixed Amount / Free Shipping) | Value* | Max Discount Amount (only for percentage type, "no limit" placeholder)
Row 4: Min Order Amount ("no minimum") | Usage Limit Total ("unlimited") | Limit Per User ("unlimited")

Section: "Schedule & Restrictions":
Row: Start Date (datetime picker) | Expiry Date (datetime picker)
Applies To: (All Products / Specific Categories / Specific Products)
  - If Specific Categories: multi-select of categories
  - If Specific Products: searchable multi-select of products
[ ] Exclude Sale Items

Right sidebar:
- [✓] Active toggle
- [💾 Create Coupon] / [Update Coupon] blue button
- [Cancel] secondary button

Validation:
- Code must be uppercase (auto-convert)
- Expiry must be after start date
- Value must be > 0
- If percentage, value must be ≤ 100
```

## PROMPT 8.2 — Flash Sales Resource

```
Create Flash Sales Filament Resource.

LIST PAGE:
- Columns: Name (EN), Start Time, End Time, Products Count, Status (Upcoming/Active/Ended badge), Actions
- Filter by status
- Search by name

FORM:
Section: "Flash Sale Details":
- Name EN (required), Name AR
- Start DateTime* (datetime picker)
- End DateTime* (datetime picker)
- Is Active toggle

Section: "Flash Sale Products":
- Dynamic table to add products:
  - Search & select Product (with variant option if variable)
  - Sale Price* (required)
  - Original Price (auto-filled from product, editable)
  - Discount % (auto-calculated, displayed)
  - Quantity Limit (optional, leave empty for unlimited)
  - Sold Count (read-only, shows 0 on create)
  - Remove row button
- "+ Add Product" button
- Products must have sale_price < original_price (validated)

Frontend behavior (for API):
- Active flash sales should be visible on product cards (countdown timer)
- Flash sale price overrides regular price during active period
```

---

# ═══════════════════════════════
# PHASE 9 — CONTENT MODULE
# ═══════════════════════════════

## PROMPT 9.1 — Blog Resource

```
Create Blog Filament Resource (Posts + Categories).

BLOG CATEGORIES (sub-resource or separate resource):
- List: Name EN, Name AR, Slug, Posts Count, Actions
- Form: Name EN*, Name AR, Slug (auto), Description

BLOG POSTS LIST:
- Columns: Featured Image (thumbnail), Title (EN), Category, Author (admin name), Status badge (Draft=grey, Published=green), Published Date, Views, Actions
- Filters: Status, Category, Author, Date range
- Search by title
- Bulk: Publish, Unpublish, Delete

BLOG POST FORM:
Match Porto Shop blog create reference.

Tabs: Content | Media | SEO | Settings

Tab "Content":
- Title EN*, Title AR
- Slug (auto, editable)
- Excerpt EN, Excerpt AR
- Content EN: rich text editor (full toolbar — headers, bold, italic, lists, images, code, links, blockquote, table)
- Content AR: rich text editor (RTL)
- Category select
- Author (auto-filled with current admin, overridable for Super Admin)

Tab "Media":
- Featured Image upload (with preview)
- Gallery images (optional)

Tab "SEO":
- Meta Title EN, Meta Title AR
- Meta Description EN, Meta Description AR
- Meta Keywords
- Google preview component

Tab "Settings":
- Status (Draft / Published)
- Published At (datetime, auto-set when first published)
- [ ] Allow Comments (future feature)

Actions: [Save Draft] [Publish] [Preview] (opens frontend URL in new tab)
```

## PROMPT 9.2 — CMS Pages Resource

```
Create CMS Pages Filament Resource.
Match Porto Shop "CMS Pages" reference.

LIST PAGE:
- Columns: Page Icon/thumbnail, Title, Slug, Template badge, Media count, Sections count, Status badge, Actions (View, Edit, Delete)
- Default pages seeded: About Us, Privacy Policy, Terms & Conditions, Exchange and Return Policy, FAQ, Cookie Consent
- "+ Add Page" button

FORM (tabbed — matching Porto Shop reference):
Tabs: Content | Media | Sections | Custom Code | SEO | Settings

Tab "Content":
- Title EN*, Title AR
- Slug (auto, editable)
- Excerpt EN, Excerpt AR
- Content EN: rich text editor
- Content AR: rich text editor (RTL)

Tab "Media":
- Featured Image upload
- Additional media gallery

Tab "Sections" (optional builder):
- For landing-page-style pages
- Drag-drop content blocks (Text, Image+Text, CTA Button, etc.)

Tab "Custom Code":
- Custom CSS textarea
- Custom JS textarea (advanced, superadmin only)

Tab "SEO":
- Meta Title, Meta Description, Meta Keywords for each language
- Open Graph Image upload

Tab "Settings":
- Template: Default | Legal | Landing
- Status: Active | Inactive
- Sort Order
- Show in footer nav (toggle)

[Cancel] | [💾 Create Page / Update Page] buttons
```

## PROMPT 9.3 — Banners & Sliders Resource

```
Create Banners Filament Resource (controls homepage hero slider and promotional banners).

LIST PAGE:
- Columns: Preview (thumbnail), Title, Position badge, Status toggle, Sort Order, Date Range (if set), Actions
- Filterable by position and status
- Drag-to-reorder

FORM:
- Title EN (nullable), Title AR (nullable)
- Subtitle EN (nullable), Subtitle AR (nullable)
- Button Text EN, Button Text AR (nullable)
- Link URL (nullable)
- Position: Hero Slider | Promotional Banner (below hero) | Category Top Banner
- Image upload* (desktop): recommended 1920x600px
- Image upload (mobile, optional): recommended 768x400px
- Is Active toggle
- Sort Order
- Start Date (optional), End Date (optional — for time-limited promotions)

Validation: Image required for new banners.
```

---

# ═══════════════════════════════
# PHASE 10 — SETTINGS MODULE
# ═══════════════════════════════

## PROMPT 10.1 — Settings Architecture

```
Create a centralized Settings system in Filament using custom Pages (not Resources).
All settings stored in the `settings` table (key-value) with group prefix.
Cache all settings in Redis tagged cache, clear on any update.

Create a helper class App\Services\SettingService with static methods:
- get(string $key, $default = null): retrieves setting value
- set(string $key, $value): updates or creates setting
- getGroup(string $group): returns all settings for a group as array
- cache key pattern: settings.{group}.{key}

The Settings navigation group should appear in the admin sidebar as:
⚙️ Settings (navigation group, collapsible)
  - General Settings
  - Languages
  - Currencies
  - Payment Gateways
  - Shipping
  - Email / Notifications
  - SEO & Social
  - Admin Users
```

## PROMPT 10.2 — General Settings Page

```
Create Filament custom page: Settings → General Settings

Fields (organized in sections):

Section "Store Information":
- Store Name EN*, Store Name AR*
- Store Tagline EN, Store Tagline AR
- Store Logo (image upload, preview shown)
- Store Favicon (image upload)
- Store Email (contact email displayed on site)
- Store Phone
- Store WhatsApp Number (used for WhatsApp chat button on frontend)
- Store Address EN, Store Address AR

Section "Regional Settings":
- Default Language (select from active languages)
- Default Currency (select from active currencies)
- Timezone (select from PHP timezones)
- Date Format (select: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD)

Section "Storefront":
- Products Per Page (number, default 12)
- Enable Guest Checkout (toggle)
- Enable Wishlist (toggle)
- Enable Product Reviews (toggle)
- Reviews Require Approval (toggle)
- [ ] Show Out of Stock products (toggle)
- Low Stock Threshold Default (number, default 5)

Section "Maintenance Mode":
- [⚠] Enable Maintenance Mode (toggle)
- Maintenance Message EN, Maintenance Message AR
Warning: enabling this will show maintenance page to all storefront visitors.

[💾 Save Settings] button — saves all and clears cache.
```

## PROMPT 10.3 — Languages Page

```
Create Filament custom page: Settings → Languages

Display: Table of all languages with columns:
- Flag icon, Language Name, Code, Direction (LTR/RTL badge), Default (star), Active toggle, Actions

Actions per row: Edit, Set as Default (makes this default, unsets others), Delete (cannot delete if default or only language)

"+ Add Language" opens modal:
- Name, Code (2-char), Direction (LTR/RTL), Flag icon upload, Is Active

Note: Language codes drive translation file loading (resources/lang/{code}/).
RTL languages automatically flip admin panel and frontend layout direction.
```

## PROMPT 10.4 — Currencies Page

```
Create Filament custom page: Settings → Currencies

Display: Table with columns:
- Currency Name, Code (3-char), Symbol, Exchange Rate vs SAR, Decimal Places, Default (star badge), Active toggle, Actions

Default seed data:
| Name           | Code | Symbol | Rate  | Default |
|----------------|------|--------|-------|---------|
| Saudi Riyal    | SAR  | ﷼      | 1.000 | ✓       |
| UAE Dirham     | AED  | د.إ    | 1.020 |         |
| Bahraini Dinar | BHD  | .د.ب   | 0.141 |         |
| Kuwaiti Dinar  | KWD  | د.ك    | 0.086 |         |
| Qatari Riyal   | QAR  | ر.ق    | 1.025 |         |

"+ Add Currency" modal: Name, Code, Symbol, Exchange Rate, Decimal Places

[↻ Update Exchange Rates] button: calls external API (e.g., exchangeratesapi.io or fixer.io) to auto-update rates. Store API key in settings.

Currency conversion logic:
- All prices stored in SAR (default)
- When displaying, multiply by exchange_rate of selected currency
- At order time: snapshot the rate into orders.currency_rate

Set as Default / Activate Toggle / Edit / Delete actions.
Cannot delete default currency.
```

## PROMPT 10.5 — Payment Gateways Page

```
Create Filament custom page: Settings → Payment Gateways

Show 4 payment method cards in a grid, each with toggle + configure:

CARD 1 — Stripe:
- [ ] Enable Stripe (toggle)
- Publishable Key (text, masked)
- Secret Key (text, masked, stored encrypted)
- Webhook Secret (text, masked)
- Mode: Test | Live (radio)
- [Test Connection] button: makes test API call and shows success/error

CARD 2 — PayPal:
- [ ] Enable PayPal (toggle)
- Client ID (text)
- Client Secret (text, masked, encrypted)
- Mode: Sandbox | Live (radio)
- [Test Connection] button

CARD 3 — Cash on Delivery (COD):
- [ ] Enable COD (toggle)
- Label EN (default: "Cash on Delivery")
- Label AR (default: "الدفع عند الاستلام")
- Description EN (shown at checkout)
- Description AR
- Extra Fee (decimal, 0 = free, e.g., 10 SAR COD fee)
- Available only for these regions (optional: JSON country codes)

CARD 4 — Direct Bank Transfer:
- [ ] Enable Bank Transfer (toggle)
- Label EN, Label AR
- Bank Name, Account Name, IBAN, Swift/BIC
- Instructions EN (shown after order placed), Instructions AR
- Upload Bank QR Code (optional image for QR payment)

[💾 Save All Payment Settings] button.
Note: Secret keys stored encrypted using Laravel's encrypt() / decrypt().
```

## PROMPT 10.6 — Shipping Settings Page

```
Create Filament custom page: Settings → Shipping

Section "Shipping Zones":
- Table: Zone Name, Countries (count badge), Rates count, Actions (Edit, Delete)
- "+ Add Zone" opens form:
  - Zone Name, Countries (multi-select from country list)

Section "Shipping Rates" (per zone):
When a zone is selected/expanded show its rates:
- Table: Rate Name EN/AR, Method (badge), Price, Min Order for Free, Status
- "+ Add Rate" per zone:
  - Name EN*, Name AR*
  - Method: Flat Rate | Free Shipping | Weight Based
  - If Flat Rate: Price (decimal)
  - If Free Shipping: Min Order Amount (optional, e.g., free if order > 200 SAR)
  - If Weight Based: Price per kg, Min Weight, Max Weight, Base Price
  - Is Active toggle

Section "General Shipping Settings":
- Free Shipping Threshold (global, overrides zone rates if enabled, e.g., 0 = disabled)
- Default Weight Unit (kg / lb)
- [ ] Enable Shipping Calculator on product page
- [ ] Require phone number for delivery

[💾 Save] button.
```

## PROMPT 10.7 — Email & Notification Settings Page

```
Create Filament custom page: Settings → Email / Notifications

Section "Mail Configuration":
- Mailer: SMTP | Mailgun | SES | Sendmail (select)
- If SMTP: Host, Port, Username, Password, Encryption (TLS/SSL)
- If Mailgun: Domain, Secret Key
- From Name (default: store name), From Email (required)
- [Send Test Email] button: sends test to currently logged-in admin

Section "Customer Email Notifications" (toggle per event):
- [ ] Order Placed confirmation email
- [ ] Order Status Changed (when admin updates order status + notify customer checked)
- [ ] Order Shipped (with tracking info)
- [ ] Order Delivered
- [ ] Order Cancelled
- [ ] Order Refunded
- [ ] Password Reset
- [ ] Welcome email on registration
- [ ] Review Approved notification
- [ ] Flash Sale starts (if subscribed)

Section "Admin Email Notifications":
- Admin notification email (comma-separated for multiple)
- [ ] New Order placed → notify admin
- [ ] Low Stock alert → notify admin
- [ ] New Customer registered → notify admin
- [ ] New Review submitted → notify admin

Section "WhatsApp Notifications" (optional):
- [ ] Enable WhatsApp notifications (via Twilio/Unifonic)
- API credentials (masked)

[💾 Save] button.

Note: Email templates should be stored in resources/views/emails/ with variables.
Each template should support both EN and AR based on customer's language preference.
```

## PROMPT 10.8 — SEO & Social Settings Page

```
Create Filament custom page: Settings → SEO & Social

Section "Default SEO":
- Site Title EN, Site Title AR
- Default Meta Description EN (max 160 chars), AR
- Default Meta Keywords
- Robots.txt content (textarea)
- [ ] Enable Sitemap auto-generation
- Sitemap URL (read-only, displayed after generation)
- [Regenerate Sitemap] button

Section "Open Graph (Social Sharing)":
- OG Site Name
- Default OG Image upload (1200x630px recommended)
- Twitter Card type (summary / summary_large_image)
- Twitter Handle (@username)

Section "Analytics & Tracking":
- Google Analytics ID (GA4 Measurement ID, e.g., G-XXXXXXXXXX)
- Google Tag Manager ID (GTM-XXXXXX)
- Facebook Pixel ID
- Snapchat Pixel ID
- TikTok Pixel ID
Note: These codes injected into frontend <head> via API settings endpoint.

Section "Social Media Links":
- Instagram URL
- Snapchat URL
- TikTok URL
- Twitter/X URL
- Facebook URL
- YouTube URL
- LinkedIn URL (optional)

[💾 Save] button.
```

## PROMPT 10.9 — Admin Users Management Page

```
Create Filament Resource: Admin Users (inside Settings navigation group).

LIST PAGE:
- Columns: Avatar, Name, Email, Role badge, Last Login, Status (Active/Inactive), Actions
- Filter by role, status
- Search by name, email
- Cannot delete your own account

CREATE/EDIT FORM:
- Name*, Email*, Password (required on create, optional on edit), Confirm Password
- Avatar image upload (optional)
- Role assignment (single role select from defined roles)
- Is Active toggle
- Note: Super Admin cannot have their role changed by non-super-admins

VIEW PAGE:
- Shows admin's activity log (last 20 actions from spatie/activitylog):
  - Date, Action (Created/Updated/Deleted), Model Type, Model ID, Description
```

---

# ═══════════════════════════════
# PHASE 11 — LARAVEL API LAYER
# ═══════════════════════════════

## PROMPT 11.1 — API Architecture & Auth

```
Set up Laravel Sanctum for the Vue.js frontend API authentication.

API Configuration:
- All API routes under: /api/v1/ prefix
- Stateful domains: configured for local and production frontend domains
- CORS: allow frontend domain (Vue.js SPA)
- All responses: JSON, camelCase keys (using spatie/laravel-data or manual resources)
- Versioning: /api/v1/ prefix for all routes
- Rate limiting: 60 req/min for guests, 120 req/min for authenticated users

Authentication endpoints (guest):
POST /api/v1/auth/register — customer registration
POST /api/v1/auth/login — returns Sanctum token + user data
POST /api/v1/auth/logout — revokes token
POST /api/v1/auth/forgot-password — sends reset link
POST /api/v1/auth/reset-password — resets password
GET  /api/v1/auth/me — returns current authenticated user

Customer protected endpoints (require Sanctum token):
GET    /api/v1/profile — user profile
PUT    /api/v1/profile — update profile (name, phone, avatar)
PUT    /api/v1/profile/password — change password
GET    /api/v1/addresses — list saved addresses
POST   /api/v1/addresses — add new address
PUT    /api/v1/addresses/{id} — update address
DELETE /api/v1/addresses/{id} — delete address
GET    /api/v1/orders — customer's orders list (paginated)
GET    /api/v1/orders/{orderNumber} — single order detail
GET    /api/v1/wishlist — wishlist items
POST   /api/v1/wishlist — add to wishlist
DELETE /api/v1/wishlist/{productId} — remove from wishlist
```

## PROMPT 11.2 — Store API Endpoints

```
Create these public API endpoints for the Vue.js storefront:

SETTINGS & INITIALIZATION:
GET /api/v1/init — returns all storefront settings in one call:
  { currencies: [...], languages: [...], defaultCurrency, defaultLanguage,
    paymentMethods: [...], socialLinks, storeName, logo, favicon,
    analyticsIds: {...}, whatsappNumber }

NAVIGATION:
GET /api/v1/categories — tree of all active categories (nested, with product counts)
GET /api/v1/categories/{slug} — single category with subcategories

BANNERS:
GET /api/v1/banners?position=hero — active banners by position

PRODUCTS:
GET /api/v1/products — paginated product listing
  Query params: category, brand, search, tags, minPrice, maxPrice,
                rating, sortBy (price_asc/price_desc/newest/best_seller),
                page, perPage, currency, lang
  Response: products array with pagination meta

GET /api/v1/products/{slug} — single product detail
  Includes: images, variants, category, brand, attributes, related products,
            active flash sale price (if any), reviews summary (avg rating + count)

GET /api/v1/products/featured — featured products for homepage
GET /api/v1/products/new-arrivals — newest products
GET /api/v1/products/best-sellers — products ordered by order count
GET /api/v1/products/search?q={term} — full-text search

BRANDS:
GET /api/v1/brands — all active brands

REVIEWS:
GET /api/v1/products/{slug}/reviews — paginated reviews for a product
  Query params: rating, page
POST /api/v1/products/{slug}/reviews — submit review (requires auth)

CART (server-side persistent cart):
GET    /api/v1/cart — current cart (session or user)
POST   /api/v1/cart/items — add item to cart
PUT    /api/v1/cart/items/{id} — update quantity
DELETE /api/v1/cart/items/{id} — remove item
DELETE /api/v1/cart — clear cart
POST   /api/v1/cart/coupon — apply coupon code, returns discount amount
DELETE /api/v1/cart/coupon — remove coupon

CHECKOUT:
POST /api/v1/checkout/shipping-rates — get available rates for address
POST /api/v1/checkout/place-order — place order (guest or auth)
  Body: { items, billingAddress, shippingAddress, shippingRateId,
          paymentMethod, couponCode, currency, language }
  For Stripe: returns { requiresAction: true, clientSecret: ... }
  For PayPal: returns { redirectUrl: ... }
  For COD/Bank: returns { orderId, orderNumber, status: 'pending' }

POST /api/v1/checkout/stripe/confirm — confirm Stripe payment
POST /api/v1/checkout/paypal/capture — capture PayPal payment after redirect
GET  /api/v1/checkout/order-success/{orderNumber} — returns order summary for thank-you page

FLASH SALES:
GET /api/v1/flash-sales/active — currently active flash sale with products

BLOG:
GET /api/v1/blog/posts — paginated published posts
GET /api/v1/blog/posts/{slug} — single post (increments view count)
GET /api/v1/blog/categories — blog categories

CMS PAGES:
GET /api/v1/pages/{slug} — single CMS page content

All currency-priced fields should accept ?currency=SAR query param and convert amounts.
All translatable fields should accept ?lang=en|ar query param and return correct translation.
```

## PROMPT 11.3 — API Resources & Response Format

```
Create Laravel API Resources for consistent response formatting:

Standard response envelope:
{
  "success": true,
  "data": { ... } or [ ... ],
  "meta": { "total": 100, "page": 1, "perPage": 12, "lastPage": 9 },
  "message": null
}

Error responses:
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "field": ["error message"] }
}

Create API Resources:
- ProductResource: id, name(localized), slug, price(converted), comparePrice, discountPercent, primaryImage, rating, reviewCount, isNew, isFeatured, inStock, flashSalePrice(if active)
- ProductDetailResource: extends ProductResource + description, images, variants, attributes, relatedProducts
- CategoryResource: id, name(localized), slug, image, childrenCount, productCount
- OrderResource: orderNumber, status, paymentStatus, paymentMethod, total, currency, items[], addresses, tracking, statusHistory[], createdAt
- CartResource: items[], subtotal, discountAmount, couponCode, shippingAmount, total, itemCount
- ReviewResource: rating, title, body, customerName, adminReply, createdAt
- UserResource: id, name, email, phone, avatar, createdAt

All monetary values: return both raw (for calculation) and formatted (with currency symbol for display).
All localized fields: return the correct language based on ?lang= param or Accept-Language header.
```

---

# ═══════════════════════════════
# PHASE 12 — VUE.JS FRONTEND
# ═══════════════════════════════

## PROMPT 12.1 — Vue.js Project Setup

```
Initialize Vue.js 3 frontend project (separate from Laravel):

Setup:
- Create project using Vite + Vue 3 + TypeScript
- Install: Vue Router 4, Pinia (state management), Axios (API calls)
- Install: @vueuse/core (composables), vue-i18n (localization), 
           headlessui/vue (accessible UI components),
           vue3-toastify (notifications), swiper (carousels),
           vue-countdown (flash sale timer), @vee-validate/zod (form validation)

Directory structure:
src/
  api/          (axios instance + all API call functions)
  assets/       (global CSS, images, fonts)
  components/
    common/     (Header, Footer, Navbar, Breadcrumb, Pagination, Modal, etc.)
    product/    (ProductCard, ProductGrid, ProductDetail, ProductImages, VariantSelector)
    cart/       (CartDrawer, CartItem, CartSummary)
    checkout/   (CheckoutSteps, AddressForm, PaymentForm, OrderSummary)
    account/    (ProfileForm, AddressList, OrderHistory, WishlistGrid)
  composables/  (useCart, useAuth, useCurrency, useLanguage, useWishlist)
  layouts/      (DefaultLayout, AccountLayout, CheckoutLayout)
  pages/        (route-level components)
  router/       (index.ts with all routes)
  stores/       (Pinia: authStore, cartStore, settingsStore, wishlistStore)
  i18n/         (en.json, ar.json translation files)
  types/        (TypeScript interfaces)

Pinia Stores to create:
1. settingsStore: currencies, languages, currentCurrency, currentLanguage, storeSettings
2. authStore: user, token, isAuthenticated, login(), logout(), register()
3. cartStore: items, total, coupon, itemCount, addItem(), removeItem(), applyCoupon()
4. wishlistStore: items, addItem(), removeItem(), isInWishlist()

Global composables:
- useCurrency(): converts amounts, formats with symbol
- useLanguage(): switches lang, sets HTML dir attribute for RTL, reloads translations
- useAuth(): guards protected routes
```

## PROMPT 12.2 — Frontend Pages & Routes

```
Define all Vue Router routes for the storefront:

PUBLIC ROUTES:
/ → HomePage
/products → ShopPage (all products)
/category/{slug} → CategoryPage
/brand/{slug} → BrandPage
/products/{slug} → ProductDetailPage
/search → SearchResultsPage
/flash-sale → FlashSalePage
/blog → BlogListPage
/blog/{slug} → BlogPostPage
/{slug} → CmsPage (About Us, Privacy Policy, etc.)
/cart → CartPage
/checkout → CheckoutPage
/checkout/success/{orderNumber} → OrderSuccessPage

AUTHENTICATED ROUTES (redirect to /login if not auth):
/account → AccountDashboardPage (orders summary, profile quick-edit)
/account/profile → ProfilePage
/account/orders → OrdersListPage
/account/orders/{orderNumber} → OrderDetailPage
/account/wishlist → WishlistPage
/account/addresses → AddressesPage

AUTH ROUTES (redirect to /account if already auth):
/login → LoginPage
/register → RegisterPage
/forgot-password → ForgotPasswordPage
/reset-password → ResetPasswordPage

Router guards:
- authGuard: checks authStore.isAuthenticated → redirect to /login
- guestGuard: checks !isAuthenticated → redirect to /account

Scroll behavior: scrollToTop on every route change
Transition: fade page transition (150ms)
```

## PROMPT 12.3 — Key Frontend Features

```
Implement these critical features on the Vue.js frontend:

1. MULTI-LANGUAGE (AR/EN):
   - Language switcher in header (globe icon → dropdown: English / العربية)
   - On switch: update settingsStore.currentLanguage, set <html lang="ar" dir="rtl"> or <html lang="en" dir="ltr">, save to localStorage, reload all API calls with ?lang={code}
   - All UI text via vue-i18n translation keys in en.json and ar.json
   - Product names, descriptions from API already localized based on ?lang param
   - Arabic: all text right-aligned, flex-row-reverse for flex layouts

2. MULTI-CURRENCY:
   - Currency switcher in header (flag + code → dropdown: SAR ﷼, AED د.إ, BHD .د.ب, KWD د.ك, QAR ر.ق)
   - On switch: update settingsStore.currentCurrency, save to localStorage
   - All price displays: run through useCurrency().format(amount) composable
   - Cart totals and checkout use selected currency
   - Currency selector matches eseven-store.com modal reference (with radio buttons + OK)

3. PRODUCT LISTING & FILTERING:
   - Left sidebar filters: Category tree, Brand checkboxes, Rating stars, Price range slider
   - Top sort bar: Best Seller, Price Low-High, Price High-Low, Newest
   - Grid view: 3 columns desktop, 2 columns tablet, 1 column mobile
   - Product card: image (with hover second image), name, brand, price + compare-price strikethrough, rating stars, "Add to Cart" button, wishlist heart icon, "Unisex shoes" / "Men's shoes" badge (from category)
   - Flash sale price shown with countdown timer on card
   - "Load More" pagination button (matching eseven-store.com "تحميل المزيد" button)

4. PRODUCT DETAIL PAGE:
   - Image gallery: main image + thumbnail strip (left side), zoom on hover
   - Name, brand, SKU, availability, price, compare price
   - Variant selector: size buttons (shows stock status per size)
   - Quantity selector
   - [Add to Cart] + [Buy Now] buttons
   - Wishlist icon
   - Product tags (clickable → filter)
   - Description tabs: Product Details | Product Rating
   - "It is usually purchased with" section (related products carousel)
   - Reviews section: star breakdown + individual reviews list

5. CART & CHECKOUT:
   - Cart: slide-out drawer OR dedicated cart page
   - Checkout flow (single page, multi-step):
     Step 1: Email/Login (guest email or login prompt)
     Step 2: Shipping Address form (with saved addresses for logged-in users)
     Step 3: Shipping Method selection (rates from API)
     Step 4: Payment Method (Stripe card, PayPal button, COD, Bank Transfer)
     Step 5: Order Review + Place Order
   - Coupon code input with [Apply] button → shows discount
   - Order Summary sidebar always visible

6. STRIPE INTEGRATION (frontend):
   - Use Stripe.js + @stripe/stripe-js
   - Load Stripe with publishable key from settings API
   - Render Stripe Elements card form
   - On place order: create PaymentIntent via API → confirm with Stripe.js → API captures

7. ANNOUNCEMENT BANNER:
   - Red scrolling ticker (marquee-style) across top — matches eseven-store.com
   - Text from Settings → General → Announcement Text EN/AR
   - Closeable (dismiss persists in localStorage for 24h)

8. WHATSAPP BUTTON:
   - Fixed bottom-right WhatsApp floating button
   - Opens wa.me/{number} in new tab
   - Number from settings

9. SEO:
   - useHead() or @vueuse/head for dynamic meta tags per page
   - Product pages: title = product name, meta description from product, OG image = product primary image
   - Category pages: canonical URLs
   - Structured data (JSON-LD): Product schema on product detail pages
```

---

# ═══════════════════════════════
# PHASE 13 — TESTING & DEPLOYMENT
# ═══════════════════════════════

## PROMPT 13.1 — Testing Strategy

```
Set up comprehensive testing for the project:

BACKEND TESTS (Laravel + Pest):

Feature Tests (HTTP tests):
- Auth: admin login, wrong password, locked account, password reset flow
- Products API: listing with filters, single product, search
- Cart API: add item, update qty, apply valid coupon, apply expired coupon
- Checkout: place COD order (guest), place Stripe order (mock), place PayPal order (mock)
- Order status update: changing status sends notification email
- Coupon validation: expired, usage limit exceeded, minimum order not met
- Review submission: can only review purchased products

Unit Tests:
- CurrencyService: conversion accuracy
- CouponModel: calculateDiscount() for each coupon type
- SettingService: get, set, cache invalidation
- OrderModel: total calculation with discount + shipping + tax

Admin Panel Tests:
- Filament resource CRUD: Products create/edit/delete
- Permission tests: editor cannot access orders
- Bulk actions work correctly

FRONTEND TESTS (Vitest + Vue Test Utils):
- ProductCard renders correctly with flash sale price
- Currency switcher updates all displayed prices
- Language switcher changes text direction to RTL
- Cart store: add, remove, apply coupon, calculate totals
- Auth store: login, logout, token persistence
- Checkout form validation

E2E Tests (Playwright):
- Full purchase flow: browse → product detail → add to cart → checkout (COD) → success page
- User registration and login
- Language and currency switching
- Coupon application at checkout
- Account: view order history
```

## PROMPT 13.2 — Deployment Setup

```
Configure deployment for production:

SERVER REQUIREMENTS:
- Ubuntu 22.04 LTS
- PHP 8.3+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, Redis, GD/Imagick
- MySQL 8.0+
- Redis 7+
- Nginx
- Node.js 20+ (for building Vue.js frontend)
- Composer 2+
- SSL certificate (Let's Encrypt)

LARAVEL PRODUCTION SETUP:
1. Set APP_ENV=production, APP_DEBUG=false
2. php artisan config:cache
3. php artisan route:cache
4. php artisan view:cache
5. php artisan storage:link
6. Set up Laravel Horizon (or supervisor) for queue workers
7. Schedule: php artisan schedule:work (or cron: * * * * * cd /path && php artisan schedule:run)
   Scheduled tasks:
   - Every hour: Update currency exchange rates
   - Every day: Check and deactivate expired coupons, deactivate ended flash sales
   - Every day: Send low stock alerts to admin
   - Every week: Generate sitemap

NGINX CONFIGURATION:
- Backend (admin + API): api.eseven-store.com
  - Laravel app server
  - /admin/* → Filament admin panel
  - /api/* → API routes
- Frontend: eseven-store.com
  - Serve Vue.js dist/ folder
  - All routes → index.html (SPA fallback)
  - Cache static assets (JS, CSS, images): 1 year

VUE.JS BUILD:
- npm run build → generates dist/
- Environment: VITE_API_BASE_URL=https://api.eseven-store.com/api/v1
               VITE_STRIPE_PUBLISHABLE_KEY=pk_live_...

MEDIA STORAGE:
- Configure S3-compatible storage (AWS S3 or Cloudflare R2) for production
- Images served via CDN with VITE_CDN_URL prefix

REDIS:
- Cache driver: Redis
- Session driver: Redis (for admin sessions)
- Queue driver: Redis

MONITORING:
- Set up Laravel Telescope in local/staging only (disable production)
- Configure error tracking: Sentry (Laravel SDK + Vue.js SDK)
- Set up uptime monitoring (e.g., Better Uptime)
```

---

# 🧭 EXECUTION ORDER (START HERE)

```
Week 1-2:   Phase 1 → Phase 2 → Phase 3 (Setup, DB, Models)
Week 3:     Phase 4 (Admin Auth + Roles)
Week 4:     Phase 5 + Phase 6 (Dashboard + Catalog)
Week 5:     Phase 7 (Orders + Customers + Reviews)
Week 6:     Phase 8 + Phase 9 (Promotions + Content)
Week 7:     Phase 10 (All Settings)
Week 8:     Phase 11 (API Layer)
Week 9-10:  Phase 12 (Vue.js Frontend)
Week 11:    Phase 13 (Testing + Deployment)
```

---

# 🔑 ESEVEN STORE SPECIFIC NOTES

These details are specific to replicating eseven-store.com:

```
TARGET MARKET: Saudi Arabia + GCC region
- Primary currency: SAR (Saudi Riyal) with symbol ﷼
- Additional: AED, BHD, KWD, QAR
- Default language: English (with Arabic support)
- Phone: +966566229730 (WhatsApp button)
- Email: eseven.store@gmail.com
- Commercial Register: 2031106284

PRODUCT CATEGORIES (from site):
Main navigation tabs to replicate:
1. Offers
2. Unisex shoes (with subcategories by brand: New Balance, ASICS, etc.)
3. Men's shoes
4. Women's shoes
5. Bags (Travel bags, luxury bags)
6. Men's Clothes
7. Women's Clothes
8. Accessories
9. Gift Cards (digital product type — add to Phase 6)

BRANDS TO SEED:
New Balance, ASICS (Asics), Adidas, Nike, Miu Miu,
Bottega Veneta, Louis Vuitton, Fendi, Gucci, Boeing SS

HOMEPAGE SECTIONS (Vue.js):
1. Announcement banner (scrolling red ticker)
2. Hero slider (family lifestyle images)
3. Category banner grid (2-column: ASICS Exclusive + New Balance)
4. Featured Products grid
5. Flash Sale section (if active)
6. New Arrivals
7. Customer Reviews carousel (the "أراء العملاء" section)
8. Footer: Important Links | Contact Us | Download App buttons

PRODUCT BADGE SYSTEM (from eseven site):
- "Unisex shoes" red badge on applicable products
- "سعر التخفيض" (Sale Price) red badge with strikethrough original price
- Flash sale indicator with countdown

ADMIN PANEL SIDEBAR GROUPS (match Porto Shop layout):
CATALOG: Products, Categories, Brands, Tags
SALES: Orders, Customers, Reviews
PROMOTIONS: Coupons, Flash Sales
CONTENT: Blog Posts, Blog Categories, CMS Pages, Banners
SETTINGS: General, Languages, Currencies, Payment Gateways, Shipping, Email, SEO & Social, Admin Users
```

---

*Blueprint Version 1.0 | Eseven Store Clone | Laravel 13 + Filament V5 + Vue.js 3*
*Generated based on: eseven-store.com analysis + Porto Shop admin panel reference + provided screenshots*
