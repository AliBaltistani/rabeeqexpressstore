# Eseven Store Admin Panel - Filament V5 Setup Documentation

## Overview
The Filament V5 admin panel for Eseven Store is now fully configured with:
- **Dark Navy/Charcoal Sidebar** (matching Porto Shop reference)
- **Clean White Content Area**
- **RTL Layout Support** with dynamic language switching
- **Dark Mode Support**
- **Admin-Only Access** (no user registration allowed)

## Installation & Configuration Summary

### 1. **Filament V5 Core Setup**
✅ **Status**: Complete

- **Admin Panel Path**: `/admin`
- **Login Route**: `/admin/login`
- **Panel ID**: `admin`
- **Brand Name**: "Eseven Store Admin"
- **User Registration**: Disabled (admin-only access)
- **Dark Mode**: Enabled
- **Primary Color**: Amber (#f59e0b)

**File**: [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)

### 2. **Database Schema Updates**
✅ **Status**: Complete

Added language preference fields to users table:
```php
$table->string('language_preference')->default('en');
$table->boolean('is_rtl')->default(false);
```

**Migration**: [database/migrations/2026_05_04_200345_add_language_preference_to_users_table.php](database/migrations/2026_05_04_200345_add_language_preference_to_users_table.php)

### 3. **User Model Updates**
✅ **Status**: Complete

Updated `User` model to support language and RTL preferences:

```php
#[Fillable(['name', 'email', 'password', 'language_preference', 'is_rtl'])]
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_rtl' => 'boolean',
    ];
}
```

**File**: [app/Models/User.php](app/Models/User.php)

### 4. **RTL & Language Middleware**
✅ **Status**: Complete

Created `SetLanguageAndRtl` middleware that:
- Sets app locale from user's language preference
- Stores RTL preference in session
- Dynamically sets HTML `dir` attribute based on user's RTL setting
- Activates on every admin panel request

**File**: [app/Http/Middleware/SetLanguageAndRtl.php](app/Http/Middleware/SetLanguageAndRtl.php)

**Registered in**: [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php)

### 5. **Dark Sidebar Theme**
✅ **Status**: Complete

Custom theme CSS with:
- **Sidebar Colors**:
  - Background: `#1a1a2e` (dark navy/charcoal)
  - Text: `#ffffff` (white)
  - Hover: `#16213e` (lighter navy)
  - Active: `#0f3460` (blue accent)
  - Accent: `#f59e0b` (amber primary color)

- **Content Area**: White background with proper dark mode support
- **RTL Support**: Full CSS RTL directives with `[dir="rtl"]` selectors
- **Font**: Inter (system font)
- **Scrollbar**: Custom styling for dark theme

**File**: [resources/css/filament/admin/theme.css](resources/css/filament/admin/theme.css)

### 6. **Filament Configuration**
✅ **Status**: Complete

Published Filament configuration:
- **File**: [config/filament.php](config/filament.php)
- **Features**:
  - Admin panel branding ("Eseven Store Admin")
  - Dark mode enabled by default
  - Sidebar collapsible on desktop
  - Sticky topbar
  - Database notifications enabled

### 7. **Admin User Seeder**
✅ **Status**: Complete

Two test admin accounts created:

| Email | Password | Language | RTL |
|-------|----------|----------|-----|
| `admin@eseven.test` | `password` | English (en) | No |
| `admin-ar@eseven.test` | `password` | Arabic (ar) | Yes |

**Seeders**: 
- [database/seeders/AdminUserSeeder.php](database/seeders/AdminUserSeeder.php)
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php)

### 8. **Avatar Provider Support**
✅ **Status**: Ready

Filament's default avatar provider is enabled for user profile pictures. Users can upload avatars through the admin panel.

## Access Points

### Admin Login
- **URL**: `http://localhost/admin/login`
- **Test Account 1**:
  - Email: `admin@eseven.test`
  - Password: `password`
  - Language: English
  - Theme: Light sidebar (LTR)
  
- **Test Account 2**:
  - Email: `admin-ar@eseven.test`
  - Password: `password`
  - Language: Arabic
  - Theme: RTL-enabled dark sidebar

### Dashboard
- **URL**: `http://localhost/admin` (requires authentication)
- **Widgets**: AccountWidget, FilamentInfoWidget (customizable)

## Key Features Implemented

