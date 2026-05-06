<div>
    @forelse($addresses as $address)
        <div class="py-3 border-b border-gray-100 dark:border-gray-800 last:border-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ $address->label ?? 'Address' }}
                </span>
                @if($address->is_default)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                        Default
                    </span>
                @endif
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-0.5">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $address->first_name }} {{ $address->last_name }}</div>
                <div>{{ $address->address_line_1 }}</div>
                @if($address->address_line_2)
                    <div>{{ $address->address_line_2 }}</div>
                @endif
                <div>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</div>
                <div>{{ $address->country }}</div>
                @if($address->phone)
                    <div class="flex items-center gap-1 mt-1">
                        <x-heroicon-o-phone class="w-3.5 h-3.5" />
                        {{ $address->phone }}
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400 italic py-2">No saved addresses.</p>
    @endforelse
</div>
