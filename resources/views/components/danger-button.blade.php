<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brick border-2 border-brick rounded-full font-semibold text-sm text-paper hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-brick focus-visible:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
