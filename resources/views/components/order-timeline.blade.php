@props(['changes', 'showWho' => false])
{{-- Status history, oldest first. Members see times and notes; admins also see who changed it. --}}
<ol {{ $attributes->merge(['class' => 'relative space-y-4 border-s-2 border-dashed border-cobalt/50 ps-6']) }} data-order-timeline>
    @foreach ($changes as $change)
        <li class="relative">
            <span class="absolute -start-[33px] top-1 grid h-4 w-4 place-items-center rounded-full border-2 border-cobalt {{ $loop->last ? 'bg-cobalt' : 'bg-paper' }}"></span>
            <p class="flex flex-wrap items-baseline gap-x-2">
                <span class="font-semibold">{{ $change->from_status ? $change->to_status->label() : __('Order placed') }}</span>
                <span class="text-xs text-cobalt/70">{{ $change->created_at->translatedFormat('j M Y · H:i') }}</span>
                @if ($showWho && $change->changedBy)
                    <span class="text-xs text-cobalt/70">· {{ $change->changedBy->isAdmin() ? __('by :name', ['name' => $change->changedBy->name]) : __('by customer') }}</span>
                @endif
            </p>
            @if ($change->note)
                <p class="text-sm text-cobalt/80">{{ $change->note }}</p>
            @endif
        </li>
    @endforeach
</ol>
