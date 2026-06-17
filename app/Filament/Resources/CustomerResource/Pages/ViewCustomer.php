<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use App\Notifications\CustomerEmailNotification;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            // Send Email Action
            Actions\Action::make('send_email')
                ->label('Send Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->form([
                    Forms\Components\TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Email subject...'),

                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(5)
                        ->placeholder('Type your message here...'),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->notify(new CustomerEmailNotification(
                        $data['subject'],
                        $data['message'],
                    ));

                    Notification::make()
                        ->title('Email Sent')
                        ->body('Email has been queued for delivery.')
                        ->success()
                        ->send();
                }),

            // Ban / Unban
            $record->is_banned
                ? Actions\Action::make('unban')
                    ->label('Unban Customer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (): void {
                        $this->getRecord()->update([
                            'is_banned' => false,
                            'ban_reason' => null,
                        ]);
                        Notification::make()->title('Customer Unbanned')->success()->send();
                        $this->refreshFormData(['is_banned']);
                    })
                    ->requiresConfirmation()
                : Actions\Action::make('ban')
                    ->label('Ban Customer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        $this->getRecord()->update([
                            'is_banned' => true,
                            'ban_reason' => $data['ban_reason'],
                        ]);
                        Notification::make()->title('Customer Banned')->warning()->send();
                        $this->refreshFormData(['is_banned']);
                    })
                    ->requiresConfirmation(),

            // Back
            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(CustomerResource::getUrl('index')),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        // ── LEFT COLUMN (2/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                // Customer Info
                                Schemas\Components\Section::make('Customer Information')
                                    ->schema([
                                        Schemas\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Placeholder::make('avatar_display')
                                                    ->label('')
                                                    ->content(function (): \Illuminate\Support\HtmlString {
                                                        $record = $this->getRecord();
                                                        $avatarUrl = $record->avatar
                                                            ? asset('storage/' . $record->avatar)
                                                            : 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=3b82f6&color=fff&size=120';
                                                        return new \Illuminate\Support\HtmlString(
                                                            '<img src="' . $avatarUrl . '" alt="Avatar" class="w-20 h-20 rounded-full">'
                                                        );
                                                    }),

                                                Schemas\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Placeholder::make('customer_name')
                                                            ->label('Full Name')
                                                            ->content(fn(): string => $this->getRecord()->name ?? '—'),

                                                        Forms\Components\Placeholder::make('customer_email')
                                                            ->label('Email')
                                                            ->content(fn(): string => $this->getRecord()->email ?? '—'),

                                                        Forms\Components\Placeholder::make('customer_phone')
                                                            ->label('Phone')
                                                            ->content(fn(): string => $this->getRecord()->phone ?? '—'),
                                                    ]),
                                            ]),
                                    ]),

                                // Stats
                                Schemas\Components\Section::make('Statistics')
                                    ->schema([
                                        Schemas\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Placeholder::make('total_orders')
                                                    ->label('Total Orders')
                                                    ->content(fn(): string => (string) $this->getRecord()->orders()->count()),

                                                Forms\Components\Placeholder::make('total_spent')
                                                    ->label('Total Spent')
                                                    ->content(fn(): string => number_format(
                                                        $this->getRecord()->orders()->where('payment_status', 'paid')->sum('total'),
                                                        2
                                                    ) . ' SAR'),

                                                Forms\Components\Placeholder::make('member_since')
                                                    ->label('Member Since')
                                                    ->content(fn(): string => $this->getRecord()->created_at->format('M d, Y')),
                                            ]),
                                    ]),

                                // Ban Info (if banned)
                                ...(
                                    $this->getRecord()->is_banned
                                    ? [
                                        Schemas\Components\Section::make('⚠ Ban Information')
                                            ->schema([
                                                Forms\Components\Placeholder::make('ban_status')
                                                    ->label('Status')
                                                    ->content(fn(): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                                                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Banned</span>'
                                                    )),

                                                Forms\Components\Placeholder::make('ban_reason_display')
                                                    ->label('Reason')
                                                    ->content(fn(): string => $this->getRecord()->ban_reason ?? 'No reason provided'),
                                            ])
                                            ->collapsed(false),
                                    ]
                                    : []
                                ),

                                // Order History
                                Schemas\Components\Section::make('Order History')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.customer-resource.order-history')
                                            ->viewData([
                                                'orders' => $this->getRecord()->orders()->latest()->limit(20)->get(),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ])
                            ->columnSpan(2),

                        // ── RIGHT SIDEBAR (1/3) ──
                        Schemas\Components\Group::make()
                            ->schema([
                                // Account Details
                                Schemas\Components\Section::make('Account Details')
                                    ->schema([
                                        Forms\Components\Placeholder::make('account_status')
                                            ->label('Account Status')
                                            ->content(function (): \Illuminate\Support\HtmlString {
                                                $record = $this->getRecord();
                                                if ($record->is_banned) {
                                                    return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Banned</span>');
                                                }
                                                if ($record->is_active) {
                                                    return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>');
                                                }
                                                return new \Illuminate\Support\HtmlString('<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Inactive</span>');
                                            }),

                                        Forms\Components\Placeholder::make('email_verified')
                                            ->label('Email Verified')
                                            ->content(fn(): string => $this->getRecord()->email_verified_at ? $this->getRecord()->email_verified_at->format('M d, Y') : 'Not verified'),

                                        Forms\Components\Placeholder::make('reviews_count')
                                            ->label('Reviews')
                                            ->content(fn(): string => (string) $this->getRecord()->reviews()->count()),

                                        Forms\Components\Placeholder::make('wishlist_count')
                                            ->label('Wishlist Items')
                                            ->content(fn(): string => (string) $this->getRecord()->wishlists()->count()),
                                    ]),

                                // Saved Addresses
                                Schemas\Components\Section::make('Saved Addresses')
                                    ->schema([
                                        Schemas\Components\View::make('filament.resources.customer-resource.addresses')
                                            ->viewData([
                                                'addresses' => $this->getRecord()->addresses,
                                            ]),
                                    ])
                                    ->collapsible(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
