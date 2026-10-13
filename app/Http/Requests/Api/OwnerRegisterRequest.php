<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class OwnerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:owners,email'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+() \-]+$/'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'accept_terms' => ['accepted'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nombre', 'email' => 'correo electrónico', 'phone' => 'teléfono',
            'password' => 'contraseña', 'accept_terms' => 'política de tratamiento de datos',
            'device_name' => 'nombre del dispositivo',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe una cuenta con este correo. Inicia sesión.',
            'accept_terms.accepted' => 'Debes aceptar la política de tratamiento de datos personales.',
        ];
    }
}
