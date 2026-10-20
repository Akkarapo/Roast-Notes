@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-cobalt border-2 border-cobalt rounded-[14px] px-4 py-2']) }}>
        {{ $status }}
    </div>
@endif
