<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponsableAcademicoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => ['required', 'string', 'min:2', 'not_regex:/^\s*$/'],
            'apellidos' => ['required', 'string', 'min:2', 'not_regex:/^\s*$/'],
            'email' => ['required', 'email:rfc', 'unique:responsable_academicos,email', 'max:255'],
            'telefono' => ['required', 'string', 'size:8', 'regex:/^[67]\d{7}$/'],
            'area' => ['required', 'string', 'max:255', 'unique:responsable_academicos,area'],
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.not_regex' => 'El nombre no puede estar vacío o contener solo espacios.',

            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.min' => 'Los apellidos deben tener al menos 2 caracteres.',
            'apellidos.not_regex' => 'Los apellidos no pueden estar vacíos o contener solo espacios.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido (ej. nombre@dominio.com).',
            'email.unique' => 'Ya existe un responsable académico con este correo.',
            'email.max' => 'El correo no debe exceder los 255 caracteres.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'telefono.regex' => 'El teléfono debe comenzar con 6 o 7 y contener solo dígitos (ej. 71234567).',

            'area.unique' => 'Ya existe un responsable asignado a esta área.',
        ];
    }
}