<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;

/**
 * Create/update logic for shared-catalog products, used by both the admin
 * and shop-owner controllers so image handling stays in one place.
 */
class CatalogProductService
{
    public const THUMBNAIL_DIRECTORY = 'products';

    public function __construct(private readonly ImageUploader $images) {}

    public function create(array $data, ?UploadedFile $thumbnail, User $creator): Product
    {
        if ($thumbnail) {
            $data['thumbnail'] = $this->images->store($thumbnail, self::THUMBNAIL_DIRECTORY);
        }

        $product = new Product($data);
        $product->created_by = $creator->id;
        $product->save();

        return $product;
    }

    public function update(Product $product, array $data, ?UploadedFile $thumbnail, bool $removeThumbnail = false): Product
    {
        $data['thumbnail'] = $this->images->replace(
            $thumbnail,
            $product->thumbnail,
            self::THUMBNAIL_DIRECTORY,
            $removeThumbnail,
        );

        $product->update($data);

        return $product;
    }
}
