@php
    use App\Support\Money;

    $badgeItems = [];
    $compare = (float) ($product->compare_at_price ?? 0);
    $price = (float) $product->price;
    $showDiscount = ($store['product_show_discount_badge'] ?? '1') === '1';

    if ($showDiscount && $compare > $price && $compare > 0) {
        $percent = (int) round((($compare - $price) / $compare) * 100);
        if ($percent > 0) {
            $template = trim((string) ($store['product_discount_badge_text'] ?? '{discount}% ছাড়!'));
            if ($template === '') {
                $template = '{discount}% ছাড়!';
            }
            $bg = (string) ($store['product_discount_badge_bg'] ?? '#A5000B');
            if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $bg)) {
                $bg = '#A5000B';
            }
            $badgeItems[] = [
                'text' => str_replace('{discount}', Money::toBengali($percent), $template),
                'tone' => 'discount',
                'bg' => $bg,
            ];
        }
    }

    if ($product->is_flash) {
        $badgeItems[] = ['text' => 'ফ্ল্যাশ সেল', 'tone' => 'flash'];
    } elseif ($product->is_hot) {
        $badgeItems[] = ['text' => 'হট ডিল', 'tone' => 'hot'];
    } elseif ($product->is_featured) {
        $badgeItems[] = ['text' => 'ফিচার্ড', 'tone' => 'featured'];
    }
@endphp

@if ($badgeItems !== [])
    <span class="product-badges">
        @foreach ($badgeItems as $item)
            <span @class(['product-badge', 'product-badge--'.$item['tone']]) @if (($item['tone'] ?? '') === 'discount') style="background: {{ $item['bg'] }}; color: #fff;" @endif>{{ $item['text'] }}</span>
        @endforeach
    </span>
@endif
