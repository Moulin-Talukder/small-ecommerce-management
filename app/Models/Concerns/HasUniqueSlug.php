<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Generates a unique `slug` from `name` on create and whenever the name changes.
 *
 * Soft-deleted rows still occupy their slug in the unique index, so the
 * uniqueness check includes trashed records: creating "T-Shirt" while a
 * trashed "T-Shirt" exists yields "t-shirt-2" instead of a constraint error.
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model) {
            if (blank($model->slug) || ($model->isDirty('name') && ! $model->isDirty('slug'))) {
                $model->slug = $model->generateUniqueSlug((string) $model->name);
            }
        });
    }

    public function generateUniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: Str::lower(class_basename($this));
        $base = Str::limit($base, 240, '');

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }
}
