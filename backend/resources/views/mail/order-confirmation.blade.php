{{-- Order confirmation email (see App\Mail\OrderConfirmation). Laravel's markdown mail components. --}}
<x-mail::message>
# Thanks for your order!

Order #{{ $order->id }}, placed {{ $order->created_at->format('j F Y') }}.

@foreach ($order->items as $item)
**{{ $item->product_name }}** ({{ $item->quantity }} × €{{ number_format($item->price_cents / 100, 2) }})

@foreach ($item->keys as $key)
- `{{ $key->code }}`
@endforeach

@endforeach
@if ($order->discount_cents > 0)
Discount ({{ $order->coupon_code }}): -€{{ number_format($order->discount_cents / 100, 2) }}
@endif

**Total: €{{ number_format($order->total_cents / 100, 2) }}**

<x-mail::button :url="$orderUrl">
View your order
</x-mail::button>

This is a demo store: no payment was taken and these keys are not real.

{{ config('app.name') }}
</x-mail::message>
