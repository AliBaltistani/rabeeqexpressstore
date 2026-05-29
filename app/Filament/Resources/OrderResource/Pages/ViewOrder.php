<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderTracking;
use App\Models\ShippingCarrier;
use App\Notifications\OrderStatusNotification;
use App\Services\InvoiceService;
use App\Services\OrderLifecycleService;
use App\Services\SmsaShipmentService;
use Filament\Actions;
use Illuminate\Support\Facades\Notification as NotificationFacade;
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

                    // Use OrderLifecycleService for audit-logged transitions
                    $lifecycle = app(OrderLifecycleService::class);
                    $lifecycle->transitionStatus(
                        $record,
                        $data['status'],
                        auth()->guard('admin')->id(),
                        $data['comment'] ?? null,
                    );

                    // Notify customer if checked
                    $notified = false;
                    if ($data['notify_customer']) {
                        try {
                            $notification = new OrderStatusNotification(
                                $record->fresh(),
                                $data['status'],
                                $data['comment'] ?? null,
                            );

                            if ($record->user) {
                                // Registered user
                                $record->user->notify($notification);
                                $notified = true;
                            } elseif ($record->guest_email) {
                                // Guest order — use on-demand notification
                                NotificationFacade::route('mail', $record->guest_email)
                                    ->notify($notification);
                                $notified = true;
                            }

                            if ($notified) {
                                // Record that customer was notified in the status history
                                $record->statusHistories()
                                    ->where('status_to', $data['status'])
                                    ->latest()
                                    ->first()
                                    ?->update(['is_customer_notified' => true]);
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Email Notification Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }

                    Notification::make()
                        ->title('Status Updated')
                        ->body(
                            "Order status changed from {$oldStatus} to {$data['status']}"
                            . ($notified ? ' — Customer notified via email.' : '')
                        )
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            // Book SMSA Shipment
            Actions\Action::make('book_smsa_shipment')
                ->label('Book SMSA Shipment')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn() => (
                    in_array($this->getRecord()->status, ['processing', 'paid']) &&
                    empty($this->getRecord()->tracking_number)
                ))
                ->requiresConfirmation()
                ->modalHeading('Book SMSA Shipment')
                ->modalDescription('This will create a shipment with SMSA Express and assign a tracking number to this order.')
                ->action(function (): void {
                    $service = app(SmsaShipmentService::class);
                    $result = $service->bookShipment($this->getRecord());

                    if ($result['success']) {
                        Notification::make()
                            ->title('SMSA Shipment Booked')
                            ->body('AWB/Tracking: ' . $result['awb'])
                            ->success()
                            ->send();

                        $this->refreshFormData(['tracking_number', 'shipping_status']);
                    } else {
                        Notification::make()
                            ->title('SMSA Booking Failed')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                }),

            // Download SMSA Shipping PDF
            Actions\Action::make('download_smsa_pdf')
                ->label('Shipping PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn() => !empty($this->getRecord()->tracking_number))
                ->action(function () {
                    $service = app(SmsaShipmentService::class);
                    $result = $service->getShipmentPdf($this->getRecord()->tracking_number);

                    if ($result['success'] && $result['pdf_base64']) {
                        $pdfContent = base64_decode($result['pdf_base64']);
                        return response()->streamDownload(
                            fn() => print($pdfContent),
                            "shipment-{$this->getRecord()->order_number}.pdf",
                            ['Content-Type' => 'application/pdf']
                        );
                    }

                    Notification::make()
                        ->title('PDF Download Failed')
                        ->body($result['error'] ?? 'Unable to download shipping PDF.')
                        ->danger()
                        ->send();
                }),

            // Save Tracking Action
            Actions\Action::make('save_tracking')
                ->label('Tracking')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('carrier')
                        ->label('Shipping Company')
                        ->options(fn () => ShippingCarrier::active()->ordered()->pluck('name', 'code')->toArray())
                        ->searchable()
                        ->default(fn() => $this->getRecord()->tracking?->carrier)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($set, $get, ?string $state) {
                            $trackingNumber = $get('tracking_number');
                            if ($state && $state !== 'Other' && $trackingNumber) {
                                $set('tracking_url', self::generateTrackingUrl($state, $trackingNumber));
                            }
                        }),

                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Tracking / AWB Number')
                        ->default(fn() => $this->getRecord()->tracking?->tracking_number)
                        ->placeholder('Enter tracking number')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($set, $get, ?string $state) {
                            $carrier = $get('carrier');
                            if ($carrier && $carrier !== 'Other' && $state) {
                                $set('tracking_url', self::generateTrackingUrl($carrier, $state));
                            }
                        }),

                    Forms\Components\TextInput::make('tracking_url')
                        ->label('Tracking URL')
                        ->url()
                        ->default(fn() => $this->getRecord()->tracking?->tracking_url)
                        ->placeholder('Auto-generated from carrier + tracking number')
                        ->helperText('Auto-filled based on carrier. You can override if needed.'),

                    Forms\Components\DatePicker::make('estimated_delivery')
                        ->label('Estimated Delivery')
                        ->default(fn() => $this->getRecord()->tracking?->estimated_delivery)
                        ->placeholder('Select estimated delivery date'),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    // Auto-generate URL if not manually provided
                    if (empty($data['tracking_url']) && !empty($data['carrier']) && $data['carrier'] !== 'Other' && !empty($data['tracking_number'])) {
                        $data['tracking_url'] = self::generateTrackingUrl($data['carrier'], $data['tracking_number']);
                    }

                    OrderTracking::updateOrCreate(
                        ['order_id' => $record->id],
                        [
                            'tracking_number'    => $data['tracking_number'] ?? null,
                            'carrier'            => $data['carrier'] ?? null,
                            'tracking_url'       => $data['tracking_url'] ?? null,
                            'estimated_delivery' => $data['estimated_delivery'] ?? null,
                        ]
                    );

                    // Also update the orders table tracking_number
                    if (!empty($data['tracking_number'])) {
                        $record->update(['tracking_number' => $data['tracking_number']]);
                    }

                    Notification::make()
                        ->title('Tracking Updated')
                        ->body('Tracking information has been saved for ' . ($data['carrier'] ?? 'carrier') . '.')
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

                                Schemas\Components\Grid::make(2)
                                    ->schema([
                                        // Billing Address
                                        Schemas\Components\Section::make('Billing Address')
                                            ->schema([
                                                Schemas\Components\View::make('filament.resources.order-resource.address-card')
                                                    ->viewData([
                                                        'address' => $this->getRecord()->billingAddress,
                                                        'type' => 'billing',
                                                    ]),
                                            ])
                                            ->columnSpan(1)
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
                                            ->columnSpan(1)
                                            ->collapsible(),
                                    ]),

                                // Order Timeline — Interactive Status History
                                Schemas\Components\Section::make('Order Timeline')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.order-resource.order-timeline')
                                            ->viewData([
                                                'histories' => $this->getRecord()->statusHistories()->with('admin', 'changedByAdmin')->orderBy('created_at', 'asc')->get(),
                                                'currentStatus' => $this->getRecord()->status,
                                            ]),
                                    ]),
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
                                                'trackingNumber' => $this->getRecord()->tracking_number,
                                                'shippingStatus' => $this->getRecord()->shipping_status,
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

                                        Forms\Components\Placeholder::make('payment_gateway_display')
                                            ->label('Payment Gateway')
                                            ->content(fn(): string => ucfirst($this->getRecord()->payment_gateway ?? '—')),

                                        Forms\Components\Placeholder::make('transaction_id_display')
                                            ->label('Transaction ID')
                                            ->content(fn(): string => $this->getRecord()->transaction_id ?? '—'),

                                        Forms\Components\Placeholder::make('payment_intent_display')
                                            ->label('Payment Intent')
                                            ->content(fn(): string => $this->getRecord()->payment_intent_id ?? '—'),

                                        Forms\Components\Placeholder::make('ip_display')
                                            ->label('IP Address')
                                            ->content(fn(): string => $this->getRecord()->ip_address ?? '—'),

                                        Forms\Components\Placeholder::make('placed_display')
                                            ->label('Placed')
                                            ->content(fn(): string => $this->getRecord()->created_at?->format('M d, Y \a\t H:i') ?? '—'),

                                        Forms\Components\Placeholder::make('currency_display')
                                            ->label('Currency')
                                            ->content(fn(): string => $this->getRecord()->currency_code . ' (Rate: ' . number_format((float) $this->getRecord()->currency_rate, 4) . ')'),
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

    /**
     * Generate a tracking URL based on carrier code and tracking number.
     * Fetches the URL template from the ShippingCarrier database record.
     */
    public static function generateTrackingUrl(string $carrierCode, string $trackingNumber): string
    {
        $carrier = ShippingCarrier::where('code', $carrierCode)->first();

        if ($carrier) {
            return $carrier->getTrackingUrl($trackingNumber) ?? '';
        }

        return '';
    }
}
