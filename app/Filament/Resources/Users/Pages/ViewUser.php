<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('send_email')
                ->label('Send Email')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    \Filament\Forms\Components\TextInput::make('subject')->required(),
                    \Filament\Forms\Components\Textarea::make('message')->required(),
                ])
                ->action(function (array $data, \App\Models\User $record) {
                    \Illuminate\Support\Facades\Mail::raw($data['message'], function ($message) use ($record, $data) {
                        $message->to($record->email)
                            ->subject($data['subject']);
                    });
                    \Filament\Notifications\Notification::make()->title('Email Sent!')->success()->send();
                }),
            \Filament\Actions\Action::make('ban')
                ->label('Ban Customer')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn(\App\Models\User $record) => $record->is_active && !$record->is_banned)
                ->action(function (\App\Models\User $record) {
                    $record->update(['is_banned' => true, 'is_active' => false]);
                }),
            \Filament\Actions\Action::make('unban')
                ->label('Unban Customer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn(\App\Models\User $record) => $record->is_banned)
                ->action(function (\App\Models\User $record) {
                    $record->update(['is_banned' => false, 'is_active' => true]);
                }),
            EditAction::make(),
        ];
    }
}
