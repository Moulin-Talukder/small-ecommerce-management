<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores images on the "public" disk and cleans up files that are replaced
 * or removed, so no orphaned uploads are left behind.
 */
class ImageUploader
{
    public const DISK = 'public';

    public const MAX_KILOBYTES = 2048;

    /** Validation rules shared by every image upload field. */
    public static function rules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KILOBYTES];
    }

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    /**
     * Returns the path to save on the model:
     *  - new file uploaded → stored, old file deleted
     *  - $remove is true   → old file deleted, null returned
     *  - otherwise         → old path kept
     */
    public function replace(?UploadedFile $file, ?string $oldPath, string $directory, bool $remove = false): ?string
    {
        if ($file) {
            $newPath = $this->store($file, $directory);
            $this->delete($oldPath);

            return $newPath;
        }

        if ($remove) {
            $this->delete($oldPath);

            return null;
        }

        return $oldPath;
    }

    public function delete(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }
}
