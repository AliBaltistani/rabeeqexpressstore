<x-mail::message>
# Low Stock Alert

The product **{{ $product->getTranslation('name', 'en') }}** is running low on stock.

**Current Stock:** {{ $product->stock_quantity }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>