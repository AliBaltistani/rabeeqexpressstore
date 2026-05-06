<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderTracking;
use App\Notifications\OrderStatusNotification;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            // Update Status Action
            Actions\Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Order Status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                            'refunded' => 'Refunded',
                        ])
                        ->default(fn() => $this->getRecord()->status)
                        ->required(),

                    Forms\Components\Textarea::make('comment')
                        ->label('Comment (optional)')
                        ->rows(3)
                        ->placeholder('Add a note about this status change...'),

                    Forms\Components\Checkbox::make('notify_customer')
                        ->label('Notify Customer via Email')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
                    $oldStatus = $record->status;

                    // Update order status
                    $record->update(['status' => $data['status']]);

                    // Create status history entry
                    OrderStatusHistory::create([
                        'order_id' => $record->id,
                        'status' => $data['status'],
                        'comment' => $data['comment'] ?? null,
                        'is_customer_notified' => $data['notify_customer'] ?? false,
                        'created_by' => auth()->guard('admin')->id(),
                    ]);

                    // Notify customer if checked
                    if ($data['notify_customer'] && $record->user) {
                        $record->user->notify(new OrderStatusNotification(
                            $record,
                            $data['status'],
                            $data['comment'] ?? null,
                        ));
                    }

                    Notification::make()
                        ->title('Status Updated')
                        ->body("Order status changed from {$oldStatus} to {$data['status']}")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Save Tracking Action
            Actions\Action::make('save_tracking')
                ->label('Tracking')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->form([
                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Tracking Number')
                        ->default(fn() => $this->getRecord()->tracking?->tracking_number)
                        ->placeholder('Enter tracking number'),

                    Forms\Components\Select::make('carrier')
                        ->label('Carrier')
                        ->options([
                            'DHL' => 'DHL',
                            'Aramex' => 'Aramex',
                            'SMSA' => 'SMSA',
                            'USPS' => 'USPS',
                            'FedEx' => 'FedEx',
                            'UPS' => 'UPS',
                            'Other' => 'Other',
                        ])
                        ->default(fn() => $this->getRecord()->tracking?->carrier)
                        ->placeholder('Select carrier'),

                    Forms\Components\TextInput::make('tracking_url')
                        ->label('Tracking URL')
                        ->url()
                        ->default(fn() => $this->getRecord()->tracking?->tracking_url)
                        ->placeholder('https://...'),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    OrderTracking::updateOrCreate(
                        ['order_id' => $record->id],
                        [
                            'tracking_number' => $data['tracking_number'] ?? null,
                            'carrier' => $data['carrier'] ?? null,
                            'tracking_url' => $data['tracking_url'] ?? null,
                        ]
                    );

                    Notification::make()
                        ->title('Tracking Updated')
                        ->body('Tracking information has been saved.')
                        ->success()
                        ->send();
                }),

            // Download Invoice
            Actions\Action::make('download_invoice')
                ->label('Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $service = new InvoiceService();
                    return $service->download($this->getRecord());
                }),

            // Back
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(OrderResource::getUrl('index')),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        // ── MAIN LEFT COLUMN (2/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                // Order Items
                                Schemas\Components\Section::make('Order Items')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.order-items')
                                            ->viewData([
                                                'record' => $this->getRecord(),
                                            ]),
                                    ]),

                                // Billing Address
                                Schemas\Components\Section::make('Billing Address')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.address-card')
                                            ->viewData([
                                                'address' => $this->getRecord()->billingAddress,
                                                'type' => 'billing',
                                            ]),
                                    ])
                                    ->collapsible(),

                                // Shipping Address
                                Schemas\Components\Section::make('Shipping Address')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.address-card')
                                            ->viewData([
                                                'address' => $this->getRecord()->shippingAddress,
                                                'type' => 'shipping',
                                            ]),
                                    ])
                                    ->collapsible(),

                                // Status History
                                Schemas\Components\Section::make('Status History')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.status-history')
                                            ->viewData([
                                                'histories' => $this->getRecord()->statusHistories()->with('admin')->latest()->get(),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ])
                            ->columnSpan(2),

                        // ── RIGHT SIDEBAR (1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                // Tracking Info
                                Schemas\Components\Section::make('Tracking Info')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.tracking-info')
                                            ->viewData([
                                                'tracking' => $this->getRecord()->tracking,
                                            ]),
                                    ]),

                                // Order Info
                                Schemas\Components\Section::make('Order Info')
                                    ->schema([
                                        Forms\Components\Placeholder::make('status_display')
                                            ->label('Status')
                                            ->content(fn(): string => ucfirst($this->getRecord()->status)),

                                        Forms\Components\Placeholder::make('payment_status_display')
                                            ->label('Payment Status')
                                            ->content(fn(): string => ucfirst(str_replace('_', ' ', $this->getRecord()->payment_status))),

                                        Forms\Components\Placeholder::make('payment_method_display')
                                            ->label('Payment Method')
                                            ->content(fn(): string => ucfirst(str_replace('_', ' ', $this->getRecord()->payment_method ?? '—'))),

                                        Forms\Components\Placeholder::make('transaction_id_display')
                                            ->label('Transaction ID')
                                            ->content(fn(): string => $this->getRecord()->transaction_id ?? '—'),

                                        Forms\Components\Placeholder::make('ip_display')
                                            ->label('IP Address')
                                            ->content(fn(): string => $this->getRecord()->ip_address ?? '—'),

                                        Forms\Components\Placeholder::make('placed_display')
                                            ->label('Placed')
                                            ->content(fn(): string => $this->getRecord()->created_at?->format('M d, Y \a\t H:i') ?? '—'),

                                        Forms\Components\Placeholder::make('currency_display')
                                            ->label('Currency')
                                            ->content(fn(): string => $this->getRecord()->currency_code . ' (Rate: ' . number_format($this->getRecord()->currency_rate, 4) . ')'),
                                    ]),

                                // Customer Info
                                Schemas\Components\Section::make('Customer')
                                    ->schema([
                                        Forms\Components\Placeholder::make('customer_name')
                                            ->label('Name')
                                            ->content(fn(): string => $this->getRecord()->user?->name ?? $this->getRecord()->guest_name ?? 'Guest'),

                                        Forms\Components\Placeholder::make('customer_email')
                                            ->label('Email')
                                            ->content(fn(): string => $this->getRecord()->user?->email ?? $this->getRecord()->guest_email ?? '—'),

                                        Forms\Components\Placeholder::make('customer_phone')
                                            ->label('Phone')
                                            ->content(fn(): string => $this->getRecord()->user?->phone ?? $this->getRecord()->guest_phone ?? '—'),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
