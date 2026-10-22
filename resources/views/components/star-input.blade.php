@props(['name' => 'rating', 'value' => null])
{{-- 1–5 stars as radio buttons: arrow keys work, no JS needed. Alpine only previews the hover. --}}
<fieldset x-data="{ value: {{ (int) $value }}, hover: 0 }" {{ $attributes }}>
    <legend class="text-sm font-semibold">{{ __('Your rating') }}</legend>
    <div class="mt-2 flex items-center gap-1" @mouseleave="hover = 0">
        @for ($i = 1; $i <= 5; $i++)
            <label class="cursor-pointer" @mouseenter="hover = {{ $i }}">
                <input type="radio" name="{{ $name }}" value="{{ $i }}" @checked((int) $value === $i) x-model.number="value" class="peer sr-only" required>
                <span class="sr-only">{{ __(':rating out of 5', ['rating' => $i]) }}</span>
                <svg viewBox="0 0 24 24" class="h-10 w-10 text-cobalt transition peer-focus-visible:ring-2 peer-focus-visible:ring-cobalt rounded"
                     :class="(hover || value) >= {{ $i }} ? 'fill-current' : 'fill-transparent'" aria-hidden="true">
                    <path d="M12 2.8l2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.1l-5.7 3.2 1.2-6.4-4.7-4.4 6.4-.8z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
                </svg>
            </label>
        @endfor
        <span class="ms-3 font-script text-2xl" x-text="['', 'ไม่ใช่ทางเรา', 'พอได้', 'ดี', 'ดีมาก', 'ชอบสุด ๆ'][hover || value]"></span>
    </div>
</fieldset>
