@props(['product', 'size' => 'sm'])
{{-- Heart toggle: plain POST/DELETE form (works without JS). Guests are sent to log in and brought back. --}}
@inject('wishlist', 'App\\Support\\Community\\WishlistState')
@php
    $saved = $wishlist->has($product->id);
    $box = $size === 'lg' ? 'h-11 w-11' : 'h-9 w-9';
@endphp
<form method="POST" action="{{ route($saved ? 'wishlist.destroy' : 'wishlist.store', $product) }}" {{ $attributes }}>
    @csrf
    @if ($saved) @method('DELETE') @endif
    <button type="submit"
        class="grid {{ $box }} place-items-center rounded-full border-2 border-cobalt {{ $saved ? 'bg-cobalt text-paper' : 'bg-paper text-cobalt hover:bg-cobalt hover:text-paper' }} transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt focus-visible:ring-offset-2"
        aria-pressed="{{ $saved ? 'true' : 'false' }}"
        aria-label="{{ $saved ? __('Remove from Wish List') : __('Save to Wish List') }}"
        title="{{ $saved ? __('Remove from Wish List') : __('Save to Wish List') }}"
        data-wishlist-button="{{ $saved ? 'saved' : 'empty' }}">
        <svg viewBox="0 0 24 24" class="h-[55%] w-[55%]" fill="{{ $saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20s-7.5-4.6-9.2-9.4C1.6 7.2 3.8 4 7.2 4c2 0 3.6 1.1 4.8 2.8C13.2 5.1 14.8 4 16.8 4c3.4 0 5.6 3.2 4.4 6.6C19.5 15.4 12 20 12 20z" />
        </svg>
    </button>
</form>
