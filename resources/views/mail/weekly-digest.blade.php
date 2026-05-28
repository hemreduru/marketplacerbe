<x-mail::message>
# {{ __('mail.weekly_digest_heading') }}

{{ __('mail.greeting', ['name' => $user->name]) }}

---

## 📊 {{ __('mail.weekly_summary') }}

| {{ __('mail.metric') }} | {{ __('mail.value') }} |
|---|---|
| {{ __('mail.net_profit') }} | **{{ $netProfit }} TL** |
| {{ __('mail.revenue') }} | {{ $revenue }} TL |
| {{ __('mail.order_count') }} | {{ $orderCount }} |
| {{ __('mail.margin') }} | %{{ $margin }} |
| {{ __('mail.return_rate') }} | %{{ $returnRate }} |

@if(count($topSkus) > 0)
## ⭐ {{ __('mail.top_products') }}
@foreach($topSkus as $sku)
- {{ $sku['title'] ?? $sku['sku'] }} — {{ $sku['profit'] ?? '0' }} TL
@endforeach
@endif

@if(count($worstSkus) > 0)
## ⚠️ {{ __('mail.attention_needed') }}
@foreach($worstSkus as $sku)
- {{ $sku['title'] ?? $sku['sku'] }} — {{ $sku['profit'] ?? '0' }} TL
@endforeach
@endif

---

[{{ __('mail.view_dashboard') }}]({{ route('dashboard') }})

{{ __('mail.regards') }},<br>
**Cirotik** ✓
</x-mail::message>
