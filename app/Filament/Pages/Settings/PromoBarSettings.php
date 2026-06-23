<?php

namespace App\Filament\Pages\Settings;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components;
use BackedEnum;

class PromoBarSettings extends Page
{
    protected string $view = 'filament.pages.settings.promo-bar-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 6;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Promo Bar';
    }

    public function getTitle(): string
    {
        return 'Promotional Top Bar Settings';
    }

    public function mount(): void
    {
        $fields = [
            'promo_bar.enabled',
            'promo_bar.mode',           // marquee | static
            'promo_bar.style',          // filled | outline | gradient
            'promo_bar.bg_color',
            'promo_bar.text_color',
            'promo_bar.gradient_from',
            'promo_bar.gradient_to',
            'promo_bar.message_en',
            'promo_bar.message_ar',
            'promo_bar.link_url',
            'promo_bar.link_target',    // _self | _blank
            'promo_bar.icon',           // emoji or heroicon name
            'promo_bar.marquee_speed',  // slow | medium | fast
            'promo_bar.items_en',       // JSON array of messages (for multi-message marquee)
            'promo_bar.items_ar',
            'promo_bar.font_size',      // sm | md | lg
            'promo_bar.font_weight',    // normal | semibold | bold
            'promo_bar.bar_height',     // compact | normal | tall
            'promo_bar.dismissible',    // bool - can user close it?
            'promo_bar.dismiss_hours',  // how many hours before it shows again
            'promo_bar.show_countdown', // bool - show countdown timer
            'promo_bar.countdown_end',  // datetime
            'promo_bar.starts_at',
            'promo_bar.ends_at',
            'promo_bar.show_on_mobile',
        ];

        $this->data = [];
        foreach ($fields as $field) {
            $key = str_replace('promo_bar.', '', $field);
            $this->data[$key] = Setting::get($field);
        }

        // Set defaults for unpopulated fields
        $this->data['enabled']       = $this->data['enabled']       ?? false;
        $this->data['mode']          = $this->data['mode']          ?? 'marquee';
        $this->data['style']         = $this->data['style']         ?? 'filled';
        $this->data['bg_color']      = $this->data['bg_color']      ?? '#cc0000';
        $this->data['text_color']    = $this->data['text_color']    ?? '#ffffff';
        $this->data['gradient_from'] = $this->data['gradient_from'] ?? '#cc0000';
        $this->data['gradient_to']   = $this->data['gradient_to']   ?? '#ff6600';
        $this->data['marquee_speed'] = $this->data['marquee_speed'] ?? 'medium';
        $this->data['link_target']   = $this->data['link_target']   ?? '_self';
        $this->data['font_size']     = $this->data['font_size']     ?? 'sm';
        $this->data['font_weight']   = $this->data['font_weight']   ?? 'semibold';
        $this->data['bar_height']    = $this->data['bar_height']    ?? 'normal';
        $this->data['dismissible']   = $this->data['dismissible']   ?? true;
        $this->data['dismiss_hours'] = $this->data['dismiss_hours'] ?? 24;
        $this->data['show_countdown']= $this->data['show_countdown']?? false;
        $this->data['show_on_mobile']= $this->data['show_on_mobile']?? true;

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // ── ENABLE / LIVE PREVIEW ──
                Components\Section::make('Status')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('enabled')
                                    ->label('Enable Promotional Bar')
                                    ->helperText('Toggle the bar on/off globally')
                                    ->default(false)
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('show_on_mobile')
                                    ->label('Show on Mobile')
                                    ->helperText('Hide on small screens if too distracting')
                                    ->default(true)
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('dismissible')
                                    ->label('User Can Dismiss')
                                    ->helperText('Show × close button')
                                    ->default(true)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\TextInput::make('dismiss_hours')
                            ->label('Hours Before Re-showing After Dismiss')
                            ->numeric()
                            ->default(24)
                            ->minValue(0)
                            ->maxValue(720)
                            ->helperText('0 = always show again on page reload'),
                    ]),

                // ── CONTENT ──
                Components\Section::make('Content')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('message_en')
                                    ->label('Message (English)')
                                    ->rows(2)
                                    ->helperText('Main promo message — used in static & marquee mode'),

                                Forms\Components\Textarea::make('message_ar')
                                    ->label('Message (Arabic / RTL)')
                                    ->rows(2)
                                    ->extraInputAttributes(['dir' => 'rtl'])
                                    ->helperText('Arabic version'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('items_en')
                                    ->label('Multiple Messages EN (JSON array)')
                                    ->rows(4)
                                    ->helperText('Optional: ["Free shipping on orders over 200 SAR 🚚", "Flash sale — 30% off today!"]  — leave empty to use single message above'),

                                Forms\Components\Textarea::make('items_ar')
                                    ->label('Multiple Messages AR (JSON array)')
                                    ->rows(4)
                                    ->extraInputAttributes(['dir' => 'rtl'])
                                    ->helperText('Arabic versions in same order'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('icon')
                                    ->label('Icon / Emoji')
                                    ->placeholder('🔥 or leave empty')
                                    ->maxLength(10)
                                    ->helperText('Displayed before every message'),

                                Forms\Components\TextInput::make('link_url')
                                    ->label('CTA Link URL')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->helperText('Wrap the entire bar in a link'),

                                Forms\Components\Select::make('link_target')
                                    ->label('Link Target')
                                    ->options([
                                        '_self'  => 'Same tab',
                                        '_blank' => 'New tab',
                                    ])
                                    ->default('_self'),
                            ]),
                    ]),

                // ── DISPLAY MODE ──
                Components\Section::make('Display Mode')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('mode')
                                    ->label('Scroll Mode')
                                    ->options([
                                        'marquee' => 'Marquee (scrolling ticker)',
                                        'static'  => 'Static (centered text)',
                                        'rotate'  => 'Rotate (cycle messages with fade)',
                                    ])
                                    ->default('marquee')
                                    ->live(),

                                Forms\Components\Select::make('marquee_speed')
                                    ->label('Marquee Speed')
                                    ->options([
                                        'slowest' => 'Slowest (120s)',
                                        'slow'   => 'Slow (60s)',
                                        'medium' => 'Medium (40s)',
                                        'fast'   => 'Fast (25s)',
                                        'fastest' => 'Fastest (10s)',
                                        'top' => 'Top (5s)',
                                        'superfast' => 'Superfast (2s)',
                                    ])
                                    ->default('medium')
                                    ->visible(fn ($get) => $get('mode') === 'marquee'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('bar_height')
                                    ->label('Bar Height')
                                    ->options([
                                        'compact' => 'Compact (28px)',
                                        'normal'  => 'Normal (36px)',
                                        'tall'    => 'Tall (44px)',
                                    ])
                                    ->default('normal'),

                                Forms\Components\Select::make('font_size')
                                    ->label('Font Size')
                                    ->options([
                                        'xs' => 'Extra Small (11px)',
                                        'sm' => 'Small (13px)',
                                        'md' => 'Medium (14px)',
                                        'lg' => 'Large (16px)',
                                    ])
                                    ->default('sm'),

                                Forms\Components\Select::make('font_weight')
                                    ->label('Font Weight')
                                    ->options([
                                        'normal'   => 'Normal (400)',
                                        'medium'   => 'Medium (500)',
                                        'semibold' => 'Semibold (600)',
                                        'bold'     => 'Bold (700)',
                                    ])
                                    ->default('semibold'),
                            ]),
                    ]),

                // ── STYLING ──
                Components\Section::make('Colors & Style')
                    ->schema([
                        Forms\Components\Select::make('style')
                            ->label('Color Style')
                            ->options([
                                'filled'   => 'Solid Fill',
                                'gradient' => 'Gradient',
                                'outline'  => 'Outline (transparent bg, colored border)',
                            ])
                            ->default('filled')
                            ->live(),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('bg_color')
                                    ->label('Background Color')
                                    ->default('#ff0000')
                                    ->visible(fn ($get) => in_array($get('style'), ['filled', 'outline'])),

                                Forms\Components\ColorPicker::make('text_color')
                                    ->label('Text Color')
                                    ->default('#ffffff'),
                            ]),

                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\ColorPicker::make('gradient_from')
                                    ->label('Gradient: From Color')
                                    ->default('#cc0000')
                                    ->visible(fn ($get) => $get('style') === 'gradient'),

                                Forms\Components\ColorPicker::make('gradient_to')
                                    ->label('Gradient: To Color')
                                    ->default('#ff6600')
                                    ->visible(fn ($get) => $get('style') === 'gradient'),
                            ]),
                    ]),

                // ── COUNTDOWN TIMER ──
                Components\Section::make('Countdown Timer')
                    ->description('Show a live countdown ticker inside the bar (e.g. for flash sales)')
                    ->schema([
                        Forms\Components\Toggle::make('show_countdown')
                            ->label('Enable Countdown Timer')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('countdown_end')
                            ->label('Countdown Target Date & Time')
                            ->native(false)
                            ->helperText('Bar auto-hides once this date passes'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // ── SCHEDULE ──
                Components\Section::make('Schedule')
                    ->description('Optionally limit when the bar is visible')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Start Date/Time')
                                    ->native(false)
                                    ->placeholder('Immediately'),

                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('End Date/Time')
                                    ->native(false)
                                    ->placeholder('No end')
                                    ->after('starts_at'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set("promo_bar.{$key}", $value);
        }

        Notification::make()
            ->title('Promo Bar Settings Saved')
            ->body('The promotional bar has been updated. Changes are live immediately.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Promo Bar Settings')
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->guard('admin')->user();
        return $user && ($user->hasRole('Super Admin') || $user->can('settings.view'));
    }
}
