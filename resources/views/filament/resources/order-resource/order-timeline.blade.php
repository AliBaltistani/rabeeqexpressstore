<div class="order-timeline">
    @if($histories->isEmpty())
        <p style="color: #6b7280; padding: 1rem 0;">No status changes recorded yet.</p>
    @else
        <div style="position: relative; padding-left: 2rem;">
            {{-- Timeline vertical line --}}
            <div style="position: absolute; left: 0.5rem; top: 0.5rem; bottom: 0.5rem; width: 2px; background: #e5e7eb;"></div>

            @foreach($histories as $history)
                @php
                    $statusColors = [
                        'pending'    => '#f59e0b',
                        'processing' => '#6366f1',
                        'shipped'    => '#3b82f6',
                        'delivered'  => '#10b981',
                        'cancelled'  => '#ef4444',
                        'refunded'   => '#6b7280',
                    ];
                    $color = $statusColors[$history->status_to ?? $history->status] ?? '#9ca3af';
                    $isLatest = $loop->last;
                @endphp

                <div style="position: relative; padding-bottom: 1.5rem; {{ $isLatest ? 'padding-bottom: 0;' : '' }}">
                    {{-- Timeline dot --}}
                    <div style="
                        position: absolute;
                        left: -1.65rem;
                        top: 0.25rem;
                        width: 14px;
                        height: 14px;
                        border-radius: 50%;
                        background: {{ $color }};
                        border: 3px solid white;
                        box-shadow: 0 0 0 2px {{ $color }}33;
                        {{ $isLatest ? 'animation: pulse 2s infinite;' : '' }}
                    "></div>

                    <div style="background: {{ $isLatest ? $color . '0d' : 'transparent' }}; border-radius: 8px; padding: 0.75rem 1rem; {{ $isLatest ? 'border: 1px solid ' . $color . '22;' : '' }}">
                        {{-- Status badge --}}
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="
                                display: inline-block;
                                padding: 0.2rem 0.6rem;
                                border-radius: 9999px;
                                font-size: 0.75rem;
                                font-weight: 600;
                                text-transform: uppercase;
                                letter-spacing: 0.05em;
                                background: {{ $color }}1a;
                                color: {{ $color }};
                            ">{{ $history->status_to ?? $history->status }}</span>

                            @if($history->status_from)
                                <span style="font-size: 0.75rem; color: #9ca3af;">from</span>
                                <span style="
                                    display: inline-block;
                                    padding: 0.15rem 0.5rem;
                                    border-radius: 9999px;
                                    font-size: 0.7rem;
                                    background: #f3f4f6;
                                    color: #6b7280;
                                ">{{ $history->status_from }}</span>
                            @endif
                        </div>

                        {{-- Comment --}}
                        @if($history->comment)
                            <p style="margin: 0.5rem 0 0; font-size: 0.875rem; color: #4b5563; line-height: 1.5;">
                                {{ $history->comment }}
                            </p>
                        @endif

                        {{-- Meta row --}}
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af; flex-wrap: wrap;">
                            <span title="{{ $history->created_at?->format('Y-m-d H:i:s') }}">
                                🕐 {{ $history->created_at?->diffForHumans() }}
                            </span>
                            @if($history->changedByAdmin)
                                <span>👤 {{ $history->changedByAdmin->name }}</span>
                            @elseif($history->admin)
                                <span>👤 {{ $history->admin->name }}</span>
                            @else
                                <span>⚙️ System</span>
                            @endif
                            @if($history->is_customer_notified)
                                <span>📧 Customer notified</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15); }
        50% { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0.08); }
    }
</style>
