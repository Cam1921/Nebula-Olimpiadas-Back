<?php
namespace App\Http\Controllers;
use App\Models\Rol;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'ci' => 'required|string|unique:persona',
                'nombres' => 'required|string',
                'apellidos' => 'required|string',
                'telefono' => 'required|string|size:8|regex:/^[67]\d{7}$/',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => "{$request->nombres} {$request->apellidos}",
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
            $persona = $user->personas()->create([
                'ci' => $request->ci,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'telefono' => $request->telefono,
                'email' => $request->email,
            ]);
            $rol = Rol::where('nombre', 'administrador')->first();
            $persona->rols()->attach($rol);
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();
            return response()->json([
                'user' => $user,
                'token' => $token,
                'rol' => ['administrador'],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar el usuario', 'error' => $e->getMessage()], 500);
        }

    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = Auth::user()->load('personas.rols');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'rol' => $user->personas
                ->flatMap(fn($p) => $p->rols->pluck('nombre'))
                ->unique()
                ->values(),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }
    public function me(Request $request)
    {
        $user = $request->user()->load('personas.rols');

        return response()->json([
            'user' => $user,
            'rol' => $user->personas
                ->flatMap(fn($p) => $p->rols->pluck('nombre'))
                ->unique()
                ->values(),
        ]);
    }
}

