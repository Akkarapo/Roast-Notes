@php
    $presets = ['30d' => __('30 days'), '90d' => __('90 days'), '12m' => __('12 months'), 'ytd' => __('This year')];
@endphp
<x-admin-layout :title="__('Sales report')" script="the numbers">
    <section class="flex flex-wrap items-end justify-between gap-4 rounded-card border-2 border-cobalt bg-paper p-5" data-range>
        <div>
            <p class="text-sm text-cobalt/70">{{ __('Period') }}</p>
            <p class="font-script text-2xl">{{ $range->label() }}</p>
            <p class="text-xs text-cobalt/60">{{ __(':days days · compared with the :days days before', ['days' => $range->days()]) }}</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <nav class="flex flex-wrap gap-2">
                @foreach ($presets as $value => $label)
                    <a href="{{ route('admin.reports.index', ['range' => $value]) }}" @class(['rounded-full border-[1.5px] border-cobalt px-3.5 py-1.5 text-sm font-medium', 'bg-cobalt text-paper' => $range->preset === $value, 'hover:bg-cream' => $range->preset !== $value])>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <label class="text-xs font-semibold">{{ __('Start date') }}
                    <x-text-input type="date" name="from" :value="$range->start->toDateString()" class="mt-1 block py-1.5 text-sm" required />
                </label>
                <label class="text-xs font-semibold">{{ __('End date') }}
                    <x-text-input type="date" name="to" :value="$range->end->toDateString()" class="mt-1 block py-1.5 text-sm" required />
                </label>
                <x-secondary-button type="submit">{{ __('Show') }}</x-secondary-button>
            </form>
        </div>
    </section>

    <div class="mt-8 space-y-10">
        @foreach ($reports as ['report' => $report, 'data' => $data, 'sql' => $sql])
            <section id="{{ $report->key() }}" class="scroll-mt-6" data-report="{{ $report->key() }}">
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-xl font-semibold">{{ $report->title() }}</h2>
                    <a href="{{ route('admin.reports.csv', ['key' => $report->key()] + $range->query()) }}" class="text-sm font-semibold underline-offset-4 hover:underline" data-csv>⇩ CSV</a>
                </div>

                @include('admin.reports.partials.'.$report->key(), $data)

                <details class="mt-3 rounded-[14px] border-[1.5px] border-dashed border-cobalt/50 bg-paper px-4 py-2 text-sm" data-sql>
                    <summary class="cursor-pointer font-semibold">{{ __('Show SQL') }}</summary>
                    @foreach ($sql as $name => $statement)
                        @if (count($sql) > 1)<p class="mt-3 text-xs font-semibold text-cobalt/70">{{ $name }}</p>@endif
                        <pre class="mt-2 overflow-x-auto whitespace-pre-wrap rounded-[10px] bg-cream p-3 font-mono text-xs leading-relaxed">{{ \App\Support\Reports\SqlFormatter::format($statement) }}</pre>
                    @endforeach
                </details>
            </section>
        @endforeach
    </div>
</x-admin-layout>
