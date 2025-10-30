<?php
namespace App\Repositories;

use App\Models\Invitacion;
use Illuminate\Support\Collection;


class InvitacionRepository
{


    public function all(): Collection
    {
        return Invitacion::select(
            'id',
            'nombres',
            'apellidos',
            'email',
            'rol',
            'estado',
            'usado',
            'created_at'
        )
            ->orderByDesc('created_at')
            ->get();
    }
    public function create($data): Invitacion
    {
        return Invitacion::create($data);
    }
    public function findByEmail($email)
    {
        return Invitacion::where('email', $email)->first();
    }
    public function findById($id)
    {
        return Invitacion::where('id', $id)->first();
    }
    public function findByToken($token)
    {
        return Invitacion::where('token', $token)->first();
    }

    public function delete($invitacion)
    {
        $invitacion->delete();
    }
    public function resumen()
    {
        return [
            'total' => Invitacion::count(),
            'enviadas_correctamente' => Invitacion::where('estado', 'Confirmado')->count(),
            'pendientes' => Invitacion::where('estado', 'Pendiente')->count(),
            'fallidas' => Invitacion::where('estado', 'Rebotado')->count(),
        ];
    }
}