<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Jersey;
use App\Models\Jersey as JerseyModel;
use App\Models\League;
use Database\Seeders\JerseySeeder;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JerseyTest extends TestCase
{
    private string $keyword = 'ar';

    private string $queryString;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->seed(JerseySeeder::class);

        $this->queryString = League::query()
            ->where('name', 'Premier League')
            ->value('slug');
    }

    #[Test]
    public function jersey_page_can_be_rendered_without_query_string(): void
    {
        $this->get(route('jersey'))
            ->assertOk()
            ->assertSeeText(trans('All products'))
            ->assertSeeInOrder($this->getPaginationData());
    }

    #[Test]
    public function jersey_page_can_be_rendered_with_query_string(): void
    {
        $league = League::query()->where('slug', $this->queryString)->firstOrFail();

        $this->get(route('jersey', ['name' => $league->slug]))
            ->assertOk()
            ->assertSeeText($league->name)
            ->assertSeeInOrder($this->getPaginationData(queryString: $league->slug));
    }

    #[Test]
    public function jersey_page_contains_livewire_component(): void
    {
        $this->get(route('jersey'))
            ->assertSeeLivewire(Jersey::class);
    }

    #[Test]
    public function jersey_page_pagination_page_2_should_be_working(): void
    {
        $this->get(route('jersey', ['page' => 2]))
            ->assertSeeInOrder($this->getPaginationData(true));
    }

    #[Test]
    public function jersey_page_can_search_jersey_by_name(): void
    {
        Livewire::test(Jersey::class)
            ->set('name', $this->keyword)
            ->assertSeeInOrder($this->getPaginationData(keyword: $this->keyword));
    }

    #[Test]
    public function jersey_page_can_search_jersey_by_league_select_option(): void
    {
        Livewire::test(Jersey::class)
            ->set('leagueSelected', $this->queryString)
            ->assertSeeInOrder($this->getPaginationData(queryString: $this->queryString));
    }

    #[Test]
    public function jersey_page_pagination_with_input_search_and_select_box_should_be_working(): void
    {
        Livewire::test(Jersey::class)
            ->set('name', $this->keyword)
            ->set('leagueSelected', $this->queryString)
            ->assertSeeInOrder($this->getPaginationData(
                keyword: $this->keyword,
                queryString: $this->queryString,
            ));
    }

    #[Test]
    public function jersey_cards_link_to_the_jersey_detail_page(): void
    {
        $jersey = JerseyModel::query()->inRandomOrder()->firstOrFail();

        $this->get(route('jersey.detail', $jersey))
            ->assertOk()
            ->assertSeeText($jersey->name)
            ->assertSeeText(trans('Add to Cart'));
    }

    private function getPaginationData(
        bool $secondPaginationData = false,
        string $keyword = '',
        ?string $queryString = null,
    ): array {
        $query = JerseyModel::query()
            ->active()
            ->when($keyword !== '', fn ($builder) => $builder->where('name', 'like', "%{$keyword}%"))
            ->when($queryString, fn ($builder, string $slug) => $builder->hasLeague($slug))
            ->orderByDesc('is_featured')
            ->orderByDesc('sold');

        if ($secondPaginationData) {
            $query->skip(Jersey::$paginationCount);
        }

        return $query
            ->take(Jersey::$paginationCount)
            ->pluck('name')
            ->all();
    }
}
