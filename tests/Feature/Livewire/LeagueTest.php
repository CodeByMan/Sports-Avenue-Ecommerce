<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\League;
use App\Models\League as LeagueModel;
use Database\Seeders\LeagueSeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeagueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->seed(LeagueSeeder::class);
    }

    #[Test]
    public function league_page_can_be_rendered(): void
    {
        $this->get(route('league'))
            ->assertOk()
            ->assertSeeText(trans('List of leagues'));
    }

    #[Test]
    public function league_page_contains_livewire_component(): void
    {
        $this->get(route('league'))
            ->assertSeeLivewire(League::class);
    }

    #[Test]
    public function league_page_pagination_should_be_working(): void
    {
        $leagues = LeagueModel::query()
            ->orderBy('name')
            ->limit(League::$paginationCount)
            ->pluck('name')
            ->all();

        $this->get(route('league'))
            ->assertSeeInOrder($leagues);
    }

    #[Test]
    public function league_page_pagination_page_2_should_be_working(): void
    {
        $leagues = LeagueModel::query()
            ->orderBy('name')
            ->skip(League::$paginationCount)
            ->take(League::$paginationCount)
            ->pluck('name')
            ->all();

        $this->get(route('league', ['page' => 2]))
            ->assertSeeInOrder($leagues);
    }

    #[Test]
    public function league_page_pagination_with_search_should_be_working(): void
    {
        $keyword = 'le';
        $leagues = LeagueModel::query()
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->take(League::$paginationCount)
            ->pluck('name')
            ->all();

        Livewire::test(League::class)
            ->set('name', $keyword)
            ->assertSeeInOrder($leagues);
    }

    #[Test]
    public function league_cards_link_to_the_filtered_jersey_page(): void
    {
        $league = LeagueModel::query()->orderBy('name')->firstOrFail();

        $this->get(route('jersey', ['name' => $league->slug]))
            ->assertOk()
            ->assertSeeText($league->name);
    }
}
