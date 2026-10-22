@props(['status'])
{{-- Member Info: current loyalty tier, cumulative spend and progress to the next tier --}}
@php
    use App\Support\Money;
    $tier = $status->tier;
    $perks = match (true) {
        $tier->early_access => __(':percent% off every order + buy rare lots :days days early', ['percent' => $tier->discount_percent, 'days' => 14]),
        $tier->discount_percent > 0 => __(':percent% off every order', ['percent' => $tier->discount_percent]),
        default => __('Collect spend to unlock member discounts'),
    };
@endphp
<section id="member-tier" {{ $attributes->merge(['class' => 'rounded-card border-2 border-cobalt bg-paper p-6 sm:p-8']) }}>
    <div class="flex flex-wrap items-center gap-6">
        <div class="grid h-24 w-24 shrink-0 place-items-center rounded-[22px] {{ $tier->slug === 'bean' ? 'border-2 border-cobalt text-cobalt' : 'bg-cobalt text-paper' }}">
            @if ($tier->slug === 'roaster-master')
                <x-illustrations.pour-over class="h-16 w-16" />
            @else
                <x-illustrations.bean-bag class="h-16 w-16" />
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm text-cobalt/70">{{ __('Member tier') }}</p>
            <h2 class="flex flex-wrap items-baseline gap-x-3 text-3xl font-bold">
                {{ $tier->name }}
                <span class="font-script text-xl font-normal">{{ match ($tier->slug) { 'roaster-master' => 'top of the roast', 'barista' => 'behind the bar', default => 'just sprouted' } }}</span>
            </h2>
            <p class="mt-1 text-cobalt/80" data-tier-perks>{{ $perks }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-cobalt/70">{{ __('Total spend') }}</p>
            <p class="font-script text-3xl" data-tier-spend>{{ Money::bahtFromSatang($status->spend) }}</p>
        </div>
    </div>

    <div class="mt-6">
        <div class="h-3 overflow-hidden rounded-full border-2 border-cobalt bg-cream" role="progressbar" aria-valuenow="{{ $status->progress() }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full bg-cobalt" style="width: {{ $status->progress() }}%"></div>
        </div>
        <p class="mt-2 flex flex-wrap justify-between gap-2 text-sm" data-tier-next>
            @if ($status->next)
                <span>{{ __(':amount more to :tier (:percent% off)', ['amount' => Money::bahtFromSatang($status->remaining()), 'tier' => $status->next->name, 'percent' => $status->next->discount_percent]) }}</span>
            @else
                <span>{{ __('You are at the top tier — thank you for roasting with us!') }}</span>
            @endif
            <span class="text-cobalt/60">{{ __('Counts delivered orders only') }}</span>
        </p>
    </div>
</section>
