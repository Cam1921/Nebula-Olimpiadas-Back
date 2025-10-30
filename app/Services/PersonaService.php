<?php

namespace App\Services;

use App\Mail\SendInviteEmail;
use App\Repositories\InvitacionRepository;
use App\Repositories\PersonaRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PersonaService
{
    use ApiResponseTrait;

    protected $personaRepository;
    protected $invitacionRepository;

    public function __construct(PersonaRepository $personaRepository, InvitacionRepository $invitacionRepository)
    {
        $this->personaRepository = $personaRepository;
        $this->invitacionRepository = $invitacionRepository;
    }

    public function enviarCorreoCreacionPassword($id)
    {
        $usuario = $this->personaRepository->getById($id);

        // 🔹 Validar existencia del usuario
        if (!$usuario) {
            return $this->errorResponse(
                errorType: 'NOT_FOUND',
                message: 'El usuario no existe.',
                code: 404
            );
        }

        // 🔹 Validar correo electrónico
        $email = $usuario->email;
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse(
                errorType: 'INVALID_EMAIL',
                message: 'El correo electrónico no es válido.',
                code: 400
            );
        }



        try {
            $token = Str::random(64);
            $rol = $usuario->rols->first();
            $data = [
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'rol' => $rol ? $rol->nombre : 'Sin rol',
                'token' => $token,
                'token_expira_en' => now()->addHours(48),
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(
                errorType: 'INTERNAL_ERROR',
                message: 'Error interno del servidor.',
                errors: [$e->getMessage()],
            );
        }
        // 🔹 Generar token y preparar datos


        // 🔹 Guardar la invitación
        try {
            // 🔹 Intentar crear la invitación
            $invitacion = $this->invitacionRepository->create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🔹 Detectar error por duplicado de email
            if ($e->getCode() == '23000') { // Código SQL para violación de restricción UNIQUE
                return $this->errorResponse(
                    errorType: 'DUPLICATE_EMAIL',
                    message: 'Ya existe una invitación para este correo electrónico.',
                    code: 409 // 409 = Conflict
                );
            }

            // 🔹 Cualquier otro error de base de datos
            return $this->errorResponse(
                errorType: 'DB_ERROR',
                message: 'Error al registrar la invitación.',
                errors: [$e->getMessage()],
                code: 500
            );
        }

        // 🔹 Intentar enviar el correo
        try {
            Mail::to($usuario->email)->send(new SendInviteEmail($usuario, $token));

            return $this->successResponse(
                message: 'Correo enviado correctamente.',
                data: ['invitacion' => $invitacion],
                code: 200
            );
        } catch (\Exception $e) {
            // 🔹 Registrar el error y retornar respuesta estandarizada
            Log::error('Error al enviar el correo: ' . $e->getMessage());

            return $this->errorResponse(
                errorType: 'MAIL_ERROR',
                message: 'Error al enviar el correo.',
                errors: [$e->getMessage()],
                code: 500
            );
        }
    }
    public function reenviarCorreoInvitacion($id)
    {


        try {
            // 🔹 Buscar si ya existe una invitación
            $invitacionExistente = $this->invitacionRepository->findById($id);

            if (!$invitacionExistente) {
                return $this->errorResponse(
                    errorType: 'NOT_FOUND',
                    message: 'No existe una invitación previa para este usuario.',
                    code: 404
                );
            }
            $usuario = $this->personaRepository->getByCorreo($invitacionExistente->email);

            // 🔹 Validar existencia del usuario
            if (!$usuario) {
                return $this->errorResponse(
                    errorType: 'NOT_FOUND',
                    message: 'El usuario no existe.',
                    code: 404
                );
            }

            $email = $usuario->email;
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->errorResponse(
                    errorType: 'INVALID_EMAIL',
                    message: 'El correo electrónico no es válido.',
                    code: 400
                );
            }
            // 🔹 Generar nuevo token y actualizar la invitación
            $nuevoToken = Str::random(64);
            $invitacionExistente->token = $nuevoToken;
            $invitacionExistente->token_expira_en = now()->addHours(48);
            $invitacionExistente->save();

            // 🔹 Enviar correo de nuevo
            Mail::to($email)->send(new SendInviteEmail($usuario, $nuevoToken));

            return $this->successResponse(
                message: 'Correo reenviado correctamente.',
                data: ['invitacion' => $invitacionExistente],
                code: 200
            );
        } catch (\Exception $e) {
            Log::error('Error al reenviar el correo: ' . $e->getMessage());

            return $this->errorResponse(
                errorType: 'MAIL_ERROR',
                message: 'Error al reenviar el correo.',
                errors: [$e->getMessage()],
                code: 500
            );
        }
    }
    public function listarNotificaciones()
    {
        $response = $this->invitacionRepository->all();
        $resumen = $this->invitacionRepository->resumen();
        if ($response->isEmpty()) {
            return $this->errorResponse(
                errorType: 'NOT_FOUND',
                message: 'No existen invitaciones.',
                code: 404
            );
        }
        return $this->successResponse(
            meta: [$resumen],
            message: 'Invitaciones listadas correctamente.',
            data: [$response],
            code: 200
        );
    }

}
