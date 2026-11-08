<?php

namespace App\Http\Requests\Owner;

use App\Http\Requests\Product\StoreProductRequest;
use Illuminate\Validation\Rule;

/** Owner creates a catalog product, optionally listing it in one of their shops. */
class StoreOwnProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'list_in_shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('owner_id', $this->user()->id)],
            'stock' => ['nullable', 'required_with:list_in_shop_id', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'list_in_shop_id.exists' => 'Please choose one of your shops.',
        ];
    }
}
