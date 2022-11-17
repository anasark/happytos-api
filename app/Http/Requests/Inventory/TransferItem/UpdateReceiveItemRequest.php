<?php

namespace App\Http\Requests\Inventory\TransferItem;

use App\Http\Requests\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiveItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rulesForm = ValidationRule::form();

        $rulesReceiveItem = [
            'warehouse_id' => ValidationRule::foreignKey('warehouses'),
            'from_warehouse_id' => ValidationRule::foreignKey('warehouses'),
            'notes' => 'nullable|string|max:255',
            'items' => 'required_without:services|array',
        ];

        $rulesReceiveItemItems = [
            'items.*.item_id' => ValidationRule::foreignKey('items'),
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit' => ValidationRule::unit(),
            'items.*.converter' => ValidationRule::converter()
        ];

        return array_merge($rulesForm, $rulesReceiveItem, $rulesReceiveItemItems);
    }
}
