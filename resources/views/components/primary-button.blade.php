<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-cobalt border-2 border-cobalt rounded-full font-semibold text-sm text-paper hover:bg-cobalt-ink hover:border-cobalt-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt focus-visible:ring-offset-2 focus-visible:ring-offset-cream disabled:opacity-40 transition']) }}>
    {{ $slot }}
</button>
