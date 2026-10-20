@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-cobalt']) }}>
    {{ $value ?? $slot }}
</label>
