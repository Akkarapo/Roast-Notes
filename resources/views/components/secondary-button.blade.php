<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-transparent border-2 border-cobalt rounded-full font-semibold text-sm text-cobalt hover:bg-cobalt hover:text-paper focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt focus-visible:ring-offset-2 focus-visible:ring-offset-cream disabled:opacity-40 transition']) }}>
    {{ $slot }}
</button>
