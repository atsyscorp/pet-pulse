<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RequestAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-\s]+$/'],
            'beds' => ['nullable', 'integer', 'min:1', 'max:500'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s().\-]{7,}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'clinic_name' => 'nombre de la clínica',
            'city' => 'ciudad',
            'tax_id' => 'NIT',
            'beds' => 'camas de hospitalización',
            'contact_name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'message' => 'mensaje',
            'consent' => 'autorización',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Complete el campo :attribute.',
            'email' => 'Escriba un correo electrónico válido.',
            'max' => 'El campo :attribute es demasiado largo.',
            'integer' => 'Escriba un número entero en :attribute.',
            'beds.min' => 'Indique al menos 1 cama.',
            'beds.max' => 'Indique un máximo de 500 camas.',
            'tax_id.regex' => 'El NIT solo puede tener números, letras, puntos y guiones.',
            'phone.regex' => 'Escriba un teléfono válido, por ejemplo +57 300 123 4567.',
            'consent.accepted' => 'Debe autorizar el tratamiento de datos para enviar la solicitud.',
        ];
    }
}
