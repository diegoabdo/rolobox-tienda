<?php

namespace App\Http\Requests;

use App\Enums\DeliveryType;
use App\Enums\PaymentMethod;
use App\Models\Zone;
use App\Support\DeliverySchedule;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nit = strtoupper(preg_replace('/\s+/', '', (string) $this->input('nit', '')) ?? '');

        $this->merge([
            'phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone'),
            'nit' => $nit === '' ? 'CF' : $nit,
            'promotion_code' => $this->filled('promotion_code') ? mb_strtoupper(trim((string) $this->input('promotion_code'))) : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^502[2-7]\d{7}$/'],
            'email' => ['nullable', 'email', 'max:160'],
            'nit' => ['required', 'string', 'regex:/^(CF|\d{1,12}-?[\dK])$/'],
            'zone_id' => ['required', 'integer', Rule::exists('zones', 'id')->where('active', true)],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'address_reference' => ['nullable', 'string', 'max:255'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_slot' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promotion_code' => ['nullable', 'string', 'max:40'],
            // El efectivo solo existe en ventas en persona.
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->only(PaymentMethod::online())],
            'terms' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('zone_id')) {
                    return;
                }

                $zone = Zone::find($this->integer('zone_id'));
                if ($zone?->delivery_type !== DeliveryType::Local) {
                    return;
                }

                if (! in_array($this->input('delivery_date'), DeliverySchedule::availableDateStrings(), true)) {
                    $validator->errors()->add('delivery_date', 'Elige un día de entrega disponible.');
                }

                if (! array_key_exists((string) $this->input('delivery_slot'), config('rolobox.delivery.slots'))) {
                    $validator->errors()->add('delivery_slot', 'Elige un horario de entrega.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Escribe un número de Guatemala de 8 dígitos.',
            'nit.regex' => 'Escribe un NIT válido o deja CF.',
            'terms.accepted' => 'Para continuar tienes que aceptar los términos y el aviso de privacidad.',
            'zone_id.required' => 'Elige dónde quieres recibir tu pedido.',
            'zone_id.exists' => 'Esa zona ya no está disponible.',
            'payment_method.required' => 'Elige cómo vas a pagar.',
            'payment_method.enum' => 'Elige cómo vas a pagar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'phone' => 'WhatsApp',
            'email' => 'correo',
            'address' => 'dirección',
            'address_reference' => 'referencias',
            'notes' => 'notas',
            'promotion_code' => 'código de promoción',
        ];
    }
}
