<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Filament\Pages\Dashboard;
use App\Livewire\Home;
use App\Models\Brand;
use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Database\Seeders\JerseySeeder;
use Database\Seeders\LeagueSeeder;
use Database\Seeders\StorefrontSettingSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    #[Test]
    public function home_page_can_be_rendered(): void
    {
        $this->seed(StorefrontSettingSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Sports Avenue')
            ->assertSeeText('Wear the Glory')
            ->assertSeeText('Wear your passion on your sleeve.');
    }

    #[Test]
    public function navbar_guest_can_be_rendered(): void
    {
        $this->get(route('home'))
            ->assertSeeText(trans('Login'))
            ->assertSeeText(trans('Register'));
    }


    #[Test]
    public function dashboard_link_is_only_visible_to_admin_and_staff_users(): void
    {
        $dashboardUrl = Dashboard::getUrl(panel: 'admin');

        foreach ([User::ROLE_ADMIN, User::ROLE_STAFF] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('home'))
                ->assertOk()
                ->assertSee($dashboardUrl, false)
                ->assertSeeText(__('Dashboard'));
        }

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee($dashboardUrl, false)
            ->assertDontSeeText(__('Dashboard'));
    }

    #[Test]
    public function home_page_contains_livewire_component(): void
    {
        $this->get(route('home'))
            ->assertSeeLivewire(Home::class);
    }

    #[Test]
    public function home_page_shows_sections_in_required_order_and_clickable_league_logos(): void
    {
        $this->seed([StorefrontSettingSeeder::class, LeagueSeeder::class, JerseySeeder::class]);
        $league = League::query()->orderBy('name')->firstOrFail();

        $this->get(route('home'))
            ->assertSeeInOrder(['hero-title', 'league-heading', 'featured-heading', 'brand-heading'], false)
            ->assertSee(route('jersey', ['name' => $league->slug]), false)
            ->assertSee($league->getImage(), false)
            ->assertSee('alt="'.$league->name.'"', false)
            ->assertSee('league-logo-card', false);
    }

    #[Test]
    public function home_page_contains_managed_brands_in_display_order(): void
    {
        $this->seed(BrandSeeder::class);

        $expectedBrandUrls = Brand::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand): string => route('jersey', ['brand' => $brand->slug]))
            ->all();

        $this->get(route('home'))
            ->assertSeeInOrder($expectedBrandUrls, false);
    }

    #[Test]
    public function home_page_contains_four_featured_products_in_display_order(): void
    {
        $this->seed(JerseySeeder::class);

        $expectedProducts = Jersey::query()
            ->active()
            ->orderByDesc('is_featured')
            ->orderByDesc('sold')
            ->limit(4)
            ->pluck('name')
            ->all();

        $this->get(route('home'))
            ->assertSeeInOrder($expectedProducts);
    }
}
