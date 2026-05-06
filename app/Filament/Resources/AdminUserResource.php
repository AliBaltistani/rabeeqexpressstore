<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\Admin;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class AdminUserResource extends Resource
{
    protected static ?string $model = Admin::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'Admin Users';
    protected static ?string $modelLabel = 'Admin User';
    protected static ?string $pluralModelLabel = 'Admin Users';
    protected static ?string $slug = 'admin-users';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $form): Schema
    {
        return $form->columns(1)->schema([
            Schemas\Components\Grid::make(3)->schema([
                Schemas\Components\Group::make()->schema([
                    Schemas\Components\Section::make('User Info')->schema([
                        Schemas\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')->required()->maxLength(255),
                            Forms\Components\TextInput::make('email')->email()->required()
                                ->unique(Admin::class, 'email', ignoreRecord: true),
                        ]),
                        Schemas\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('password')->password()->revealable()
                                ->required(fn(string $context): bool => $context === 'create')
                                ->dehydrateStateUsing(fn($state) => $state ? Hash::make($state) : null)
                                ->dehydrated(fn($state) => filled($state))
                                ->label(fn(string $context): string => $context === 'create' ? 'Password' : 'New Password (leave blank to keep)'),
                            Forms\Components\TextInput::make('password_confirmation')->password()->revealable()
                                ->requiredWith('password')->same('password')->label('Confirm Password'),
                        ]),
                        Forms\Components\FileUpload::make('avatar')->image()->directory('admins')->avatar(),
                        Forms\Components\Select::make('roles')->relationship('roles', 'name')
                            ->preload()->required()->label('Role'),
                    ]),
                ])->columnSpan(2),
                Schemas\Components\Group::make()->schema([
                    Schemas\Components\Section::make('Status')->schema([
                        Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                    ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('avatar')->circular()
                ->defaultImageUrl(fn(Admin $r) => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&background=3b82f6&color=fff'),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable()->sortable()->color('gray'),
            Tables\Columns\TextColumn::make('roles.name')->label('Role')->badge()->color('primary'),
            Tables\Columns\TextColumn::make('last_login_at')->label('Last Login')->dateTime('M d, Y H:i')->placeholder('Never'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('role')->relationship('roles', 'name'),
            Tables\Filters\TernaryFilter::make('is_active')->label('Status')->trueLabel('Active')->falseLabel('Inactive'),
        ])
        ->actions([
            Actions\EditAction::make(),
            Actions\DeleteAction::make()->visible(fn(Admin $record) => $record->id !== auth()->guard('admin')->id()),
        ])
        ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('admins.view'));
    }
}
