<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEvaluadorRequest extends FormRequest
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
            'ci' => ['required', 'numeric', 'unique:persona,ci'],
            'telefono' => ['required', 'string', 'size:8', 'regex:/^[67]\d{7}$/', 'unique:persona,telefono'],
            'email' => ['required', 'email', 'unique:users,email'],
            'asignaciones' => ['required', 'array', 'min:1'],
            'asignaciones.*.id_area' => ['required', 'integer', 'exists:area,id'],
            'asignaciones.*.id_nivel' => ['nullable', 'integer', 'exists:nivel,id'],
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

            'ci.required' => 'El CI es obligatorio.',
            'ci.numeric' => 'El CI debe ser numérico.',
            'ci.unique' => 'Este CI ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener solo dígitos.',
            'telefono.unique' => 'Este número de teléfono ya está registrado.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'Este correo ya está registrado.',

            'asignaciones.required' => 'Debe asignar al menos una asignación.',
            'asignaciones.array' => 'Las asignaciones deben ser un arreglo.',
            'asignaciones.*.id_area.required' => 'El área es obligatoria para cada asignación.',
            'asignaciones.*.id_area.integer' => 'El ID del área debe ser un número.',
            'asignaciones.*.id_area.exists' => 'El área seleccionada no existe.',
            'asignaciones.*.id_nivel.integer' => 'El ID del nivel debe ser un número.',
            'asignaciones.*.id_nivel.exists' => 'El nivel seleccionado no existe.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
