<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponsableAcademicoRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Permitir a todos por ahora (en desarrollo)
    }

    public function rules()
{
    return [
        'nombre' => [
            'required',
            'string',
            'min:2',
            'not_regex:/^\s*$/', 
        ],
        'apellidos' => [
            'required',
            'string',
            'min:2',
            'not_regex:/^\s*$/', 
        ],
        'email' => 'required|email|unique:responsable_academicos,email',
        'telefono' => 'required|string|max:20',
        'area' => 'required|string|max:255',
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
        'email.email' => 'El correo debe ser una dirección válida.',
        'email.unique' => 'Ya existe un responsable con este correo.',
        
        'telefono.required' => 'El teléfono es obligatorio.',
        'area.required' => 'El área es obligatoria.',
    ];
}
}