<?php

namespace App\Http\Requests\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        return parent::rules() + [
            'remove_thumbnail' => ['sometimes', 'boolean'],
        ];
    }
}
