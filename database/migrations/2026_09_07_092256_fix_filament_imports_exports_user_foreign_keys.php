<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the `user_id` foreign key constraints on Filament's `imports` and
 * `exports` tables.
 *
 * Problem: Filament generates these tables with `user_id` referencing `users.id`,
 * but this project uses a separate `admins` table for the admin panel guard.
 * Inserting an export/import record with an admin's user_id fails with:
 *   SQLSTATE[23000]: Cannot add or update a child row: a foreign key constraint
 *   fails (`exports`, CONSTRAINT `exports_user_id_foreign` FOREIGN KEY
 *   (`user_id`) REFERENCES `users` (`id`)...)
 *
 * Fix: drop the FK constraints so `user_id` is a plain integer.
 * The `user_type` column (already present) handles the polymorphic lookup.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── exports table ──────────────────────────────────────────────────
        if (Schema::hasTable('exports')) {
            Schema::table('exports', function (Blueprint $table) {
                // Drop FK if it exists (safe — won't error if already absent)
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable) {
                    // Already removed or never existed on this server
                }
            });
        }

        // ── imports table ──────────────────────────────────────────────────
        if (Schema::hasTable('imports')) {
            Schema::table('imports', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable) {
                    // Already removed or never existed
                }
            });
        }

        // ── failed_import_rows table ───────────────────────────────────────
        if (Schema::hasTable('failed_import_rows')) {
            Schema::table('failed_import_rows', function (Blueprint $table) {
                try {
                    $table->dropForeign(['import_id']);
                } catch (\Throwable) {
                    // Not all versions have this FK
                }
            });
        }
    }

    public function down(): void
    {
        // Restore the original FK pointing to users (not safe in production,
        // would require all rows to have valid users.id — left intentionally blank)
    }
};
