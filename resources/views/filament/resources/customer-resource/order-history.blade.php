<div>
    @forelse($orders as $order)
        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-800 last:border-0">
            <div class="flex items-center gap-4">
                <div>
                    <a href="{{ route('filament.admin.resources.orders.view', $order) }}"
                       class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        {{ $order->order_number }}
                    </a>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $order->created_at->format('M d, Y H:i') }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Status Badge --}}
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    @switch($order->status)
                        @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                        @case('processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                        @case('shipped') bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 @break
                        @case('delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                        @case('cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                        @case('refunded') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @break
                    @endswitch
                ">
                    {{ ucfirst($order->status) }}
                </span>

                {{-- Payment Badge --}}
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}
                ">
                    {{ ucfirst($order->payment_status) }}
                </span>

                {{-- Total --}}
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ number_format($order->total, 2) }} {{ $order->currency_code }}
                </span>
            </div>
        </div>
    @empty
        <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400 italic">
            No orders yet.
        </div>
    @endforelse
</div>
