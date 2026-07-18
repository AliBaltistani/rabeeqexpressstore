<x-filament-panels::page>

    {{-- ── Store Base Currency Card ── --}}
    <x-filament::section>
        <x-slot name="heading">
            Store Base Currency
        </x-slot>
        <x-slot name="description">
            The currency product prices are <strong>stored in</strong> the database.
            All price conversions happen <strong>FROM</strong> this currency.
            This is separate from the "Display Default" in the table below.
        </x-slot>

        {{-- Warning --}}
        <x-filament::badge color="warning" class="mb-4 w-full justify-start text-left text-wrap py-2 px-3 text-sm font-normal">
            ⚠ Changing the Store Base Currency does <strong>not</strong> automatically convert existing product prices.
            You must manually re-enter all product prices if you change this setting.
        </x-filament::badge>

        <div class="flex flex-wrap items-end gap-4 mt-3">
            {{-- Current value display --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Current Store Currency</p>
                <div class="flex items-center gap-2">
                    <x-filament::badge color="primary" size="lg">
                        {{ store_currency_code() }}
                    </x-filament::badge>
                    @php $storeCurr = \App\Models\Currency::where('code', store_currency_code())->first(); @endphp
                    @if($storeCurr)
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $storeCurr->name }} ({{ $storeCurr->symbol }}) &mdash; exchange rate base = 1.000000
                        </span>
                    @endif
                </div>
            </div>

            {{-- Change form --}}
            <div class="flex-1 min-w-48">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Change Store Currency</p>
                <form wire:submit.prevent="saveStoreCurrency" class="flex items-center gap-2">
                    <select
                        wire:model="storeCurrencyCode"
                        class="block flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    >
                        @foreach(\App\Models\Currency::where('is_active', true)->orderBy('code')->get() as $cur)
                            <option value="{{ $cur->code }}" @selected($cur->code === store_currency_code())>
                                {{ $cur->code }} &mdash; {{ $cur->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-filament::button
                        type="submit"
                        color="warning"
                        onclick="return confirm('Are you sure? You must manually update ALL product prices after changing the store base currency.')"
                    >
                        Save
                    </x-filament::button>
                </form>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Currencies Table ── --}}
    <x-filament::section class="mt-2">
        <x-slot name="heading">Active Currencies</x-slot>
        <x-slot name="description">
            Exchange rates are relative to <strong>{{ store_currency_code() }}</strong>.
            Use <em>Set as Display Default</em> to change which currency new visitors see — this never affects price storage.
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

</x-filament-panels::page>
