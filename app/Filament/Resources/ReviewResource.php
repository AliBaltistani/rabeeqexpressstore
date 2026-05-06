<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Product;
use App\Models\Review;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.reviews');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Schemas\Components\Grid::make(3)
                    ->schema([
                        // Main content (left 2/3)
                        Schemas\Components\Group::make()
                            ->schema([
                                // Review Content (read-only)
                                Schemas\Components\Section::make('Review Details')
                                    ->schema([
                                        Forms\Components\Placeholder::make('product_name')
                                            ->label('Product')
                                            ->content(function (?Review $record): string {
                                                if (!$record || !$record->product) return '—';
                                                $name = $record->product->getTranslation('name', 'en');
                                                return $name;
                                            }),

                                        Forms\Components\Placeholder::make('customer_name')
                                            ->label('Customer')
                                            ->content(fn(?Review $record): string => $record?->user?->name ?? '—'),

                                        Forms\Components\Placeholder::make('rating_display')
                                            ->label('Rating')
                                            ->content(function (?Review $record): \Illuminate\Support\HtmlString {
                                                if (!$record) return new \Illuminate\Support\HtmlString('—');
                                                $stars = str_repeat('★', $record->rating) . str_repeat('☆', 5 - $record->rating);
                                                $color = $record->rating >= 4 ? '#f59e0b' : ($record->rating >= 3 ? '#eab308' : '#ef4444');
                                                return new \Illuminate\Support\HtmlString(
                                                    "<span style=\"color: {$color}; font-size: 18px; letter-spacing: 2px;\">{$stars}</span> <span style=\"color: #6b7280;\">({$record->rating}/5)</span>"
                                                );
                                            }),

                                        Forms\Components\Placeholder::make('review_title')
                                            ->label('Title')
                                            ->content(fn(?Review $record): string => $record?->title ?? '—'),

                                        Forms\Components\Placeholder::make('review_body')
                                            ->label('Review')
                                            ->content(fn(?Review $record): string => $record?->body ?? 'No review text.'),

                                        Forms\Components\Placeholder::make('review_date')
                                            ->label('Submitted')
                                            ->content(fn(?Review $record): string => $record?->created_at?->format('M d, Y H:i') ?? '—'),
                                    ]),

                                // Admin Reply
                                Schemas\Components\Section::make('Admin Reply')
                                    ->schema([
                                        Forms\Components\Textarea::make('admin_reply')
                                            ->label('Your Reply (shown on frontend)')
                                            ->rows(4)
                                            ->placeholder('Write a reply to this review...'),
                                    ]),
                            ])
                            ->columnSpan(2),

                        // Right sidebar
                        Schemas\Components\Group::make()
                            ->schema([
                                Schemas\Components\Section::make('Status')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->required()
                                            ->default(fn () => setting('general.reviews_require_approval', true) ? 'pending' : 'approved'),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->formatStateUsing(function ($state, Review $record): string {
                        if (!$record->product) return '—';
                        return $record->product->getTranslation('name', 'en');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(function (int $state): \Illuminate\Support\HtmlString {
                        $stars = str_repeat('★', $state) . str_repeat('☆', 5 - $state);
                        $color = $state >= 4 ? '#f59e0b' : ($state >= 3 ? '#eab308' : '#ef4444');
                        return new \Illuminate\Support\HtmlString(
                            "<span style=\"color: {$color};\">{$stars}</span>"
                        );
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(admin_date_format())
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(20)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        '1' => '1 Star',
                        '2' => '2 Stars',
                        '3' => '3 Stars',
                        '4' => '4 Stars',
                        '5' => '5 Stars',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Review $record): bool => $record->status !== 'approved')
                    ->action(fn(Review $record) => $record->update(['status' => 'approved']))
                    ->requiresConfirmation(),

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Review $record): bool => $record->status !== 'rejected')
                    ->action(fn(Review $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),

                Actions\EditAction::make()
                    ->label('Reply'),

                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('approve_selected')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['status' => 'approved']))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\BulkAction::make('reject_selected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['status' => 'rejected']))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('reviews.view'));
    }
}
