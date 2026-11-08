<?php

namespace App\Http\Requests\Owner;

use App\Support\ImageUploader;

/** Per-shop override fields shared by the add and edit listing requests. */
final class ListingRules
{
    public static function overrides(): array
    {
        return [
            // Empty price = follow the catalog base price.
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string', 'max:5000'],
            'custom_thumbnail' => ImageUploader::rules(),
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'custom_name' => 'name',
            'custom_description' => 'description',
            'custom_thumbnail' => 'thumbnail',
        ];
    }
}
