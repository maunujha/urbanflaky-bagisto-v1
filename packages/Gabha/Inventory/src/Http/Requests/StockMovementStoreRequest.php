<?php

declare(strict_types=1);

namespace Gabha\Inventory\Http\Requests;

use Gabha\Inventory\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a manually-created stock movement.
 *
 * Only the manual movement types are allowed here (PURCHASE is created through
 * the Purchase module). The "would this go negative?" rule is enforced
 * authoritatively in the service under a row lock.
 */
class StockMovementStoreRequest extends FormRequest
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
        $allowedTypes = array_map(fn (MovementType $type) => $type->value, MovementType::manualCases());

        return [
            'product_variant_id' => ['required', 'integer', 'exists:products,id'],
            'movement_type'      => ['required', Rule::in($allowedTypes)],
            'quantity'           => ['required', 'integer', 'min:1'],
            'notes'              => ['nullable', 'string', 'max:2000'],
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
            'product_variant_id' => trans('inventory::app.admin.movements.create.product-variant'),
            'movement_type'      => trans('inventory::app.admin.movements.create.movement-type'),
            'quantity'           => trans('inventory::app.admin.movements.create.quantity'),
        ];
    }
}
