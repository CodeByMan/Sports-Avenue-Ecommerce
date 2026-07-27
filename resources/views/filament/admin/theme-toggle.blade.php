<div class="sa-theme-toggle" x-data>
    <button
        type="button"
        class="sa-theme-toggle__button"
        x-on:click="$dispatch('theme-changed', $store.theme === 'dark' ? 'light' : 'dark')"
        x-bind:aria-label="$store.theme === 'dark' ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
        x-bind:title="$store.theme === 'dark' ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
    >
        <x-filament::icon icon="heroicon-m-moon" class="h-5 w-5" x-show="$store.theme !== 'dark'" x-cloak />
        <x-filament::icon icon="heroicon-m-sun" class="h-5 w-5" x-show="$store.theme === 'dark'" x-cloak />
    </button>
</div>
