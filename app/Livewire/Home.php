<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Jersey;
use App\Models\League;
use App\Models\StorefrontSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Home extends Component
{
    /** @var Collection<int, League> */
    public Collection $topLeagues;

    /** @var Collection<int, Brand> */
    public Collection $topBrands;

    /** @var Collection<int, Jersey> */
    public Collection $topJerseys;

    public ?StorefrontSetting $storefrontSetting = null;

    public function mount(): void
    {
        $this->storefrontSetting = StorefrontSetting::query()->active()->latest('id')->first();

        $this->topLeagues = League::query()
            ->withCount(['jerseys' => fn ($query) => $query->active()])
            ->orderBy('name')
            ->get();

        $this->topBrands = Brand::query()
            ->active()
            ->withCount(['jerseys' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->topJerseys = Jersey::query()
            ->active()
            ->with(['league', 'brand', 'category'])
            ->orderByDesc('is_featured')
            ->orderByDesc('sold')
            ->limit(4)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.home');
    }
}
