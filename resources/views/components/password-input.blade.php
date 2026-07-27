@props([
    'id',
    'name',
    'label' => null,
    'autocomplete' => 'current-password',
    'required' => false,
])

<label class="auth-field" data-password-field>
    <span>{{ $label ?? __('Password') }}</span>
    <span class="auth-password-control">
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $name }}"
            autocomplete="{{ $autocomplete }}"
            @required($required)
            {{ $attributes->merge(['class' => 'auth-input auth-input--password']) }}
        >
        <button type="button" class="auth-password-toggle" data-password-toggle aria-label="{{ __('Show password') }}" aria-pressed="false">
            <svg class="auth-password-toggle__show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <svg class="auth-password-toggle__hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A11.7 11.7 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.1 3.8"></path><path d="M6.6 6.7C3.5 8.6 2 12 2 12s3.5 6 10 6c1.2 0 2.3-.2 3.3-.5"></path><path d="M10.7 10.7a2 2 0 0 0 2.6 2.6"></path></svg>
        </button>
    </span>
</label>
