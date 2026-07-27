<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\League as LeagueModel;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class League extends Component
{
    use WithPagination;

    public string $name = '';

    public static int $paginationCount = 12;

    public function updatingName(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $search = trim($this->name);
        $leagues = LeagueModel::query()
            ->withCount('jerseys')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(self::$paginationCount);

        return view('livewire.league', compact('leagues'));
    }
}
