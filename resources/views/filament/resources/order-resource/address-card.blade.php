<div>
    @if($address)
        <div class="space-y-1 text-sm">
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $address->first_name }} {{ $address->last_name }}
            </div>
            <div class="text-gray-600 dark:text-gray-400">
                {{ $address->address_line_1 }}
            </div>
            @if($address->address_line_2)
                <div class="text-gray-600 dark:text-gray-400">
                    {{ $address->address_line_2 }}
                </div>
            @endif
            <div class="text-gray-600 dark:text-gray-400">
                {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
            </div>
            <div class="text-gray-600 dark:text-gray-400">
                {{ $address->country }}
            </div>
            @if($address->phone)
                <div class="text-gray-600 dark:text-gray-400 flex items-center gap-1 mt-2">
                    <x-heroicon-o-phone class="w-4 h-4" />
                    {{ $address->phone }}
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No {{ $type }} address provided.</p>
    @endif
</div>
