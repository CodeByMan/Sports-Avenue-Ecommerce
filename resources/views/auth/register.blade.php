<x-guest-layout>
    <x-slot name="title">
        {{ config('app.name', 'Laravel') }} - {{ __('Register') }}
    </x-slot>

    <div class="auth-card">
        <header class="auth-card__header">
            <p class="auth-card__eyebrow">{{ __('Create your account') }}</p>
            <h2>{{ __('Join Sports Avenue') }}</h2>
            <p>{{ __('Save your details, manage orders, and enjoy a faster checkout.') }}</p>
        </header>

        <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--register">
            @csrf

            <div class="auth-form__grid">
                <div>
                    <label class="auth-field" for="name">
                        <span>{{ __('Full name') }}</span>
                        <input
                            id="name"
                            class="auth-input"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="{{ __('Your full name') }}"
                            @class(['is-invalid' => $errors->has('name')])
                        >
                    </label>
                    <x-input-error :messages="$errors->get('name')" class="auth-field-error" />
                </div>

                <div>
                    <label class="auth-field" for="username">
                        <span>{{ __('Username') }}</span>
                        <input
                            id="username"
                            class="auth-input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autocomplete="username"
                            placeholder="{{ __('Choose a username') }}"
                            @class(['is-invalid' => $errors->has('username')])
                        >
                    </label>
                    <x-input-error :messages="$errors->get('username')" class="auth-field-error" />
                </div>
            </div>

            <label class="auth-field" for="email">
                <span>{{ __('Email address') }}</span>
                <input
                    id="email"
                    class="auth-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="name@example.com"
                    @class(['is-invalid' => $errors->has('email')])
                >
            </label>
            <x-input-error :messages="$errors->get('email')" class="auth-field-error" />

            <div class="auth-form__grid">
                <div>
                    <x-password-input
                        id="password"
                        name="password"
                        :label="__('Password')"
                        autocomplete="new-password"
                        required
                        placeholder="{{ __('Create a password') }}"
                        @class(['is-invalid' => $errors->has('password')])
                    />
                    <x-input-error :messages="$errors->get('password')" class="auth-field-error" />
                </div>

                <div>
                    <x-password-input
                        id="password_confirmation"
                        name="password_confirmation"
                        :label="__('Confirm password')"
                        autocomplete="new-password"
                        required
                        placeholder="{{ __('Repeat your password') }}"
                        @class(['is-invalid' => $errors->has('password_confirmation')])
                    />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="auth-field-error" />
                </div>
            </div>

            <p class="auth-password-hint">{{ __('Use at least 10 characters, including a number and a symbol.') }}</p>

            <button type="submit" class="auth-submit">{{ __('Create Account') }}</button>
        </form>

        <p class="auth-card__switch">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}">{{ __('Sign in') }}</a>
        </p>
    </div>
</x-guest-layout>
