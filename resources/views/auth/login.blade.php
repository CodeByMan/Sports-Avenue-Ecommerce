<x-guest-layout>
    <x-slot name="title">
        {{ config('app.name', 'Laravel') }} - {{ __('Login') }}
    </x-slot>

    <div class="auth-card">
        <header class="auth-card__header">
            <p class="auth-card__eyebrow">{{ __('Member access') }}</p>
            <h2>{{ __('Sign in to Sports Avenue') }}</h2>
            <p>{{ __('Access your cart, order history, and account details.') }}</p>
        </header>

        <x-auth-session-status :status="session('status')" class="auth-status" />

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <label class="auth-field" for="username">
                <span>{{ __('Username or email') }}</span>
                <input
                    id="username"
                    class="auth-input"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="{{ __('Enter your username or email') }}"
                    @class(['is-invalid' => $errors->has('username')])
                >
            </label>
            <x-input-error :messages="$errors->get('username')" class="auth-field-error" />

            <x-password-input
                id="password"
                name="password"
                :label="__('Password')"
                autocomplete="current-password"
                required
                placeholder="{{ __('Enter your password') }}"
                @class(['is-invalid' => $errors->has('password')])
            />
            <x-input-error :messages="$errors->get('password')" class="auth-field-error" />

            <div class="auth-form__options">
                <label class="auth-checkbox" for="remember_me">
                    <input id="remember_me" type="checkbox" name="remember">
                    <span>{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                @endif
            </div>

            <button type="submit" class="auth-submit">{{ __('Sign In') }}</button>
        </form>

        <p class="auth-card__switch">
            {{ __('New to Sports Avenue?') }}
            <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
        </p>
    </div>
</x-guest-layout>
