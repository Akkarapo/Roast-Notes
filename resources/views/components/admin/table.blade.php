{{-- Bordered paper table for admin lists. Put <thead>/<tbody> in the slot. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-card border-2 border-cobalt bg-paper']) }}>
    <table class="w-full text-sm [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:font-semibold [&_th]:text-cobalt/80 [&_td]:px-4 [&_td]:py-3 [&_tbody_tr]:border-t-[1.5px] [&_tbody_tr]:border-dashed [&_tbody_tr]:border-cobalt/30">
        {{ $slot }}
    </table>
</div>
