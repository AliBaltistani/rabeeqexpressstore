# Database Seeders Documentation

## Overview
This document explains which seeders are safe for production and which should only be used for development.

---

## 🟢 Production-Safe Seeders (Run with `php artisan db:seed`)

These seeders create essential configuration data and are safe to run on production servers.

### 1. **RolePermissionSeeder**
- **Purpose:** Creates admin roles (Super Admin, Manager, Editor, Support) and their permissions
- **Data:** Static role definitions and permission matrix
- **Safe for production:** ✅ YES
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

### 2. **AdminUserSeeder**
- **Purpose:** Creates admin user accounts with assigned roles
- **Emails:** 
  - `admin@rabeq-express-store.com` (Super Admin)
  - `manager@rabeq-express-store.com` (Manager)
  - `editor@rabeq-express-store.com` (Editor)
  - `support@rabeq-express-store.com` (Support)
- **Passwords:** 
  - Read from `.env` environment variables
  - `ADMIN_DEFAULT_PASSWORD` for super admin
  - `MANAGER_PASSWORD`, `EDITOR_PASSWORD`, `SUPPORT_PASSWORD` for other roles
  - Default fallback: `password` (if not set in .env)
- **Safe for production:** ✅ YES (set strong passwords in .env!)
- **Run automatically:** ✅ YES (included in DatabaseSeeder)
- **IMPORTANT:** Configure unique passwords in `.env.production` before running on server!

### 3. **CurrencySeeder**
- **Purpose:** Creates multi-currency support
- **Currencies:** SAR (default), AED, BHD, KWD, QAR
- **Data:** Exchange rates, symbols, decimal places
- **Safe for production:** ✅ YES
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

### 4. **LanguageSeeder**
- **Purpose:** Creates language options for the store
- **Languages:** English (default), Arabic
- **Data:** Language codes, text direction (LTR/RTL), flag icons
- **Safe for production:** ✅ YES
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

### 5. **ShippingMethodSeeder**
- **Purpose:** Creates shipping method options
- **Methods:** Standard Delivery, Express, International, COD
- **Data:** Costs, free shipping thresholds, estimated delivery times
- **Safe for production:** ✅ YES (customize costs for your business)
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

### 6. **ShippingCarrierSeeder**
- **Purpose:** Creates shipping carrier integrations
- **Carriers:** SMSA Express, Aramex, DHL
- **Data:** Tracking URL templates, integration codes
- **Safe for production:** ✅ YES
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

### 7. **CmsPagesSeeder**
- **Purpose:** Creates legal and informational pages
- **Pages:**
  - About Us
  - Privacy Policy
  - Exchange and Return Policy
  - Terms and Conditions
  - Affiliate Marketing Program
- **Safe for production:** ✅ YES
- **Run automatically:** ✅ YES (included in DatabaseSeeder)

---

## 🟡 Optional Seeders (Run Manually as Needed)

These seeders create configuration data that may or may not be needed depending on your setup.

### 1. **CountrySeeder**
- **Purpose:** Creates list of supported countries
- **Countries:** UAE, Saudi Arabia, Kuwait, Qatar, Bahrain, Oman
- **Safe for production:** ✅ YES (if you need these countries)
- **Run automatically:** ❌ NO (not included in DatabaseSeeder)
- **Usage:**
  ```bash
  php artisan db:seed --class=Database\\Seeders\\CountrySeeder
  ```

### 2. **HomeSectionSeeder**
- **Purpose:** Creates homepage section templates
- **Sections:** Hero Slider, Promo Banners, Featured Products, New Arrivals, Collections, Sale Items
- **Safe for production:** ✅ YES
- **Run automatically:** ❌ NO (not included in DatabaseSeeder)
- **Usage:**
  ```bash
  php artisan db:seed --class=Database\\Seeders\\HomeSectionSeeder
  ```
- **Note:** You still need to add actual content/items to each section via admin panel

