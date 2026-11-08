<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Support\ImageUploader;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/** Creates/updates per-shop listings (ShopProduct) and their custom thumbnails. */
class ListingService
{
    public const THUMBNAIL_DIRECTORY = 'shop-products';

    public function __construct(private readonly ImageUploader $images) {}

    /**
     * @throws ValidationException when the product is already in the shop
     *                             (race condition past the validation rule)
     */
    public function add(Shop $shop, Product $product, array $data, ?UploadedFile $thumbnail = null): ShopProduct
    {
        $path = $thumbnail ? $this->images->store($thumbnail, self::THUMBNAIL_DIRECTORY) : null;

        try {
            return $shop->shopProducts()->create([
                ...$data,
                'product_id' => $product->id,
                'custom_thumbnail' => $path,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->images->delete($path);

            throw ValidationException::withMessages([
                'product_id' => "This product is already in {$shop->name}.",
            ]);
        }
    }

    public function update(ShopProduct $listing, array $data, ?UploadedFile $thumbnail, bool $removeThumbnail = false): ShopProduct
    {
        $data['custom_thumbnail'] = $this->images->replace(
            $thumbnail,
            $listing->custom_thumbnail,
            self::THUMBNAIL_DIRECTORY,
            $removeThumbnail,
        );

        $listing->update($data);

        return $listing;
    }

    public function remove(ShopProduct $listing): void
    {
        $path = $listing->custom_thumbnail;

        $listing->delete();

        $this->images->delete($path);
    }
}