### 1. **Dark Sidebar Navigation**
- Navy/charcoal background (#1a1a2e)
- White text for readability
- Hover states with lighter navy background
- Active state with amber accent color
- Smooth transitions

### 2. **RTL Language Support**
- Dynamically toggles based on user's language preference
- CSS-based RTL implementation using `[dir="rtl"]` selectors
- All sidebar and form elements support RTL layout
- Session-based RTL preference storage

### 3. **Security**
- User registration disabled - admin-only access
- Middleware-based language/RTL setting
- Proper authentication guards
- Standard Laravel security middleware

### 4. **Customization Ready**
- Custom theme CSS in `resources/css/filament/admin/theme.css`
- Vite asset compilation for production
- Tailwind CSS compatible
- Dark mode media query support

## Admin Panel Routes

| Route | Purpose | Auth Required |
|-------|---------|---------------|
| `/admin/login` | Login page | No |
| `/admin` | Dashboard | Yes |
| `/admin/logout` | Logout | Yes |
| `/admin/account` | User account settings | Yes |

## Next Steps (Recommended)

1. **Create Admin Resources**:
   - Create Filament Resources for Products, Orders, Categories, etc.
   - Located in: `app/Filament/Resources/`

2. **Create Admin Pages**:
   - Custom admin pages for reports, settings
   - Located in: `app/Filament/Pages/`

3. **Create Admin Widgets**:
   - Dashboard widgets for analytics and KPIs
   - Located in: `app/Filament/Widgets/`

4. **Configure Email Settings**:
   - Update `.env` with SMTP credentials
   - Test email notifications

5. **Add More Admin Users**:
   - Use `php artisan tinker` to create additional admin accounts
   - Set appropriate language_preference and is_rtl values

6. **Customize Branding**:
   - Update theme.css with your brand colors
   - Add custom logo in resources/css/filament/admin/
   - Modify sidebar brand name in AdminPanelProvider

## Environment Configuration

Ensure your `.env` file has:

```env
APP_NAME=Eseven-Store
APP_URL=http://localhost
DB_DATABASE=eseven_store
DB_USERNAME=root
DB_PASSWORD=
```

## Testing the Admin Panel

### Test RTL Functionality:
1. Login with `admin-ar@eseven.test` / `password`
2. Observe the sidebar is right-aligned (RTL layout)
3. All text and UI elements should be right-to-left

### Test LTR Functionality:
1. Login with `admin@eseven.test` / `password`
2. Observe the sidebar is left-aligned (LTR layout)
3. All text and UI elements should be left-to-right

### Test Dark Mode:
1. Access admin panel and check sidebar colors
2. Browser dark mode preference will also affect the theme
3. White content area should show proper contrast

## File Structure

```
app/
├── Providers/Filament/
│   └── AdminPanelProvider.php          (Panel configuration)
├── Http/Middleware/
│   └── SetLanguageAndRtl.php           (RTL/Language middleware)
├── Models/User.php                      (Updated with language fields)
├── Filament/                            (Will grow with Resources/Pages/Widgets)
│   ├── Resources/
│   ├── Pages/
│   └── Widgets/
config/
└── filament.php                         (Filament configuration)
database/
├── migrations/
│   └── 2026_05_04_200345_*.php         (Language preference migration)
└── seeders/
    ├── AdminUserSeeder.php              (Admin users)
    └── DatabaseSeeder.php               (Calls AdminUserSeeder)
resources/
├── css/filament/admin/
│   └── theme.css                        (Dark sidebar theme)
└── views/vendor/filament-panels/        (Published Filament views)
```

## Support & Customization

### To Add More Languages:
1. Create additional admin users with different `language_preference` values
2. Add language files to `lang/` directory
3. Middleware will automatically apply the selected language

### To Change Sidebar Colors:
1. Edit `resources/css/filament/admin/theme.css`
2. Update CSS variables in `:root` section
3. Run `npm run build` to compile changes

### To Add Avatar Upload:
Filament provides built-in avatar support through the AccountWidget. Users can upload avatars directly through their profile.

### To Customize Dashboard:
Edit [app/Providers/Filament/AdminPanelProvider.php](app/Providers/Filament/AdminPanelProvider.php) to add custom pages and widgets.

---

**Installation Complete!** ✅

The Filament V5 admin panel is ready for backend development. Proceed with creating Resources for Products, Orders, Categories, and other entities.
