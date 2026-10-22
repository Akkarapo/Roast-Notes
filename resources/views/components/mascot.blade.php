@props(['pose' => 'idle'])
{{--
    "Bean" — the Roast & Notes mascot (designed by the project owner, references in docs/design/mascot/).
    A cream coffee bean with a seam down the middle, two dot eyes, stubby arms and legs, dark outline.
    Poses: idle, wave, pour, sip, cheer, bag, search, sleep.
    Body colours are fixed; steam / sparkles / drips use currentColor (paper on cobalt, cobalt on cream).
--}}
@php
    $ink = '#1B1F3B';
    $cream = '#F3EEE3';
    $white = '#FBF8F1';
    $line = "stroke=\"{$ink}\" stroke-width=\"3.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"";
    $eyesClosed = in_array($pose, ['sip', 'sleep'], true);
    $mouth = match ($pose) {
        'wave', 'bag', 'search' => 'smile',
        'cheer' => 'open',
        default => null,
    };
@endphp
<svg viewBox="0 0 240 240" fill="none" {{ $attributes->merge(['aria-hidden' => 'true', 'class' => 'overflow-visible']) }} data-mascot="{{ $pose }}">
    {{-- decorations in the accent colour --}}
    <g stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        @if (in_array($pose, ['pour', 'sip', 'idle'], true))
            <path d="M{{ $pose === 'sip' ? 146 : ($pose === 'pour' ? 150 : 66) }} 76c-7-8 7-14 0-23m12 23c-7-8 7-14 0-23" />
        @endif
        @if (in_array($pose, ['wave', 'cheer', 'bag'], true))
            <path d="M206 30q2 10 12 12-10 2-12 12-2-10-12-12 10-2 12-12z" />
            <path d="M34 64q1.5 6 7 7.5-5.5 1.5-7 7.5-1.5-6-7-7.5 5.5-1.5 7-7.5z" />
        @endif
        @if ($pose === 'cheer')
            <path d="M208 92h12M214 86v12M24 120h10M29 115v10" />
        @endif
        @if ($pose === 'sleep')
            <path d="M176 46h12l-12 14h12M196 26h9l-9 10h9" />
        @endif
        @if ($pose === 'pour')
            <path d="M40 154v7M40 170v7M40 186v5" />
        @endif
        @if ($pose === 'search')
            <path d="M214 60l8-6M218 76h10M206 46l3-9" />
        @endif
    </g>

    {{-- legs behind the body --}}
    <path d="M88 184c-2 22 0 40 14 40 12 0 14-18 12-32z" fill="{{ $cream }}" {!! $line !!} />
    <path d="M128 192c-2 14 0 32 12 32 14 0 16-18 14-40z" fill="{{ $cream }}" {!! $line !!} />

    {{-- body: two lobes meeting at a dip on top, the seam runs down the middle --}}
    <path d="M120 32c-8-12-30-16-46-6-20 13-28 48-28 88 0 54 24 84 74 86 50-2 74-32 74-86 0-40-8-75-28-88-16-10-38-6-46 6z" fill="{{ $cream }}" {!! $line !!} />
    <path d="M120 32c2 44 10 96 4 160" {!! $line !!} />
    <path d="M176 84c5 18 6 38 2 56" stroke="{{ $ink }}" stroke-width="3" stroke-linecap="round" />

    {{-- face --}}
    @if ($eyesClosed)
        <path d="M88 101q5 5 10 0M108 101q5 5 10 0" {!! $line !!} />
    @else
        <circle cx="93" cy="100" r="4.8" fill="{{ $ink }}" />
        <circle cx="113" cy="100" r="4.8" fill="{{ $ink }}" />
    @endif
    @if ($mouth === 'smile')
        <path d="M96 113q7 7 14 0" {!! $line !!} />
    @elseif ($mouth === 'open')
        <path d="M94 111q9 16 18 0z" fill="{{ $ink }}" {!! $line !!} />
    @endif

    {{-- props held in front, then the arms over them --}}
    @switch($pose)
        @case('pour')
            <path d="M26 198h28l-3 17H29z" fill="{{ $white }}" {!! $line !!} />
            <path d="M54 202c9 0 9 10 0 10" {!! $line !!} />
            <path d="M80 166c-16 0-26-12-40-20" {!! $line !!} />
            <path d="M76 138h44l-5 38H81z" fill="{{ $white }}" {!! $line !!} />
            <path d="M84 138q14-12 28 0" fill="{{ $white }}" {!! $line !!} />
            <circle cx="98" cy="127" r="3" fill="{{ $ink }}" />
            <path d="M120 144c15 0 15 24-3 24" {!! $line !!} />
            @break
        @case('sip')
            <path d="M126 100h28l-3 22h-22z" fill="{{ $white }}" {!! $line !!} />
            <path d="M154 104c9 0 9 11 0 11" {!! $line !!} />
            @break
        @case('bag')
            <path d="M90 118h60l5 64H85z" fill="{{ $white }}" {!! $line !!} />
            <path d="M90 130h60" {!! $line !!} />
            <ellipse cx="120" cy="158" rx="8" ry="11" transform="rotate(20 120 158)" stroke="{{ $ink }}" stroke-width="3" />
            <path d="M117 148c4 6-1 14 3 20" stroke="{{ $ink }}" stroke-width="2.5" stroke-linecap="round" />
            @break
    @endswitch

    @switch($pose)
        @case('wave')
            <path d="M52 116c-18 6-22 32-6 38 12 4 18-8 16-20z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M180 104c16-14 24-38 14-46-10-6-20 8-24 28z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @case('cheer')
            <path d="M60 104c-16-14-24-38-14-46 10-6 20 8 24 28z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M180 104c16-14 24-38 14-46-10-6-20 8-24 28z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @case('pour')
            <path d="M50 120c-6 18 6 40 28 44 6 1 8-10 2-13-14-3-18-12-16-24z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M188 118c-6 22-30 42-60 40-6-1-6-12 0-13 24 2 40-10 46-26z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @case('bag')
            <path d="M54 112c8 24 26 36 44 34 6-1 6-12 0-13-14 1-26-7-30-23z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M186 112c-8 24-26 36-44 34-6-1-6-12 0-13 14 1 26-7 30-23z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @case('sip')
            <path d="M52 116c-18 6-22 32-6 38 12 4 18-8 16-20z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M182 114c-6 14-20 20-32 16-6-2-4-12 2-12 10 1 18-4 22-12z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @case('search')
            <path d="M52 116c-18 6-22 32-6 38 12 4 18-8 16-20z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M190 108l16 16" stroke="{{ $ink }}" stroke-width="7" stroke-linecap="round" />
            <circle cx="178" cy="96" r="17" fill="{{ $white }}" {!! $line !!} />
            <path d="M170 90q4-6 10-6" stroke="{{ $ink }}" stroke-width="2.5" stroke-linecap="round" />
            <path d="M182 120c12-2 18-12 12-18-6-4-14 2-18 8z" fill="{{ $cream }}" {!! $line !!} />
            @break
        @default
            <path d="M52 116c-18 6-22 32-6 38 12 4 18-8 16-20z" fill="{{ $cream }}" {!! $line !!} />
            <path d="M188 116c18 6 22 32 6 38-12 4-18-8-16-20z" fill="{{ $cream }}" {!! $line !!} />
    @endswitch
</svg>
