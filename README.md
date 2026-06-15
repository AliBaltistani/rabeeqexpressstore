# 🛍️ Rabeq Express Store

Rabeq Express Store is a modern, high-performance, and multi-lingual e-commerce platform. It features a robust decoupled architecture using a **Laravel 13 API backend** paired with a **Vue.js 3 frontend**, all managed by a powerful **Filament V5 admin panel**.

---

## 🚀 Tech Stack & Core Technologies

### Backend & Admin
*   **Framework:** [Laravel 13](https://laravel.com/)
*   **Admin Panel:** [Filament V5](https://filamentphp.com/) (powered by Livewire 4)
*   **Database:** MySQL 8+
*   **Authentication:** Laravel Sanctum (for API), Session (for Admin)
*   **Key Packages:** Spatie Media Library, Spatie Translatable, Spatie Permission.

### Frontend (Storefront)
*   **Framework:** [Vue.js 3](https://vuejs.org/) (Composition API)
*   **Build Tool:** Vite
*   **State Management:** Pinia (`authStore`, `cartStore`, `settingsStore`, `wishlistStore`)
*   **Routing:** Vue Router
*   **Styling:** Custom CSS (adhering strictly to existing layout and variables)
*   **Localization:** `vue-i18n` (EN/LTR and AR/RTL support)

---

## 🏗️ Project Architecture

This is a **monorepo-style** structure where Laravel servers the API/Admin and Vue manages the public storefront.

```text
eseven-store/ → rabeq-express-store/
├── app/                  # Backend Logic (Models, API Controllers, Filament Resources)
├── bootstrap/            # Laravel Bootstrapping
├── config/               # Global Laravel Configuration
├── database/             # Migrations, Factories, and Seeders
├── frontend/             # 🟢 Vue 3 SPA Codebase (Vite root)
│   ├── src/
│   │   ├── components/   # Reusable Vue components
│   │   ├── pages/        # Route views (HomePage, CheckoutPage, etc.)
│   │   ├── stores/       # Pinia stores for global state
│   │   ├── router/       # Vue Router configuration
│   │   └── i18n/         # Language dictionaries
├── public/               # Public assets and Vue build output (if integrated)
├── routes/               # Backend routing (api.php for endpoints)
└── storage/              # App storage (Media uploads, Logs, Framework cache)
```

---

## ⚙️ How to Run Locally

### 1. Backend Setup
1. Clone the repository and configure `.env` based on `.env.example`.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Generate application key, link storage, and run migrations:
   ```bash
   php artisan key:generate
   php artisan storage:link
   php artisan migrate --seed
   ```
4. Start the Laravel development server:
   ```bash
   php artisan serve
   ```
   *The API will be available at `http://127.0.0.1:8000/api/v1` and the Admin at `http://127.0.0.1:8000/admin`.*

### 2. Frontend Setup
1. Navigate to the frontend directory:
   ```bash
   cd frontend
   ```
2. Install Node dependencies:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
   *The Storefront will be dynamically served at `http://localhost:5173`.*

---

## 🛡️ Key Documentation

For deeper dives into the implementation details, refer to the following comprehensive guides located in the root:
*   `Rabeq_Express_Store_Complete_Project_Blueprint.md` - Complete schema, endpoints, and structural requirements.
*   `frontend_status_and_plan.md` - Tracking the current completion of Vue frontend features.
*   `ADMIN_PANEL_COMPLETE.md` - Specialized instructions regarding the Filament Admin configuration.

---

## 🤖 AI Agent Guidelines
If you are an AI assistant working on this repository, please review `.agents/skills/rabeq_express_store/SKILL.md` to understand strict architectural rules, API fetching protocols, and styling constraints before making any modifications.
