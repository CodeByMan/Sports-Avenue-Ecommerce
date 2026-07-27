<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\League;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeagueObserver
{
    public function updated(League $league): void
    {
        if (! $league->wasChanged('image')) {
            return;
        }

        $this->deleteAfterCommit($league->getOriginal('image'));
    }

    public function forceDeleted(League $league): void
    {
        $this->deleteAfterCommit($league->image);
    }

    private function deleteAfterCommit(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        DB::afterCommit(static fn (): bool => Storage::disk('public')->delete($path));
    }
}
