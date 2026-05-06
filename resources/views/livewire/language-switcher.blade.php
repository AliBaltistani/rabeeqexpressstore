<div class="flex items-center">
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <button type="button"
                class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-language class="h-5 w-5" />
                <span class="uppercase font-bold">{{ $currentLocale }}</span>
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($languages as $language)
                <x-filament::dropdown.list.item
                    wire:click="switchLanguage('{{ $language->code }}')"
                    :icon="$currentLocale === $language->code ? 'heroicon-o-check-circle' : 'heroicon-o-globe-alt'"
                    :color="$currentLocale === $language->code ? 'primary' : 'gray'"
                >
                    <span class="flex items-center gap-x-2">
                        <span>{{ $language->name }}</span>
                        @if ($language->is_default)
                            <span class="text-xs text-gray-400">({{ __('admin.common.active') }})</span>
                        @endif
                        @if ($currentLocale === $language->code)
                            <span class="ml-auto text-xs font-bold text-primary-600">✓</span>
                        @endif
                    </span>
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
