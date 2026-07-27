<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey as JerseyModel;
use App\Models\League as LeagueModel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Jersey extends Component
{
    use WithPagination;

    public string $title = '';

    public ?string $leagueSelected = null;

    public ?string $brandSelected = null;

    public ?string $categorySelected = null;

    public string $name = '';

    /** @var array<string, string> */
    public array $allLeagues = [];

    /** @var array<string, string> */
    public array $allBrands = [];

    /** @var array<string, string> */
    public array $allCategories = [];

    public static int $paginationCount = 32;

    public function mount(Request $request): void
    {
        $this->allLeagues = LeagueModel::query()->orderBy('name')->pluck('name', 'slug')->toArray();
        $this->allBrands = Brand::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name', 'slug')->toArray();
        $this->allCategories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name', 'slug')->toArray();

        $this->leagueSelected = $this->validatedSlug(LeagueModel::class, (string) $request->query('name', ''));
        $this->brandSelected = $this->validatedSlug(Brand::class, (string) $request->query('brand', ''));
        $this->categorySelected = $this->validatedSlug(Category::class, (string) $request->query('category', ''));
        $this->refreshTitle();
    }

    public function updatingName(): void
    {
        $this->resetPage();
    }

    public function updatingLeagueSelected(): void
    {
        $this->resetPage();
    }

    public function updatingBrandSelected(): void
    {
        $this->resetPage();
    }

    public function updatingCategorySelected(): void
    {
        $this->resetPage();
    }

    public function updatedLeagueSelected(): void
    {
        $this->refreshTitle();
    }

    public function updatedBrandSelected(): void
    {
        $this->refreshTitle();
    }

    public function updatedCategorySelected(): void
    {
        $this->refreshTitle();
    }

    public function render(): View
    {
        $search = trim($this->name);
        $jerseys = JerseyModel::query()
            ->active()
            ->with(['league', 'brand', 'category'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($this->leagueSelected, fn ($query, string $slug) => $query->hasLeague($slug))
            ->when($this->brandSelected, fn ($query, string $slug) => $query->hasBrand($slug))
            ->when($this->categorySelected, fn ($query, string $slug) => $query->hasCategory($slug))
            ->orderByDesc('is_featured')
            ->orderByDesc('sold')
            ->paginate(self::$paginationCount);

        return view('livewire.jersey', compact('jerseys'));
    }

    private function validatedSlug(string $model, string $slug): ?string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return $model::query()->where('slug', $slug)->exists() ? $slug : null;
    }

    private function refreshTitle(): void
    {
        $this->title = (string) (
            Brand::query()->where('slug', $this->brandSelected)->value('name')
            ?? Category::query()->where('slug', $this->categorySelected)->value('name')
            ?? LeagueModel::query()->where('slug', $this->leagueSelected)->value('name')
            ?? ''
        );
    }
}
