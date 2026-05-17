<?php

namespace App\Filament\Resources\ShippingZoneResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Forms\Components;
use Filament\Schemas;
use Illuminate\Database\Eloquent\Builder;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Tabs::make('Translations')
                    ->tabs([
                        Schemas\Components\Tabs\Tab::make('English')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Components\TextInput::make('name.en')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Rate Name (English)'),
                            ]),
                        Schemas\Components\Tabs\Tab::make('Arabic')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Components\TextInput::make('name.ar')
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'rtl'])
                                    ->label('Rate Name (Arabic)'),
                            ]),
                    ])
                    ->columnSpanFull(),
                Components\Select::make('method')
                    ->required()
                    ->options([
                        'flat' => 'Flat Rate',
                        'free' => 'Free Shipping',
                        'weight_based' => 'Weight Based',
                    ])
                    ->label('Shipping Method Code'),
                Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix(currency_symbol())
                    ->default(0.00),
                Components\TextInput::make('min_order_for_free')
                    ->numeric()
                    ->prefix(currency_symbol())
                    ->label('Min. Order for Free Shipping (Leave empty if none)'),
                Components\TextInput::make('min_weight')
                    ->numeric()
                    ->label('Min Weight (Optional)'),
                Components\TextInput::make('max_weight')
                    ->numeric()
                    ->label('Max Weight (Optional)'),
                Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn(\App\Models\ShippingRate $record) => $record->getTranslation('name', app()->getLocale()))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name', 'like', "%{$search}%");
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('method')
                    ->colors([
                        'primary' => 'flat',
                        'success' => 'free',
                        'warning' => 'weight_based',
                    ]),
                Tables\Columns\TextColumn::make('price')
                    ->money(currency_code())
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
