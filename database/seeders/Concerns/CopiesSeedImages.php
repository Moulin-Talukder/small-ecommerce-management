<?php

namespace Database\Seeders\Concerns;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Copies a placeholder image committed under database/seeders/images into the
 * "public" disk. No network access is needed to seed the demo data.
 */
trait CopiesSeedImages
{
    /**
     * @param  string  $source  path relative to database/seeders/images, e.g. "products/mug.jpg"
     * @return string path on the public disk, e.g. "products/mug.jpg"
     */
    protected function copySeedImage(string $source): string
    {
        $absolute = database_path('seeders/images/'.$source);

        if (! is_file($absolute)) {
            throw new RuntimeException("Seed image not found: {$absolute}");
        }

        return Storage::disk('public')->putFileAs(
            dirname($source),
            new File($absolute),
            basename($source),
        );
    }
}
