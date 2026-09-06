<?php

namespace App\Policies;

use App\Models\Admin;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * Allow an admin user to view/download an import failure CSV.
     *
     * Filament's DownloadImportFailureCsv controller checks for this policy
     * first. If found, it uses Gate::forUser($user)->authorize('view', $import)
     * instead of the strict $import->user()->is($user) identity check.
     *
     * We handle two scenarios:
     * 1. user_type is correctly set to Admin::class (normal case).
     * 2. user_type is empty (happens when Filament resolves auth() via the
     *    default 'web' guard instead of 'admin') — in this case we allow any
     *    authenticated admin to access it.
     */
    public function view(Admin $user, Import $import): bool
    {
        // If user_type is correctly set, verify ownership
        if (filled($import->user_type)) {
            return $import->user_type === Admin::class
                && $import->user_id === $user->id;
        }

        // Fallback: user_type was not stored (morph resolution failed at
        // import-time). Allow any authenticated admin to download the CSV
        // since we can still check user_id ownership.
        return $import->user_id === $user->id;
    }
}
