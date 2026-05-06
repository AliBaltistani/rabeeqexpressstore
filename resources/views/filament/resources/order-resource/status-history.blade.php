<div>
    @forelse($histories as $history)
        <div class="flex gap-4 mb-4 last:mb-0">
            {{-- Timeline dot --}}
            <div class="flex flex-col items-center">
                <div class="w-3 h-3 rounded-full mt-1.5
                    @switch($history->status)
                        @case('pending') bg-yellow-500 @break
                        @case('processing') bg-blue-500 @break
                        @case('shipped') bg-cyan-500 @break
                        @case('delivered') bg-green-500 @break
                        @case('cancelled') bg-red-500 @break
                        @case('refunded') bg-gray-500 @break
                        @default bg-gray-400
                    @endswitch
                "></div>
                @if(!$loop->last)
                    <div class="w-0.5 flex-1 bg-gray-200 dark:bg-gray-700 mt-1"></div>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 pb-4">
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @switch($history->status)
                            @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                            @case('processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                            @case('shipped') bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 @break
                            @case('delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                            @case('cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                            @case('refunded') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch
                    ">
                        {{ ucfirst($history->status) }}
                    </span>

                    @if($history->is_customer_notified)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                            ✓ Notified
                        </span>
                    @endif
                </div>

                @if($history->comment)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $history->comment }}</p>
                @endif

                <div class="flex items-center gap-2 mt-1 text-xs text-gray-400 dark:text-gray-500">
                    <span>{{ $history->created_at->format('M d, Y H:i') }}</span>
                    @if($history->admin)
                        <span>• by {{ $history->admin->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No status history recorded.</p>
    @endforelse
</div>
