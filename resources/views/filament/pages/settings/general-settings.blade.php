<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6" style="{{ session('is_rtl') ? 'text-align: left;' : 'text-align: right;' }}">
            <x-filament::button type="submit">
                {{ __('admin.common.save_settings') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
