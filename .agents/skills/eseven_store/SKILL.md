---
name: Rabeq Express Store Codebase Context
description: Deep context, architecture guidelines, and strict rules for AI agents interacting with the Rabeq Express Store Laravel + Vue project.
---

# Rabeq Express Store — Master AI Developer Context

Welcome to Rabeq Express Store. You are working on a highly structured, decoupled **Laravel 13** + **Filament V5** backend and a **Vue 3** + **Pinia** frontend. 

When you receive a prompt related to this project, you **MUST** adhere to the following rules, structures, and workflows. Do not deviate.

---

## 1. System Architecture & Boundaries

### The Backend (Laravel / Filament)
*   **Path:** `/` (Root directory)
*   **Role:** Exclusively responsible for database interactions, admin management (via Filament), and data provisioning via REST API (`routes/api.php`).
*   **Admin Panel:** Handled entirely by Filament (`app/Filament/Resources`). It is server-rendered via Livewire. **Do not use Vue here.**
*   **Models:** Located in `app/Models/`. They utilize Spatie MediaLibrary for image handling and Spatie Translatable for multi-language JSON fields (like `name`, `description`).
*   **API Response Protocol:** API responses MUST be clean JSON. Use `app/Http/Resources` whenever returning Models to format data cleanly (e.g., resolving absolute URLs for images using localized temporary signed URLs or storage links).

### The Frontend (Vue 3 / Vite)
*   **Path:** `/frontend/`
*   **Role:** The publicly facing storefront. Fully dynamic SPA.
*   **State Management:** Pinia stores (`src/stores/*`) manage Auth (`authStore`), Cart (`cartStore`), general Settings (`settingsStore`), and Wishlist (`wishlistStore`).
*   **Styling:** Follows a strict custom CSS design. **DO NOT introduce Tailwind, Bootstrap, or new component libraries unless explicitly requested.** Reuse existing CSS classes defined in existing components.

---

## 2. Strict Rules for Modifications

### 🛑 Rule A: No Hardcoded Data in Vue
The frontend must be 100% dynamic. 
- Banners, sliders, and products must be fetched from the layout API (`/api/v1/homepage`).
- Contact info, social links, and branding must come from the Settings API.
- Do not add dummy arrays in Vue templates.

### 🛑 Rule B: Multi-Language & RTL Compliance
- **Backend:** Models implement `$translatable`. Save/fetch localized data properly.
- **Frontend:** Operates on `vue-i18n`. When making a UI component, text must be localized via `$t('key')`. Visual layout MUST support RTL. Avoid absolute placements that break when flipped horizontally.

### 🛑 Rule C: Image Resolution & Media Library
- Images in Laravel are managed by `Spatie\MediaLibrary`.
- When an API resource returns an image, you MUST resolve its actual URL. If using local storage, do not return dummy placeholders.
- If an image doesn't exist, gracefully fall back to a predefined variable (e.g., `placeholder.png`), handled by the server Resource class.

### 🛑 Rule D: Filament Conventions
- Filament Resources (`app/Filament/Resources/*`) must be built using `Forms` and `Tables`.
- Keep the Filament UI clean, using matching dark-mode friendly layouts (defined in `/resources/css/filament/admin/theme.css`).
- Never alter the standard Filament layout rendering logic unless extending via proper Filament plugin hooks.

---

## 3. Workflow for Writing Code

When asked to build a new feature (e.g., "Add a Blog System"), you must follow this sequence:
1.  **Database:** Create Laravel migration and update logic to support translations and media if necessary.
2.  **Model:** Create the Eloquent Model (`app/Models/`), implement traits (`HasTranslations`, `HasMedia`, etc.).
3.  **Filament Resource:** Create `php artisan make:filament-resource ModelName`. Build Form (with localized tabs) and Table.
4.  **API Endpoint:** Create `/routes/api.php` route. Create Controller & API Resource (`app/Http/Resources`) to format data.
5.  **Vue Store / Fetch:** Create or update Pinia store / API service in `frontend/src/` to fetch from the new endpoint.
6.  **Vue Component:** Build the UI page/component binding to the fetched data, respecting i18n and RTL.

---

## 4. Current State References
Before altering logic, consult the existing reference texts in the root folder:
- **`Rabeq_Express_Store_Complete_Project_Blueprint.md`**: Defines schema, original specs, and scope.
- **`frontend_status_and_plan.md`**: Outlines components left to be built on the frontend.
- **`ADMIN_PANEL_COMPLETE.md`**: Documentation for the Filament admin dashboard implementation.

*Remember: This is a premium eCommerce storefront. Maintain clean models, modular code, and elegant, performant layouts.*
