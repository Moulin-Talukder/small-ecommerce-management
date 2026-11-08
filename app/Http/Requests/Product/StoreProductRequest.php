<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Support\ImageUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Catalog product fields, shared by the admin and shop-owner screens. */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        // Empty SKU means "no SKU" (NULL), so several products may omit it.
        $this->merge(['sku' => filled($this->sku) ? trim($this->sku) : null]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            // The DB index also covers soft-deleted rows, so the rule must too (it does by default).
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'thumbnail' => ImageUploader::rules(),
        ];
    }

    public function attributes(): array
    {
        return [
            'base_price' => 'base price',
            'sku' => 'SKU',
        ];
    }

    /** Validated catalog fields only (no files / UI flags). */
    public function productData(): array
    {
        return $this->safe()->only(['name', 'description', 'base_price', 'sku']);
    }
}
