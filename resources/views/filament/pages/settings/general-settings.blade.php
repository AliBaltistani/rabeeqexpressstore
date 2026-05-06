@extends('filament-panels::page')

@section('content')
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="fi-form-actions">
            <x-filament::button type="submit" color="primary">
                💾 Save Settings
            </x-filament::button>
        </div>
    </x-filament-panels::form>
@endsection
