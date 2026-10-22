@props(['name', 'value', 'checked' => false, 'type' => 'checkbox', 'disabled' => false])

<label class="shrink-0 {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @checked($checked) @disabled($disabled) {{ $attributes->only(['required', 'x-model', 'x-model.number', 'onchange']) }} class="peer sr-only">
    <span class="inline-flex items-center rounded-full border-[1.5px] border-cobalt px-3.5 py-1.5 text-sm font-medium text-cobalt transition
                 hover:bg-paper peer-checked:bg-cobalt peer-checked:text-paper peer-focus-visible:ring-2 peer-focus-visible:ring-cobalt peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-cream
                 peer-disabled:opacity-40 peer-disabled:line-through peer-disabled:cursor-not-allowed">
        {{ $slot }}
    </span>
</label>
