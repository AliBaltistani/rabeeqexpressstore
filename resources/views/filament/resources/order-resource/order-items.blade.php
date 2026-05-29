<div>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="text-left py-3 px-2 font-medium text-gray-500 dark:text-gray-400">SKU</th>
                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Price</th>
                <th class="text-center py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Qty</th>
                <th class="text-right py-3 px-2 font-medium text-gray-500 dark:text-gray-400">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($record->items as $item)
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="py-3 px-2">
                        <div class="flex items-center gap-3">
                            @if($item->product_image)
                                <img src="{{ asset('storage/' . $item->product_image) }}" alt="" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" style="width: 3rem; height: 3rem; min-width: 3rem;">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <x-heroicon-o-cube class="w-5 h-5 text-gray-400" />
                                </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $item->product_name }}</div>
                                @if($item->variant)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Variant: {{ $item->variant->sku }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-2 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $item->product_sku }}</td>
                    <td class="py-3 px-2 text-right text-gray-900 dark:text-gray-100">
                        {{ number_format($item->unit_price, 2) }}
                        @if($item->compare_price && $item->compare_price > $item->unit_price)
                            <br><span class="line-through text-gray-400 text-xs">{{ number_format($item->compare_price, 2) }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-2 text-center text-gray-900 dark:text-gray-100">{{ $item->quantity }}</td>
                    <td class="py-3 px-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">No items in this order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="mt-4 flex justify-end">
        <div class="w-72">
            <div class="flex justify-between py-2 text-sm text-gray-600 dark:text-gray-400">
                <span>Subtotal</span>
                <span>{{ number_format($record->subtotal, 2) }} {{ $record->currency_code }}</span>
            </div>
            @if($record->discount_amount > 0)
                <div class="flex justify-between py-2 text-sm text-red-600 dark:text-red-400">
                    <span>Discount{{ $record->coupon_code ? ' (' . $record->coupon_code . ')' : '' }}</span>
                    <span>-{{ number_format($record->discount_amount, 2) }} {{ $record->currency_code }}</span>
                </div>
            @endif
            <div class="flex justify-between py-2 text-sm text-gray-600 dark:text-gray-400">
                <span>Shipping</span>
                <span>{{ number_format($record->shipping_amount, 2) }} {{ $record->currency_code }}</span>
            </div>
            @if($record->tax_amount > 0)
                <div class="flex justify-between py-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Tax</span>
                    <span>{{ number_format($record->tax_amount, 2) }} {{ $record->currency_code }}</span>
                </div>
            @endif
            <div class="flex justify-between py-3 text-base font-bold text-gray-900 dark:text-gray-100 border-t-2 border-gray-300 dark:border-gray-600 mt-1">
                <span>Grand Total</span>
                <span>{{ number_format($record->total, 2) }} {{ $record->currency_code }}</span>
            </div>
        </div>
    </div>
</div>
