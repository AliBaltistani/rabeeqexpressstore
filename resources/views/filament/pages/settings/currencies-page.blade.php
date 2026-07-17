<x-filament-panels::page>
    {{-- ══════════════════════════════════════════════════════════════
         Store Currency Panel
         This is the currency ALL product prices are stored in.
         It is NOT the same as the "Display Default" currency.
         Only change it if you manually re-price every product.
    ══════════════════════════════════════════════════════════════ --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6">
        <div class="fi-section-header flex items-center gap-x-3 px-6 py-4 border-b border-gray-200 dark:border-white/10">
            <x-filament::icon
                icon="heroicon-o-archive-box"
                class="h-5 w-5 text-gray-500 dark:text-gray-400"
            />
            <div>
                <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Store Base Currency
                </h3>
                <p class="fi-section-description text-sm text-gray-500 dark:text-gray-400">
                    The currency in which product prices are stored in the database. All conversions happen FROM this currency.
                </p>
            </div>
        </div>

        <div class="fi-section-content px-6 py-4">
            {{-- Warning Banner --}}
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                <div class="flex gap-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 flex-shrink-0 text-amber-500" />
                    <div class="text-sm text-amber-800 dark:text-amber-300">
                        <strong>Important:</strong>
                        Changing the Store Base Currency does <strong>not</strong> convert your existing product prices.
                        You must manually update all product prices if you change this setting.
                        The "Display Default" currency in the table below is separate — it only controls what new visitors see.
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-48">
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Current Store Currency
                    </label>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1.5 text-sm font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                            <x-filament::icon icon="heroicon-o-currency-dollar" class="h-4 w-4" />
                            {{ store_currency_code() }}
                        </span>

                        @php
                            $storeCurr = \App\Models\Currency::where('code', store_currency_code())->first();
                        @endphp
                        @if($storeCurr)
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                — {{ $storeCurr->name }} ({{ $storeCurr->symbol }}) · Exchange rate base = 1.000000
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex-1 min-w-48">
                    <label for="storeCurrencySelect" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Change Store Currency
                    </label>
                    <form wire:submit.prevent="saveStoreCurrency" class="flex items-center gap-2">
                        <select
                            id="storeCurrencySelect"
                            wire:model="storeCurrencyCode"
                            class="fi-select-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        >
                            @foreach(\App\Models\Currency::where('is_active', true)->orderBy('code')->get() as $cur)
                                <option value="{{ $cur->code }}" @selected($cur->code === store_currency_code())>
                                    {{ $cur->code }} — {{ $cur->name }}
                                </option>
                            @endforeach
                        </select>
                        <button
                            type="submit"
                            onclick="return confirm('Are you sure? Changing the Store Base Currency requires you to manually update ALL product prices. This action cannot be undone automatically.')"
                            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center gap-1.5 font-semibold outline-none transition duration-75 rounded-lg bg-amber-500 px-4 py-2 text-sm text-white shadow-sm hover:bg-amber-600 focus:ring-2 focus:ring-amber-500 whitespace-nowrap"
                        >
                            Save Base Currency
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         Currencies Table — display defaults & exchange rates
    ══════════════════════════════════════════════════════════════ --}}
    <div>
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
            <strong>Display Default</strong> controls which currency new visitors see.
            Exchange rates are relative to <strong>{{ store_currency_code() }}</strong> (your store base).
            Use "Set as Display Default" on any row to change what visitors see — this does not affect price storage.
        </p>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
