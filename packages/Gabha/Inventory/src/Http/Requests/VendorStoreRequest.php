<?php

declare(strict_types=1);

namespace Gabha\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a vendor.
 */
class VendorStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'mobile'  => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/', 'unique:vendors,mobile'],
            'address' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Human-friendly attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'    => trans('inventory::app.admin.vendors.create.name'),
            'mobile'  => trans('inventory::app.admin.vendors.create.mobile'),
            'address' => trans('inventory::app.admin.vendors.create.address'),
        ];
    }
}
