<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'flavors' => ['required', 'array', 'min:1', 'max:12'],
            'flavors.*' => ['integer', 'min:0', 'max:12'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:'.config('rolobox.cart.max_quantity_per_line')],
            'gift_message' => ['nullable', 'string', 'max:200'],
            'go_to_cart' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'flavors' => 'sabores',
            'gift_message' => 'mensaje de regalo',
            'quantity' => 'cantidad',
        ];
    }
}
