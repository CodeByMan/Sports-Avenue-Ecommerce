<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryObserver
{
    public function updated(Category $category): void
    {
        if (! $category->wasChanged('image')) {
            return;
        }

        $this->deleteAfterCommit($category->getOriginal('image'));
    }

    public function forceDeleted(Category $category): void
    {
        $this->deleteAfterCommit($category->image);
    }

    private function deleteAfterCommit(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        DB::afterCommit(static fn (): bool => Storage::disk('public')->delete($path));
    }
}
