<div>
    @if($tracking)
        <div class="space-y-3">
            @if($tracking->tracking_number)
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Tracking Number</div>
                    <div class="text-sm font-mono font-medium text-gray-900 dark:text-gray-100 mt-0.5">{{ $tracking->tracking_number }}</div>
                </div>
            @endif

            @if($tracking->carrier)
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Carrier</div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-0.5">{{ $tracking->carrier }}</div>
                </div>
            @endif

            @if($tracking->tracking_url)
                <div>
                    <a href="{{ $tracking->tracking_url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                        Track Shipment
                    </a>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No tracking information available.</p>
    @endif

    {{-- Tracking Form --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <form wire:submit.prevent="$parent.saveTracking">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tracking Number</label>
                    <input
                        type="text"
                        wire:model="trackingNumber"
                        value="{{ $tracking?->tracking_number }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                        placeholder="Enter tracking number"
                    >
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Carrier</label>
                    <select
                        wire:model="trackingCarrier"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                    >
                        <option value="">Select Carrier</option>
                        <option value="DHL" @selected($tracking?->carrier === 'DHL')>DHL</option>
                        <option value="Aramex" @selected($tracking?->carrier === 'Aramex')>Aramex</option>
                        <option value="SMSA" @selected($tracking?->carrier === 'SMSA')>SMSA</option>
                        <option value="USPS" @selected($tracking?->carrier === 'USPS')>USPS</option>
                        <option value="FedEx" @selected($tracking?->carrier === 'FedEx')>FedEx</option>
                        <option value="UPS" @selected($tracking?->carrier === 'UPS')>UPS</option>
                        <option value="Other" @selected($tracking?->carrier === 'Other')>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tracking URL</label>
                    <input
                        type="url"
                        wire:model="trackingUrl"
                        value="{{ $tracking?->tracking_url }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                        placeholder="https://..."
                    >
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-600 text-white font-medium text-sm hover:bg-gray-700 transition-colors"
                >
                    <x-heroicon-o-truck class="w-4 h-4" />
                    Save Tracking
                </button>
            </div>
        </form>
    </div>
</div>
