<?php

declare(strict_types=1);

namespace Gabha\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for appending new line items to an existing purchase.
 *
 * Purchases are an immutable ledger: existing lines, vendor, and dates can't
 * be changed once saved. This only validates the NEW lines being appended.
 */
class PurchaseAddItemsRequest extends FormRequest
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
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'          => ['required', 'numeric', 'min:0'],
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
            'items'                      => trans('inventory::app.admin.purchases.create.products'),
            'items.*.product_variant_id' => trans('inventory::app.admin.purchases.create.product-variant'),
            'items.*.quantity'           => trans('inventory::app.admin.purchases.create.quantity'),
            'items.*.unit_cost'          => trans('inventory::app.admin.purchases.create.unit-cost'),
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required'                      => trans('inventory::app.admin.purchases.create.items-required'),
            'items.min'                            => trans('inventory::app.admin.purchases.create.items-required'),
            'items.*.product_variant_id.distinct'  => trans('inventory::app.admin.purchases.create.items-distinct'),
        ];
    }
}
