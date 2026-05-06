<div>
    <form wire:submit.prevent="$parent.updateOrderStatus">
        <div class="space-y-4">
            <div>
                <label for="new_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order Status</label>
                <select
                    id="new_status"
                    wire:model="newStatus"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                >
                    <option value="pending" @selected($record->status === 'pending')>Pending</option>
                    <option value="processing" @selected($record->status === 'processing')>Processing</option>
                    <option value="shipped" @selected($record->status === 'shipped')>Shipped</option>
                    <option value="delivered" @selected($record->status === 'delivered')>Delivered</option>
                    <option value="cancelled" @selected($record->status === 'cancelled')>Cancelled</option>
                    <option value="refunded" @selected($record->status === 'refunded')>Refunded</option>
                </select>
            </div>

            <div>
                <label for="status_comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment (optional)</label>
                <textarea
                    id="status_comment"
                    wire:model="statusComment"
                    rows="3"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm"
                    placeholder="Add a note about this status change..."
                ></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="notify_customer"
                    wire:model="notifyCustomer"
                    class="rounded border-gray-300 text-primary-600 shadow-sm dark:border-gray-600 dark:bg-gray-700"
                >
                <label for="notify_customer" class="text-sm text-gray-600 dark:text-gray-400">Notify Customer</label>
            </div>

            <button
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 transition-colors"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4" />
                Update Status
            </button>
        </div>
    </form>

    {{-- Current Status Badge --}}
    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="text-xs text-gray-500 dark:text-gray-400">Current Status</div>
        <span class="inline-flex items-center mt-1 px-2.5 py-1 rounded-full text-xs font-semibold
            @switch($record->status)
                @case('pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                @case('processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                @case('shipped') bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200 @break
                @case('delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                @case('cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                @case('refunded') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @break
            @endswitch
        ">
            {{ ucfirst($record->status) }}
        </span>
    </div>
</div>