### 3. **LoyaltyRewardSeeder**
- **Purpose:** Creates loyalty reward tiers
- **Rewards:** Discount coupons, free shipping, gift cards
- **Safe for production:** ✅ REVIEW FIRST
- **Run automatically:** ❌ NO (not included in DatabaseSeeder)
- **Usage:**
  ```bash
  php artisan db:seed --class=Database\\Seeders\\LoyaltyRewardSeeder
  ```
- **Important:** Customize point costs and reward values for your business before running!

---

## 🔴 Development-Only Seeders (NEVER Run on Production!)

### 1. **DummyDataSeeder**
- **Purpose:** Creates fake/dummy data for development and testing
- **Data Created:**
  - 5 fake customer accounts
  - Dummy categories, brands, tags
  - Hundreds of fake products with images
  - Test images downloaded from external source
- **Safe for production:** ❌ NO - NEVER RUN ON PRODUCTION!
- **Run automatically:** ❌ NO (explicitly disabled in DatabaseSeeder)
- **Development usage:**
  ```bash
  # Run ONLY in local development environment
  php artisan db:seed --class=Database\\Seeders\\DummyDataSeeder
  ```
- **Risk:** Running this on production will fill your database with thousands of fake products

---

## Server Setup Instructions

### Step 1: Configure Environment Variables
Edit `.env.production` (on your server) with strong, unique passwords:

```env
APP_NAME="Rabeq Express Store"
APP_URL=https://your-domain.com

# Admin passwords - SET THESE TO STRONG PASSWORDS!
ADMIN_DEFAULT_PASSWORD=your-super-secure-password-here
MANAGER_PASSWORD=your-manager-secure-password
EDITOR_PASSWORD=your-editor-secure-password
SUPPORT_PASSWORD=your-support-secure-password

# Database
DB_HOST=your-server-host
DB_DATABASE=your-database
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

### Step 2: Run Database Setup
```bash
# Create tables
php artisan migrate

# Seed essential configuration data only
php artisan db:seed

# Optional: Add country data
php artisan db:seed --class=Database\\Seeders\\CountrySeeder

# Optional: Add home section templates
php artisan db:seed --class=Database\\Seeders\\HomeSectionSeeder

# Optional: Add loyalty rewards (AFTER reviewing/customizing values)
php artisan db:seed --class=Database\\Seeders\\LoyaltyRewardSeeder
```

### Step 3: Verify Admin Access
1. Visit `/admin/login`
2. Login with: `admin@rabeq-express-store.com` and your `ADMIN_DEFAULT_PASSWORD`
3. Change the password immediately after first login

---

## Summary: What Gets Seeded with `php artisan db:seed`

✅ **Automatically seeded (production-ready):**
- Roles and permissions
- Admin users (with .env passwords)
- Currencies (SAR, AED, BHD, KWD, QAR)
- Languages (EN, AR)
- Shipping methods
- Shipping carriers
- CMS pages (legal pages)

❌ **NOT seeded (must run manually if needed):**
- Countries
- Home sections
- Loyalty rewards
- Dummy data (development only)

---

## Local Development Setup

To include dummy data during local development:

```bash
# Fresh migration with essential data only
php artisan migrate:fresh --seed

# Then manually add dummy data
php artisan db:seed --class=Database\\Seeders\\DummyDataSeeder

# Optional: Add other optional seeders
php artisan db:seed --class=Database\\Seeders\\HomeSectionSeeder
php artisan db:seed --class=Database\\Seeders\\LoyaltyRewardSeeder
php artisan db:seed --class=Database\\Seeders\\CountrySeeder
```

---

## Notes

- All seeders use `firstOrCreate()` to prevent duplicate data when re-run
- AdminUserSeeder reads passwords from environment variables for security
- DummyDataSeeder is **completely disabled** in the main DatabaseSeeder to prevent accidental production data pollution
- For real server: Only run `php artisan db:seed` - it will only seed production-safe data
- All email addresses have been updated to `rabeq-express-store.com` domain
