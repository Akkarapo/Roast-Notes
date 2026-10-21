@props(['name' => 'quantity', 'value' => 1, 'max' => 20, 'autosubmit' => false])

{{-- Works without JS (plain number input); Alpine adds −/+ buttons. --}}
<div x-data="{ q: {{ (int) $value }}, max: {{ (int) $max }} }" class="inline-flex items-center rounded-full border-2 border-cobalt bg-paper">
    <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-lg font-semibold hover:bg-cream disabled:opacity-30"
            x-bind:disabled="q <= 1" @click="q--; {{ $autosubmit ? '$nextTick(() => $el.form.requestSubmit())' : '' }}" aria-label="{{ __('Decrease') }}">−</button>
    <input type="number" name="{{ $name }}" x-model.number="q" min="1" max="{{ (int) $max }}" value="{{ (int) $value }}"
           {{ $attributes->merge(['class' => 'w-12 border-0 bg-transparent p-0 text-center font-semibold text-cobalt focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none']) }}
           @if ($autosubmit) @change="$el.form.requestSubmit()" @endif aria-label="{{ __('Quantity') }}">
    <button type="button" class="grid h-9 w-9 place-items-center rounded-full text-lg font-semibold hover:bg-cream disabled:opacity-30"
            x-bind:disabled="q >= max" @click="q++; {{ $autosubmit ? '$nextTick(() => $el.form.requestSubmit())' : '' }}" aria-label="{{ __('Increase') }}">+</button>
</div>
