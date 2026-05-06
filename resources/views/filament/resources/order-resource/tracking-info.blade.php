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
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No tracking info yet. Use the "Tracking" button above to add.</p>
    @endif
</div>
