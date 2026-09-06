<?php

namespace App\Policies;

use App\Models\Admin;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    /**
     * Allow an admin user to download their own export file.
     *
     * Filament's DownloadExport controller checks for a policy with a `view`
     * method. If found, it uses Gate instead of the strict $export->user()->is($user)
     * identity check (which 403s when user_type is empty or mismatched).
     */
    public function view(Admin $user, Export $export): bool
    {
        // If user_type is correctly stored, verify ownership + class
        if (filled($export->user_type)) {
            return $export->user_type === Admin::class
                && $export->user_id === $user->id;
        }

        // Fallback: user_type not stored (morph resolution failed at export-time)
        // Allow any authenticated admin who owns the export by user_id
        return $export->user_id === $user->id;
    }
}
