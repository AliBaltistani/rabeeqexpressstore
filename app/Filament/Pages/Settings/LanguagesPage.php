<?php

namespace App\Filament\Pages\Settings;

use App\Models\Language;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LanguagesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.settings.languages-page';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings_pages.languages');
    }

    public function getTitle(): string
    {
        return __('admin.settings_pages.languages');
    }

    protected function getTableQuery(): Builder
    {
        return Language::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('admin.common.name'))
                ->sortable(),
            Tables\Columns\TextColumn::make('code')
                ->badge()
                ->color('primary'),
            Tables\Columns\TextColumn::make('direction')
                ->badge()
                ->color(fn(string $state) => strtoupper($state) === 'RTL' ? 'warning' : 'gray')
                ->formatStateUsing(fn(string $state) => strtoupper($state)),
            Tables\Columns\IconColumn::make('is_default')
                ->boolean()
                ->label(__('admin.common.active')),
            Tables\Columns\ToggleColumn::make('is_active')
                ->label(__('admin.common.active')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->form([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code')->required()->maxLength(5),
                    Forms\Components\Select::make('direction')
                        ->options(['LTR' => 'LTR (Left to Right)', 'RTL' => 'RTL (Right to Left)'])
                        ->required(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),

            Actions\Action::make('set_default')
                ->label(__('admin.common.active'))
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn(Language $record) => !$record->is_default)
                ->action(function (Language $record) {
                    // Unset all other defaults
                    Language::where('is_default', true)->update(['is_default' => false]);
                    $record->update(['is_default' => true, 'is_active' => true]);

                    // Sync to settings table so AppServiceProvider picks it up
                    Setting::set('general.default_language', $record->code);

                    Notification::make()
                        ->title(__('admin.settings.settings_saved'))
                        ->body("Default language set to {$record->name}")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),

            Actions\DeleteAction::make()
                ->visible(fn(Language $record) => !$record->is_default && Language::count() > 1),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->model(Language::class)
                ->label(__('admin.common.create'))
                ->form([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code')->required()->maxLength(5),
                    Forms\Components\Select::make('direction')
                        ->options(['LTR' => 'LTR (Left to Right)', 'RTL' => 'RTL (Right to Left)'])
                        ->required()
                        ->default('LTR'),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}
