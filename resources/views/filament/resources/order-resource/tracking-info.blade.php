<div>
    @if($tracking)
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @if($tracking->carrier)
                <div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Shipping Company</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #f3f4f6; margin-top: 0.125rem;">{{ $tracking->carrier }}</div>
                </div>
            @endif

            @if($tracking->tracking_number)
                <div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Tracking Number</div>
                    <div style="font-size: 0.875rem; font-family: monospace; font-weight: 500; color: #f3f4f6; margin-top: 0.125rem;">{{ $tracking->tracking_number }}</div>
                </div>
            @endif

            @if($tracking->estimated_delivery)
                <div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Estimated Delivery</div>
                    <div style="font-size: 0.875rem; color: #f3f4f6; margin-top: 0.125rem;">{{ $tracking->estimated_delivery->format('M d, Y') }}</div>
                </div>
            @endif

            @if($shippingStatus)
                <div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Shipping Status</div>
                    <div style="margin-top: 0.25rem;">
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;
                            @if($shippingStatus === 'delivered') background: #065f46; color: #6ee7b7;
                            @elseif($shippingStatus === 'in_transit') background: #1e3a5f; color: #93c5fd;
                            @elseif($shippingStatus === 'booked') background: #312e81; color: #a5b4fc;
                            @else background: #78350f; color: #fcd34d;
                            @endif
                        ">{{ ucfirst(str_replace('_', ' ', $shippingStatus)) }}</span>
                    </div>
                </div>
            @endif

            @if($tracking->tracking_url)
                <div style="margin-top: 0.25rem;">
                    <a href="{{ $tracking->tracking_url }}" target="_blank" rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; color: #60a5fa; text-decoration: none;">
                        <x-heroicon-o-arrow-top-right-on-square style="width: 1rem; height: 1rem;" />
                        Track Shipment
                    </a>
                </div>
            @endif
        </div>
    @else
        <p style="font-size: 0.875rem; color: #9ca3af; font-style: italic;">No tracking info yet. Use the "Tracking" button above to add.</p>
    @endif
</div>
