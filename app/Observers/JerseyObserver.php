<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Jersey;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JerseyObserver
{
    public function updated(Jersey $jersey): void
    {
        if (! $jersey->wasChanged('image')) {
            return;
        }

        $this->deleteAfterCommitUnlessReferencedByHistory($jersey->getOriginal('image'));
    }

    public function forceDeleted(Jersey $jersey): void
    {
        $this->deleteAfterCommitUnlessReferencedByHistory($jersey->image);
    }

    private function deleteAfterCommitUnlessReferencedByHistory(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        DB::afterCommit(static function () use ($path): void {
            $isHistoricalSnapshot = OrderItem::query()
                ->where('image_path', $path)
                ->whereHas('order', fn (Builder $query): Builder => $query->where('status', '!=', Order::STATUS_IN_CART))
                ->exists();

            if (! $isHistoricalSnapshot) {
                Storage::disk('public')->delete($path);
            }
        });
    }
}
