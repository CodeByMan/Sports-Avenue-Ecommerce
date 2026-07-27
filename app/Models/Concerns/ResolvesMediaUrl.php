<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesMediaUrl
{
    protected function resolveMediaUrl(?string $path, ?string $directory = null): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return asset('assets/images/no-image.png');
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (file_exists(public_path($normalized))) {
            return asset($normalized);
        }

        if ($directory !== null) {
            $publicCandidate = 'images/'.trim($directory, '/').'/'.basename($normalized);

            if (file_exists(public_path($publicCandidate))) {
                return asset($publicCandidate);
            }
        }

        if (Storage::disk('public')->exists($normalized)) {
            return asset('storage/'.$normalized);
        }

        if ($directory !== null) {
            $storageCandidate = trim($directory, '/').'/'.basename($normalized);

            if (Storage::disk('public')->exists($storageCandidate)) {
                return asset('storage/'.$storageCandidate);
            }
        }

        return asset('assets/images/no-image.png');
    }
}
