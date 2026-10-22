@props(['pose' => 'wave'])
{{--
    "Bean" — the Roast & Notes mascot, the project owner's own artwork (originals in docs/design/mascot/).
    Each pose is cut from the owner's sticker sheet / illustration with the blue background keyed out
    (public/images/mascot/<pose>.png), so it sits on both the cobalt and the cream surfaces.
--}}
@php
    $poses = [
        'wave', 'pour', 'sip', 'carry', 'delivery', 'peek', 'nap', 'bag-up', 'bath',
        'bag', 'stir', 'search', 'takeaway', 'sleep', 'cheer', 'wordmark',
    ];
    $pose = in_array($pose, $poses, true) ? $pose : 'wave';
@endphp
<img src="{{ asset('images/mascot/'.$pose.'.png') }}" alt="" loading="lazy" decoding="async" draggable="false"
     {{ $attributes->merge(['class' => 'object-contain select-none']) }} data-mascot="{{ $pose }}">
