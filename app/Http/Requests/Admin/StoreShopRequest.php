<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\Shop;
use App\Support\ImageUploader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Shop::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', Role::ShopOwner->value)],
            'is_active' => ['required', 'boolean'],
            'logo' => ImageUploader::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'owner_id.exists' => 'Please choose a valid shop owner.',
        ];
    }

    public function attributes(): array
    {
        return ['owner_id' => 'shop owner'];
    }
}
