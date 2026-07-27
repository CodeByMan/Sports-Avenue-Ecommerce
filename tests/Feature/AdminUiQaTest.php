<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class AdminUiQaTest extends TestCase
{
    public function test_admin_topbar_links_to_full_notifications_page(): void
    {
        $provider = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
        $view = file_get_contents(resource_path('views/filament/admin/account-controls.blade.php'));

        $this->assertStringContainsString('PanelsRenderHook::TOPBAR_END', $provider);
        $this->assertStringContainsString('Notifications::class', $provider);
        $this->assertStringNotContainsString('->databaseNotifications()', $provider);
        $this->assertStringContainsString('Notifications::getUrl()', $view);
        $this->assertStringContainsString('ProfileResource::getUrl()', $view);
        $this->assertStringContainsString('OrderResource::getUrl()', $view);
        $this->assertStringContainsString("route('home')", $view);
        $this->assertStringContainsString('filament()->getLogoutUrl()', $view);
        $this->assertStringNotContainsString('Dashboard::getUrl()', $view);
    }

    public function test_admin_css_is_compact(): void
    {
        $css = file_get_contents(resource_path('css/filament/admin/theme.css'));

        $this->assertStringContainsString('SPORTS-AVENUE-COMPACT-ADMIN-UI', $css);
        $this->assertStringContainsString('.fi-sidebar-item-label', $css);
        $this->assertStringContainsString('font-size: 0.76rem', $css);
        $this->assertStringContainsString('SPORTS-AVENUE-FULL-PAGE-NOTIFICATIONS', $css);
        $this->assertStringContainsString('.sa-admin-notification-link', $css);
        $this->assertStringContainsString('width: 2.3rem', $css);
        $this->assertStringContainsString('width: 1.15rem', $css);
        $this->assertStringContainsString('.sa-admin-account-menu__identity strong', $css);
        $this->assertStringContainsString('font-size: 0.74rem', $css);
        $this->assertStringContainsString('.sa-admin-account-menu__chevron', $css);
    }

    public function test_storefront_hero_is_smaller_centered_and_has_no_overlay(): void
    {
        $view = file_get_contents(resource_path('views/livewire/home.blade.php'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--hero-background-image', $view);
        $this->assertStringContainsString("__('Shop Jerseys')", $view);
        $this->assertStringNotContainsString('Explore leagues', $view);
        $this->assertStringNotContainsString('hero-storefront__visual', $view);
        $this->assertStringContainsString('SPORTS-AVENUE-HERO-COMPACT-NO-OVERLAY', $css);
        $this->assertStringContainsString('background-image: var(--hero-background-image, none)', $css);
        $this->assertStringContainsString('content: none !important', $css);
        $this->assertStringContainsString('font-size: clamp(2.25rem, 5vw, 4.75rem)', $css);
        $this->assertStringContainsString('SPORTS-AVENUE-HERO-TYPOGRAPHY-BALANCE', $css);
        $this->assertStringContainsString('white-space: nowrap', $css);
        $this->assertStringContainsString('margin-top: clamp(0.75rem, 1.6vh, 1rem)', $css);
    }

    public function test_google_authentication_ui_and_routes_are_removed(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $services = file_get_contents(config_path('services.php'));

        $this->assertStringNotContainsString('x-social-auth', $login);
        $this->assertStringNotContainsString('x-social-auth', $register);
        $this->assertStringNotContainsString('SocialiteController', $routes);
        $this->assertStringNotContainsString('/auth/google', $routes);
        $this->assertStringNotContainsString("'google' =>", $services);
    }
}
