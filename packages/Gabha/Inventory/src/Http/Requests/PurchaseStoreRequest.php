<?php

declare(strict_types=1);

namespace Gabha\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a purchase.
 *
 * Totals (total_quantity / total_amount) and line totals are intentionally NOT
 * accepted from the client — they are recomputed server-side in the service.
 */
class PurchaseStoreRequest extends FormRequest
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
            'vendor_id'      => ['required', 'integer', 'exists:vendors,id'],
            'purchase_date'  => ['required', 'date', 'before_or_equal:today'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:2000'],

            /*
             * Belt-and-braces file validation: extension (mimes) + real MIME
             * type (mimetypes) + a 4 MB ceiling.
             */
            'bill_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:4096',
            ],

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
            'vendor_id'                  => trans('inventory::app.admin.purchases.create.vendor'),
            'purchase_date'              => trans('inventory::app.admin.purchases.create.purchase-date'),
            'invoice_number'             => trans('inventory::app.admin.purchases.create.invoice-number'),
            'bill_file'                  => trans('inventory::app.admin.purchases.create.bill'),
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
            'items.required'                   => trans('inventory::app.admin.purchases.create.items-required'),
            'items.min'                        => trans('inventory::app.admin.purchases.create.items-required'),
            'items.*.product_variant_id.distinct' => trans('inventory::app.admin.purchases.create.items-distinct'),
        ];
    }
}
