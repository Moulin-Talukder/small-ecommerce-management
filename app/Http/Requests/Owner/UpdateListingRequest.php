<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('shopProduct'));
    }

    public function rules(): array
    {
        return ListingRules::overrides() + [
            'remove_custom_thumbnail' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ListingRules::attributes();
    }
}
