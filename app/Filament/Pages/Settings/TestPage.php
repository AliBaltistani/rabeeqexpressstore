<?php

namespace App\Filament\Pages\Settings;

use Filament\Pages\Page;

class TestPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings.test-page';
}
