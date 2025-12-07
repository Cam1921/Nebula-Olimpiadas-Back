<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreResponsableAcademicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'apellidos' => ['required', 'string', 'min:2', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'email' => ['required', 'email:rfc,dns', 'max:70', 'unique:users,email'],
            'telefono' => ['required', 'string', 'size:8', 'regex:/^[67]\d{7}$/', 'unique:persona,telefono'],
            'ci' => ['required', 'string', 'min:6', 'max:10', 'regex:/^\d{6,10}$/', 'unique:persona,ci'],
            'id_area' => ['required', 'integer', 'exists:area,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',

            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido (ej. nombre@dominio.com).',
            'email.unique' => 'Este correo ya está registrado.',
            'email.max' => 'El correo no debe exceder los 70 caracteres.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener solo dígitos.',
            'telefono.unique' => 'Este número de teléfono ya está registrado.',

            'ci.required' => 'El CI es obligatorio.',
            'ci.min' => 'El CI debe tener al menos 6 dígitos.',
            'ci.max' => 'El CI no debe exceder los 10 dígitos.',
            'ci.regex' => 'El CI debe contener solo dígitos y tener entre 6 y 10 caracteres.',
            'ci.unique' => 'Este CI ya está registrado.',

            'id_area.required' => 'Cada asignación debe tener un área.',
            'id_area.integer' => 'El ID del área debe ser un número.',
            'id_area.exists' => 'El área seleccionada no existe.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['status' => 'error', 'message' => 'Error de validación', 'errors' => $validator->errors()], 422)
        );
    }
}