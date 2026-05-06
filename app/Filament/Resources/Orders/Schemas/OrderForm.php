<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->schema([
                    Section::make('Order Items')->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Grid::make(5)->schema([
                                    Placeholder::make('product')
                                        ->label('Product')
                                        ->content(fn($record) => optional($record)->product_name)
                                        ->columnSpan(2),
                                    Placeholder::make('sku')
                                        ->label('SKU')
                                        ->content(fn($record) => optional($record)->product_sku),
                                    Placeholder::make('price')
                                        ->label('Price')
                                        ->content(fn($record) => optional($record)->unit_price),
                                    Placeholder::make('qty')
                                        ->label('Qty')
                                        ->content(fn($record) => optional($record)->quantity),
                                    Placeholder::make('total')
                                        ->label('Total')
                                        ->content(fn($record) => optional($record)->total),
                                ])
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),

                        Grid::make(4)->schema([
                            Placeholder::make('subtotal_display')
                                ->label('Subtotal')->content(fn($record) => optional($record)->subtotal),
                            Placeholder::make('discount_display')
                                ->label('Discount')->content(fn($record) => optional($record)->discount_amount),
                            Placeholder::make('shipping_display')
                                ->label('Shipping')->content(fn($record) => optional($record)->shipping_amount),
                            Placeholder::make('tax_display')
                                ->label('Tax')->content(fn($record) => optional($record)->tax_amount),
                        ])
                    ]),

                    Grid::make(2)->schema([
                        Section::make('Billing Address')
                            ->relationship('billingAddress')
                            ->schema([
                                TextInput::make('first_name')->disabled(),
                                TextInput::make('last_name')->disabled(),
                                TextInput::make('phone')->disabled(),
                                TextInput::make('address_line_1')->disabled(),
                                TextInput::make('city')->disabled(),
                                TextInput::make('country')->disabled(),
                            ])->columnSpan(1),

                        Section::make('Shipping Address')
                            ->relationship('shippingAddress')
                            ->schema([
                                TextInput::make('first_name')->disabled(),
                                TextInput::make('last_name')->disabled(),
                                TextInput::make('phone')->disabled(),
                                TextInput::make('address_line_1')->disabled(),
                                TextInput::make('city')->disabled(),
                                TextInput::make('country')->disabled(),
                            ])->columnSpan(1),
                    ]),

                    Section::make('Status History')->schema([
                        Repeater::make('statusHistories')
                            ->relationship('statusHistories')
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('status')->content(fn($record) => optional($record)->status),
                                    Placeholder::make('comment')->content(fn($record) => optional($record)->comment),
                                    Placeholder::make('created_at')->content(fn($record) => optional($record)->created_at?->toDateTimeString()),
                                ])
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                    ])
                ])->columnSpan(['lg' => 2]),

                Group::make()->schema([
                    Section::make('Update Status')->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Textarea::make('status_comment')
                            ->label('Comment (Optional)')
                            ->placeholder('This will be added to status history...')
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Checkbox::make('notify_customer')
                            ->label('Notify Customer')
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ]),

                    Section::make('Tracking')
                        ->relationship('tracking')
                        ->schema([
                            TextInput::make('tracking_number'),
                            Select::make('carrier')->options([
                                'DHL' => 'DHL',
                                'Aramex' => 'Aramex',
                                'SMSA' => 'SMSA',
                                'USPS' => 'USPS',
                                'FedEx' => 'FedEx',
                                'UPS' => 'UPS',
                                'Other' => 'Other',
                            ]),
                            TextInput::make('tracking_url')->url(),
                        ]),

                    Section::make('Order Info')->schema([
                        Placeholder::make('payment_method')->content(fn($record) => optional($record)->payment_method),
                        Placeholder::make('transaction_id')->content(fn($record) => optional($record)->transaction_id ?? '-'),
                        Placeholder::make('ip_address')->content(fn($record) => optional($record)->ip_address ?? '-'),
                        Placeholder::make('created_at')->content(fn($record) => optional($record)->created_at?->toDayDateTimeString()),
                    ])
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
