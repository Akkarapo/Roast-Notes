@use('App\Enums\BrewMethod')
<x-admin-layout :title="__('Reviews')">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        @foreach (['' => __('All stars'), 5 => '5★', 4 => '4★', 3 => '3★', 2 => '2★', 1 => '1★'] as $value => $label)
            <x-filter-chip type="radio" name="rating" :value="$value" :checked="(string) ($rating ?? '') === (string) $value" onchange="this.form.submit()">{{ $label }}</x-filter-chip>
        @endforeach
        <select name="brew" onchange="this.form.submit()" class="rounded-full border-2 border-cobalt/40 bg-paper py-1.5 pl-4 pr-9 text-sm text-cobalt focus:border-cobalt focus:ring-0">
            <option value="">{{ __('All methods') }}</option>
            @foreach (BrewMethod::cases() as $case)
                <option value="{{ $case->value }}" @selected($brew === $case)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <span class="ms-auto flex gap-2">
            <x-text-input name="q" :value="$search" placeholder="{{ __('Words or product') }}" class="w-56" />
            <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
        </span>
    </form>

    <x-admin.table class="mt-6">
        <thead>
            <tr><th>{{ __('Date') }}</th><th>{{ __('Product') }}</th><th>{{ __('Reviewer') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Notes') }}</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($reviews as $review)
                @php
                    $item = $review->orderItem;
                @endphp
                <tr data-review-row>
                    <td class="whitespace-nowrap">{{ $review->created_at->translatedFormat('j M Y') }}</td>
                    <td>
                        <a href="{{ route('products.show', $item->variant->product) }}#reviews" class="font-semibold underline-offset-4 hover:underline" target="_blank">{{ $item->product_name }}</a>
                        <span class="block text-xs text-cobalt/70">{{ $review->brew_method->label() }}</span>
                    </td>
                    <td><a href="{{ route('admin.users.show', $item->order->user) }}" class="underline-offset-4 hover:underline">{{ $item->order->user->name }}</a></td>
                    <td class="whitespace-nowrap"><x-star-rating :rating="$review->rating" /></td>
                    <td class="max-w-md">{{ \Illuminate\Support\Str::limit($review->comment ?? '—', 140) }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm(@js(__('Delete this review?')))">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-semibold text-brick underline-offset-4 hover:underline">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-cobalt/70">{{ __('No reviews match.') }}</td></tr>
            @endforelse
        </tbody>
    </x-admin.table>
    <div class="mt-4">{{ $reviews->links() }}</div>
</x-admin-layout>
