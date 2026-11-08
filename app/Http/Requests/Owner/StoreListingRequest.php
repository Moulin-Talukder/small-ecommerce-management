<?php

namespace App\Http\Requests\Owner;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Add a catalog product to one of the owner's shops. */
class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageListings', $this->shop());
    }

    public function shop(): Shop
    {
        return $this->route('shop');
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required', 'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                // Friendly error instead of hitting the UNIQUE(shop_id, product_id) index.
                Rule::unique('shop_products', 'product_id')->where('shop_id', $this->shop()->id),
            ],
            ...ListingRules::overrides(),
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'This product is already in '.$this->shop()->name.'.',
            'product_id.exists' => 'This product is no longer available in the catalog.',
        ];
    }

    public function attributes(): array
    {
        return ListingRules::attributes();
    }
}
