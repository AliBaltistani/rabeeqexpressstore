<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $relatedResource = OrderResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('order_number')
                    ->label('Order#')
                    ->searchable()
                    ->sortable()
                    ->url(fn(\App\Models\Order $record) => \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $record])),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge(),
                \Filament\Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment'),
                \Filament\Tables\Columns\TextColumn::make('total')
                    ->money('SAR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                // CreateAction disabled since it's order history only
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->url(fn(\App\Models\Order $record) => \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
