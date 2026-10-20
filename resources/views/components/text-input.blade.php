@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-paper border-2 border-cobalt/40 text-cobalt placeholder-cobalt/40 rounded-[14px] focus:border-cobalt focus:ring-0 transition']) }}>
