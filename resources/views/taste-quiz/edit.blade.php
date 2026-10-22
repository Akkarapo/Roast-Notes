@php
    $descriptions = [
        'fruity' => 'ผลไม้สดชื่น เปรี้ยวหวาน',
        'floral' => 'หอมดอกไม้ ชา เบาสบาย',
        'sweet' => 'หวานคาราเมล น้ำผึ้ง',
        'nutty-cocoa' => 'ถั่วคั่ว ช็อกโกแลต นุ่มกลม',
        'sour-fermented' => 'ไวน์ เหล้ารัม หมักเข้มข้น',
    ];
    $selected = collect(old('flavors', $user->tastePreferences->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
    $roast = old('roast', $user->taste_quiz_completed_at ? ($user->preferred_roast_level?->value ?? 'unsure') : null);
    $brew = old('brew', $user->preferred_brew_method?->value);
    $startStep = $errors->has('flavors') || $errors->has('flavors.*') ? 1 : ($errors->has('roast') ? 2 : ($errors->has('brew') ? 3 : 1));
@endphp

<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center gap-5">
            <x-mascot pose="sip" class="hidden h-32 w-32 shrink-0 sm:block" />
            <div>
                <p class="font-script text-3xl">taste quiz</p>
                <h1 class="mt-1 text-3xl font-semibold">{{ $user->taste_quiz_completed_at ? __('Edit your taste profile') : __('3 quick questions about how you like your coffee') }}</h1>
                <p class="mt-2 text-cobalt/80">{{ __('Your answers turn into a “matches your taste X%” on every bean.') }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('taste-quiz.update') }}" class="mt-8"
              x-data="{ step: {{ $startStep }}, flavors: @js($selected), roast: @js($roast), brew: @js($brew) }">
            @csrf
            @method('PUT')

            {{-- progress (JS only) --}}
            <div class="mb-6 hidden gap-2" x-init="$el.classList.replace('hidden', 'flex')" aria-hidden="true">
                @foreach ([1, 2, 3] as $n)
                    <span class="h-2 flex-1 rounded-full border-2 border-cobalt" :class="step >= {{ $n }} ? 'bg-cobalt' : ''"></span>
                @endforeach
            </div>

            {{-- Q1 --}}
            <fieldset x-show="step === 1" class="rounded-card border-2 border-cobalt bg-paper p-6">
                <legend class="sr-only">{{ __('Question 1') }}</legend>
                <p class="text-sm font-semibold text-cobalt/70">1 / 3</p>
                <h2 class="mt-1 text-xl font-semibold">{{ __('Which flavors do you enjoy? (pick 1–3)') }}</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($categories as $category)
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="flavors[]" value="{{ $category->id }}" x-model.number="flavors" @checked(in_array($category->id, $selected, true))
                                   :disabled="flavors.length >= 3 && ! flavors.includes({{ $category->id }})" class="peer sr-only">
                            <span class="block h-full rounded-[18px] border-2 border-cobalt/40 p-4 transition hover:border-cobalt peer-checked:border-cobalt peer-checked:bg-cobalt peer-checked:text-paper peer-focus-visible:ring-2 peer-focus-visible:ring-cobalt peer-disabled:cursor-not-allowed peer-disabled:opacity-40">
                                <span class="block font-script text-2xl">{{ $category->name }}</span>
                                <span class="block text-sm">{{ $descriptions[$category->slug] ?? '' }}</span>
                                <span class="mt-1 block text-xs opacity-75">{{ $category->children->flatMap->notes->take(4)->pluck('name')->join(' · ') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="array_merge($errors->get('flavors'), $errors->get('flavors.*') ? [__('Please pick from the list.')] : [])" class="mt-3" />
            </fieldset>

            {{-- Q2 --}}
            <fieldset x-show="step === 2" class="mt-6 rounded-card border-2 border-cobalt bg-paper p-6">
                <legend class="sr-only">{{ __('Question 2') }}</legend>
                <p class="text-sm font-semibold text-cobalt/70">2 / 3</p>
                <h2 class="mt-1 text-xl font-semibold">{{ __('How do you like it roasted?') }}</h2>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach (\App\Enums\RoastLevel::cases() as $level)
                        <x-filter-chip type="radio" name="roast" :value="$level->value" :checked="$roast === $level->value" x-model="roast">{{ $level->label() }} · {{ $level->thaiLabel() }}</x-filter-chip>
                    @endforeach
                    <x-filter-chip type="radio" name="roast" value="unsure" :checked="$roast === 'unsure'" x-model="roast">{{ __('Not sure') }}</x-filter-chip>
                </div>
                <x-input-error :messages="$errors->get('roast')" class="mt-3" />
            </fieldset>

            {{-- Q3 --}}
            <fieldset x-show="step === 3" class="mt-6 rounded-card border-2 border-cobalt bg-paper p-6">
                <legend class="sr-only">{{ __('Question 3') }}</legend>
                <p class="text-sm font-semibold text-cobalt/70">3 / 3</p>
                <h2 class="mt-1 text-xl font-semibold">{{ __('How do you brew most often?') }}</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($brewMethods as $method)
                        <label class="cursor-pointer">
                            <input type="radio" name="brew" value="{{ $method->value }}" x-model="brew" @checked($brew === $method->value) class="peer sr-only">
                            <span class="block rounded-[18px] border-2 border-cobalt/40 px-4 py-3 transition hover:border-cobalt peer-checked:border-cobalt peer-checked:bg-cobalt peer-checked:text-paper peer-focus-visible:ring-2 peer-focus-visible:ring-cobalt">
                                <span class="font-semibold">{{ $method->label() }}</span>
                                <span class="block text-sm opacity-80">{{ $method->thaiHint() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('brew')" class="mt-3" />
            </fieldset>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                {{-- step buttons only exist with JS --}}
                <template x-if="step > 1">
                    <x-secondary-button @click="step--">‹ {{ __('Back') }}</x-secondary-button>
                </template>
                <template x-if="step < 3">
                    <x-primary-button type="button" @click="step++"
                        x-bind:disabled="(step === 1 && flavors.length === 0) || (step === 2 && ! roast)">{{ __('Next') }} ›</x-primary-button>
                </template>
                <x-primary-button x-show="step === 3" x-bind:disabled="! brew">{{ __('Save my taste profile') }}</x-primary-button>

                <a href="{{ route('home') }}" class="ms-auto text-sm underline underline-offset-4">{{ $user->taste_quiz_completed_at ? __('Cancel') : __('Skip for now') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
