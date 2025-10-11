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
            'correo' => ['required', 'email:rfc,dns', 'unique:responsable_academicos,correo'],
            'telefono' => ['required', 'string', 'size:8', 'regex:/^[67]\d{7}$/', 'unique:responsable_academicos,telefono'],
            'ci' => ['required', 'string', 'min:6', 'max:10', 'regex:/^\d{6,10}$/', 'unique:responsable_academicos,ci'],
            'area' => ['required', 'string', 'max:255', 'unique:responsable_academicos,area'],
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

            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe tener un formato válido (ej. nombre@dominio.com).',
            'correo.unique' => 'Ya existe un responsable con este correo.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener solo dígitos.',
            'telefono.unique' => 'Este número de teléfono ya está registrado.',

            'ci.required' => 'El CI es obligatorio.',
            'ci.min' => 'El CI debe tener al menos 6 dígitos.',
            'ci.max' => 'El CI no debe exceder los 10 dígitos.',
            'ci.regex' => 'El CI debe contener solo dígitos y tener entre 6 y 10 caracteres.',
            'ci.unique' => 'Este CI ya está registrado.',

            'area.required' => 'El área es obligatoria.',
            'area.unique' => 'Ya existe un responsable asignado a esta área.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}