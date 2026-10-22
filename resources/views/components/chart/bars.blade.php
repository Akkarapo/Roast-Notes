@props(['items', 'label', 'prefix' => '', 'height' => 240])
{{--
    Vertical bars over time, drawn as SVG on the server (no JS).
    items: list of ['label' => 'ต.ค.', 'title' => 'ตุลาคม 2026', 'value' => float, 'display' => '฿12,340']
    Hover/focus a bar for its exact value (<title>); the table below the chart has every number.
--}}
@php
    $items = array_values($items);
    $n = max(1, count($items));
    $width = 1100; $left = 56; $right = 8; $top = 22; $bottom = 28;
    $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
    $max = max(array_column($items, 'value') ?: [0]);
    $ticks = \App\Support\Reports\Scale::ticks($max);
    $tickMax = end($ticks) ?: 1;
    $slot = $plotW / $n;
    $barW = min(24, max(3, $slot - 2));                 // thin marks, 2px air between neighbours
    $every = (int) ceil($n / 12);                       // at most ~12 x labels
    $maxIndex = $max > 0 ? array_search($max, array_column($items, 'value')) : null;
    $y = fn ($v) => $top + $plotH - ($v / $tickMax) * $plotH;
@endphp
<figure {{ $attributes->merge(['class' => 'rounded-card border-2 border-cobalt bg-paper p-4']) }}>
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full" role="img" aria-label="{{ $label }}" data-chart="bars">
        @foreach ($ticks as $tick)
            <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" stroke="#2338C4" stroke-opacity="{{ $tick == 0 ? 0.5 : 0.12 }}" stroke-width="1" />
            <text x="{{ $left - 8 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" font-size="11" fill="#2338C4" fill-opacity="0.7">{{ $prefix }}{{ \App\Support\Reports\Scale::compact($tick) }}</text>
        @endforeach

        @foreach ($items as $i => $item)
            @php
                $x = $left + $i * $slot + ($slot - $barW) / 2;
                $yTop = $y($item['value']);
                $base = $top + $plotH;
                $h = $base - $yTop;
                $r = min(4, $h, $barW / 2);
            @endphp
            <g class="transition-opacity hover:opacity-70 focus:opacity-70 focus:outline-none" tabindex="0">
                <title>{{ $item['title'] ?? $item['label'] }}: {{ $item['display'] }}</title>
                {{-- invisible full-height hit target, bigger than the mark --}}
                <rect x="{{ $left + $i * $slot }}" y="{{ $top }}" width="{{ $slot }}" height="{{ $plotH }}" fill="transparent" />
                @if ($h > 0)
                    <path d="M{{ $x }},{{ $base }} V{{ $yTop + $r }} Q{{ $x }},{{ $yTop }} {{ $x + $r }},{{ $yTop }} H{{ $x + $barW - $r }} Q{{ $x + $barW }},{{ $yTop }} {{ $x + $barW }},{{ $yTop + $r }} V{{ $base }} Z" fill="#2338C4" />
                @endif
                @if ($i === $maxIndex)
                    <text x="{{ $x + $barW / 2 }}" y="{{ $yTop - 6 }}" text-anchor="middle" font-size="11" font-weight="600" fill="#18248A">{{ $item['display'] }}</text>
                @endif
                @if ($i % $every === 0)
                    <text x="{{ $x + $barW / 2 }}" y="{{ $height - 8 }}" text-anchor="middle" font-size="11" fill="#2338C4" fill-opacity="0.8">{{ $item['label'] }}</text>
                @endif
            </g>
        @endforeach
    </svg>
    @isset($caption)
        <figcaption class="mt-2 text-xs text-cobalt/70">{{ $caption }}</figcaption>
    @endisset
</figure>
