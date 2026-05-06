<?php

namespace App\Filament\Pages\Settings;

use App\Models\Language;
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

    protected static string $view = 'filament.pages.settings.languages-page';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Languages';
    protected static ?string $title = 'Languages';

    protected function getTableQuery(): Builder
    {
        return Language::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('code')->badge()->color('primary'),
            Tables\Columns\TextColumn::make('direction')
                ->badge()
                ->color(fn(string $state) => $state === 'RTL' ? 'warning' : 'gray')
                ->formatStateUsing(fn(string $state) => strtoupper($state)),
            Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->form([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code')->required()->maxLength(5),
                    Forms\Components\Select::make('direction')->options(['LTR' => 'LTR', 'RTL' => 'RTL'])->required(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ]),

            Actions\Action::make('set_default')
                ->label('Set Default')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn(Language $record) => !$record->is_default)
                ->action(function (Language $record) {
                    Language::where('is_default', true)->update(['is_default' => false]);
                    $record->update(['is_default' => true, 'is_active' => true]);
                    Notification::make()->title('Default language updated')->success()->send();
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
                ->label('Add Language')
                ->form([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code')->required()->maxLength(5),
                    Forms\Components\Select::make('direction')->options(['LTR' => 'LTR', 'RTL' => 'RTL'])->required()->default('LTR'),
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
