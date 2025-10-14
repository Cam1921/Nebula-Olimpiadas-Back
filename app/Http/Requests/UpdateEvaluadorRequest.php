<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEvaluadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id'); // obtener el id del evaluador
        $personaId = $id;

        return [
            'nombre' => ['required', 'string', 'min:2', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'apellidos' => ['required', 'string', 'min:2', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
            'ci' => ['required', 'string', 'min:6', 'max:10', 'regex:/^\d{6,10}$/', "unique:persona,ci,{$personaId}"],
            'telefono' => ['required', 'string', 'size:8', 'regex:/^[67]\d{7}$/', "unique:persona,telefono,{$personaId}"],
            'email' => ['required', 'email:rfc,dns', "unique:users,email,{$this->user_id}"], // user_id se pasará desde el controlador
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
            'ci.min' => 'El CI debe tener al menos 6 dígitos.',
            'ci.max' => 'El CI no debe exceder los 10 dígitos.',
            'ci.regex' => 'El CI debe contener solo dígitos.',
            'ci.unique' => 'Este CI ya está registrado.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener solo dígitos.',
            'telefono.unique' => 'Este número de teléfono ya está registrado.',

            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'Este correo ya está registrado.',

            'asignaciones.required' => 'Debe asignar al menos un área y nivel.',
            'asignaciones.array' => 'Las asignaciones deben enviarse en formato de lista.',
            'asignaciones.*.id_area.required' => 'El área es obligatoria en cada asignación.',
            'asignaciones.*.id_area.exists' => 'El área seleccionada no existe.',
            'asignaciones.*.id_nivel.exists' => 'El nivel seleccionado no existe.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
