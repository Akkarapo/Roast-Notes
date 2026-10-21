@if (session('cart_added') || session('flash'))
    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6" role="status">
        <div x-data="{ show: true }" x-show="show"
             class="flex flex-wrap items-center gap-3 rounded-[14px] bg-cobalt px-5 py-3 text-paper">
            @if (session('cart_added'))
                <span class="font-script text-xl">added!</span>
                <span>{{ __('Added to cart: :item', ['item' => session('cart_added')]) }}</span>
                <a href="{{ route('cart.index') }}" class="ms-auto font-semibold underline underline-offset-4">{{ __('View cart') }} ›</a>
            @else
                <span>{{ session('flash') }}</span>
            @endif
            <button type="button" @click="show = false" class="{{ session('cart_added') ? '' : 'ms-auto' }} text-paper/70 hover:text-paper" aria-label="{{ __('Close') }}">✕</button>
        </div>
    </div>
@endif
