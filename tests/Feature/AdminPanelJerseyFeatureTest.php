<?php

namespace Tests\Feature;

use App\Filament\Resources\JerseyResource;
use App\Filament\Resources\JerseyResource\Pages\CreateJersey;
use App\Filament\Resources\JerseyResource\Pages\EditJersey;
use App\Filament\Resources\JerseyResource\Pages\ListJerseys;
use App\Filament\Resources\JerseyResource\Pages\ViewJersey;
use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Database\Seeders\LeagueSeeder;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelJerseyFeatureTest extends TestCase
{
    private Collection $jerseys;

    private Jersey $jersey;

    private string $directory = 'jerseys';

    private int $paginationCount = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        Storage::fake($this->directory);
        $this->seed([
            LeagueSeeder::class,
            JerseySeeder::class,
        ]);
        $userAdmin = $this->authenticatedUser(['role' => User::$roles[0]]);
        Jersey::query()->update(['created_by' => $userAdmin->id]);

        $this->jerseys = Jersey::all();
        $this->jersey = $this->jerseys
            ->random(1)
            ->first();
    }

    #[Test]
    public function jersey_menu_list_can_be_rendered(): void
    {
        $this->get(JerseyResource::getUrl())
            ->assertSuccessful()
            ->assertSee(trans('List of jerseys'));
    }

    #[Test]
    public function jersey_menu_list_can_show_table_records(): void
    {
        Livewire::test(ListJerseys::class)
            ->assertCanSeeTableRecords($this->getPaginationData());
    }

    #[Test]
    public function jersey_menu_list_pagination_page_2_can_be_rendered(): void
    {
        Livewire::withQueryParams(['page' => 2])
            ->test(ListJerseys::class)
            ->assertCanSeeTableRecords($this->getPaginationData(true));
    }

    #[Test]
    public function jersey_menu_list_can_search_jersey_by_name(): void
    {
        Livewire::test(ListJerseys::class)
            ->assertCanSeeTableRecords($this->getPaginationData())
            ->searchTable($this->jersey->name)
            ->assertCanSeeTableRecords($this->getPaginationData(name: $this->jersey->name));
    }

    #[Test]
    public function jersey_menu_list_can_filter_jersey_by_league(): void
    {
        $league = 'ligue-1';

        Livewire::test(ListJerseys::class)
            ->assertCanSeeTableRecords($this->getPaginationData())
            ->filterTable('league', ['league' => $league])
            ->assertCanSeeTableRecords($this->getPaginationData(league: $league));
    }

    #[Test]
    public function jersey_menu_list_can_search_jersey_by_name_and_filter_by_league(): void
    {
        $name = 'liverpool';
        $league = 'serie-a';

        Livewire::test(ListJerseys::class)
            ->assertCanSeeTableRecords($this->getPaginationData())
            ->searchTable($name)
            ->assertCanSeeTableRecords($this->getPaginationData(name: $name))
            ->filterTable('league', ['league' => $league])
            ->assertCanSeeTableRecords($this->getPaginationData(name: $name, league: $league));
    }

    #[Test]
    public function jersey_menu_create_can_be_rendered(): void
    {
        $this->get(JerseyResource::getUrl('create'))
            ->assertSuccessful()
            ->assertSee(trans('Create jersey'));
    }

    #[Test]
    public function jersey_menu_create_can_create_new_jersey(): void
    {
        $image = UploadedFile::fake()
            ->image('beatles.png');
        $image = ['image' => $image];
        $jersey = Jersey::factory()
            ->make();
        $data = array_merge($jersey->toArray(), $image);

        Livewire::test(CreateJersey::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.resources.jerseys.index'));

        $createdJersey = Jersey::query()->where('name', $jersey->name)->firstOrFail();
        $imagePath = $createdJersey->image;
        $this->deleteFile($imagePath);

        $this->assertDatabaseHas(Jersey::class, array_merge($jersey->only(['name', 'slug']), ['image' => $imagePath]))
            ->assertDatabaseCount(Jersey::class, 21);
    }

    #[Test]
    public function jersey_menu_create_the_validation_rules_should_be_dispatched(): void
    {
        $this->withExceptionHandling();
        $data = ['league_id' => 99, 'weight' => -1, 'price' => -1, 'S' => -1];
        $jersey = Jersey::factory()
            ->make();
        $data = array_merge(Arr::except($jersey->toArray(), ['image', 'stock']), $data);
        $data = array_merge($data, ['name' => $this->jersey->name]);

        Livewire::test(CreateJersey::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors(['league_id' => 'exists', 'name' => 'unique', 'weight' => 'min', 'price' => 'min', 'S' => 'min']);
    }

    #[Test]
    public function jersey_menu_edit_can_be_rendered(): void
    {
        $this->get(JerseyResource::getUrl('edit', ['record' => $this->jersey]))
            ->assertSuccessful()
            ->assertSee(trans('Edit jersey'));
    }

    #[Test]
    public function jersey_menu_edit_can_retrieve_selected_jersey_data(): void
    {
        $jersey = $this->splitStock($this->jersey->toArray());
        $expectedData = Arr::except($jersey, ['image']);

        Livewire::test(EditJersey::class, ['record' => $this->jersey->slug])
            ->assertFormSet($expectedData);
    }

    #[Test]
    public function jersey_menu_edit_can_edit_selected_jersey(): void
    {
        $jersey = Arr::except(Jersey::factory()->make()->toArray(), ['sold', 'created_by', 'updated_by', 'deleted_by', 'deleted_at']);
        $jersey = $this->splitStock($jersey);
        $image = UploadedFile::fake()
            ->image('beatles.png');
        $image = ['image' => $image];
        $data = array_merge($jersey, $image);

        Livewire::test(EditJersey::class, ['record' => $this->jersey->slug])
            ->fillForm($data)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.resources.jerseys.index'));

        $imagePath = $this->jersey->fresh()->image;
        $this->deleteFile($imagePath);
        $data = array_merge($data, ['image' => $imagePath]);
        $data = $this->mergeStock($data);

        $this->assertDatabaseHas(Jersey::class, $data)
            ->assertDatabaseMissing(Jersey::class, $this->jersey->toArray());
    }

    #[Test]
    public function staff_can_edit_selected_jersey_created_by_admin(): void
    {
        $this->authenticatedUser(['role' => User::ROLE_STAFF]);

        $this->get(JerseyResource::getUrl('edit', ['record' => $this->jersey]))
            ->assertSuccessful();
    }

    #[Test]
    public function jersey_menu_delete_can_delete_selected_jersey(): void
    {
        Livewire::test(ListJerseys::class)
            ->callTableAction(DeleteAction::class, $this->jersey);

        $this->assertDatabaseMissing(Jersey::class, $this->jersey->toArray());
    }

    #[Test]
    public function staff_can_delete_selected_jersey_created_by_admin(): void
    {
        $this->authenticatedUser(['role' => User::ROLE_STAFF]);

        Livewire::test(ListJerseys::class)
            ->callTableAction('delete', $this->jersey);

        $this->assertDatabaseMissing(
            Jersey::class,
            ['id' => $this->jersey->id],
        );
    }

    #[Test]
    public function jersey_menu_view_can_be_rendered(): void
    {
        $this->get(JerseyResource::getUrl('view', ['record' => $this->jersey]))
            ->assertSuccessful()
            ->assertSee(trans('Detail of jersey'));
    }

    #[Test]
    public function jersey_menu_view_can_retrieve_selected_jersey_data(): void
    {
        $jersey = Arr::except($this->jersey->toArray(), ['image']);
        $jersey = $this->convertStockToString($jersey);
        $expectedData = array_merge(
            $jersey,
            [
                'created_by' => (User::find($jersey['created_by']))->name,
                'league_id' => (League::find($jersey['league_id']))->name,
                'weight' => $jersey['weight'].' Gram',
                'price' => currencyFormat($jersey['price']),
                'price_nameset' => currencyFormat($jersey['price_nameset']),
                'sold' => $jersey['sold'].' pcs',
            ]
        );

        Livewire::test(ViewJersey::class, ['record' => $expectedData['slug']])
            ->assertFormSet($expectedData);
    }

    private function getPaginationData(
        bool $secondPage = false,
        ?string $name = null,
        ?string $league = null,
    ): Collection {
        $jerseys = Jersey::where('name', 'LIKE', "%$name%")
            ->take($this->paginationCount);

        if ($league) {
            $jerseys->hasLeague($league);
        }

        if ($secondPage) {
            $jerseys->skip($this->paginationCount);
        }

        return $jerseys
            ->orderBy('sold', 'desc')
            ->get();
    }

    private function splitStock(array $data): array
    {
        foreach ($data['stock'] as $key => $stock) {
            $data[$key] = $stock;
        }

        unset($data['stock']);

        return $data;
    }

    private function mergeStock(array $data): array
    {
        $data['stock'] = array_combine(
            Jersey::$sizes,
            [$data['S'], $data['M'], $data['L'], $data['XL']]
        );

        $data['stock'] = json_encode($data['stock']);

        foreach (Jersey::$sizes as $size) {
            unset($data[$size]);
        }

        return $data;
    }

    private function convertStockToString(array $data): array
    {
        $result = '';

        foreach ($data['stock'] as $key => $stock) {
            $result .= "$key : $stock,  ";
        }
        $result = rtrim($result, ',  ');

        $data['stock'] = $result;

        return $data;
    }
}
