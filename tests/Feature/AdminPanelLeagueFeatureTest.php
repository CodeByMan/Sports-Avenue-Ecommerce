<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LeagueResource;
use App\Filament\Resources\LeagueResource\Pages\CreateLeague;
use App\Filament\Resources\LeagueResource\Pages\EditLeague;
use App\Filament\Resources\LeagueResource\Pages\ListLeagues;
use App\Filament\Resources\LeagueResource\Pages\ViewLeague;
use App\Models\League;
use App\Models\User;
use Database\Seeders\LeagueSeeder;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelLeagueFeatureTest extends TestCase
{
    private Collection $leagues;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        Storage::fake('public');
        $this->admin = $this->authenticatedUser(['role' => User::ROLE_ADMIN]);
        $this->seed(LeagueSeeder::class);
        League::query()->update(['created_by' => $this->admin->id]);
        $this->leagues = League::query()->get();
    }

    public function test_league_menu_list_can_be_rendered(): void
    {
        $this->get(LeagueResource::getUrl())
            ->assertSuccessful()
            ->assertSee(trans('List of leagues'));
    }

    public function test_league_menu_list_can_show_and_search_records(): void
    {
        $league = $this->leagues->last();

        Livewire::test(ListLeagues::class)
            ->assertCanSeeTableRecords($this->leagues)
            ->searchTable($league->name)
            ->assertCanSeeTableRecords($this->leagues->where('name', $league->name))
            ->assertCanNotSeeTableRecords($this->leagues->where('name', '!=', $league->name));
    }

    public function test_league_menu_create_can_be_rendered_and_create_a_league(): void
    {
        $this->get(LeagueResource::getUrl('create'))
            ->assertSuccessful()
            ->assertSee(trans('Create league'));

        $data = League::factory()->make([
            'name' => 'Championship',
            'slug' => 'championship',
            'country' => 'England',
        ])->only(['name', 'country']);
        $data['image'] = UploadedFile::fake()->image('championship.png');

        Livewire::test(CreateLeague::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leagues', [
            'name' => 'Championship',
            'country' => 'England',
        ]);
    }

    public function test_league_create_validation_is_enforced(): void
    {
        $this->withExceptionHandling();

        Livewire::test(CreateLeague::class)
            ->fillForm([
                'name' => $this->leagues->firstOrFail()->name,
                'country' => 'Pakistan',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique', 'image' => 'required']);
    }

    public function test_league_menu_edit_and_view_can_be_rendered(): void
    {
        $league = $this->leagues->firstOrFail();

        $this->get(LeagueResource::getUrl('edit', ['record' => $league->slug]))
            ->assertSuccessful()
            ->assertSee(trans('Edit league'));
        $this->get(LeagueResource::getUrl('view', ['record' => $league->slug]))
            ->assertSuccessful()
            ->assertSee(trans('Detail of league'));

        Livewire::test(EditLeague::class, ['record' => $league->slug])
            ->assertFormSet(Arr::except($league->toArray(), ['image']));
        $viewData = Arr::except($league->toArray(), ['image']);
        $viewData['created_by'] = $this->admin->name;

        Livewire::test(ViewLeague::class, ['record' => $league->slug])
            ->assertFormSet($viewData);
    }

    public function test_deleting_an_unused_league_removes_it_permanently(): void
    {
        $league = League::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(ListLeagues::class)
            ->callTableAction(DeleteAction::class, $league);

        $this->assertDatabaseMissing('leagues', [
            'id' => $league->id,
        ]);
    }


    public function test_deleting_a_used_league_moves_products_to_the_selected_replacement(): void
    {
        $source = League::factory()->create([
            'name' => 'Source League',
            'created_by' => $this->admin->id,
        ]);
        $replacement = League::factory()->create([
            'name' => 'Replacement League',
            'created_by' => $this->admin->id,
        ]);
        $jersey = \App\Models\Jersey::factory()->create([
            'league_id' => $source->id,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(ListLeagues::class)
            ->callTableAction(DeleteAction::class, $source, data: [
                'replacement_league_id' => $replacement->id,
            ]);

        $this->assertDatabaseMissing('leagues', [
            'id' => $source->id,
        ]);
        $this->assertDatabaseHas('jerseys', [
            'id' => $jersey->id,
            'league_id' => $replacement->id,
        ]);
    }

    public function test_admin_can_edit_and_permanently_delete_an_unused_league_owned_by_staff(): void
    {
        $staff = $this->createUser([
            'role' => User::ROLE_STAFF,
        ]);

        $league = League::factory()->create([
            'created_by' => $staff->id,
        ]);

        $this->get(
            LeagueResource::getUrl('edit', ['record' => $league])
        )->assertSuccessful();

        Livewire::test(ListLeagues::class)
            ->callTableAction(DeleteAction::class, $league);

        $this->assertDatabaseMissing('leagues', [
            'id' => $league->id,
        ]);
    }
}
