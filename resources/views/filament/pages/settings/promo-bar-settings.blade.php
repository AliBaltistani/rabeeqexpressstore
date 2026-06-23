<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6" style="text-align: right;">
            <x-filament::button type="submit">
                Save Promo Bar Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
