<x-guest-layout>
    <div class="mb-4 text-sm text-cobalt/70">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-cobalt">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline underline-offset-4 text-sm text-cobalt/80 hover:text-cobalt rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-cobalt">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
