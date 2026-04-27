<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users', 'alpha_dash'],
            'email'    => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function actualizarPerfil(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:255'],
            'username'              => ['sometimes', 'string', 'max:50', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email'                 => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'password'              => ['sometimes', 'confirmed', Password::min(8)],
            'password_actual'       => ['required_with:password', 'string'],
        ]);

        // Si quiere cambiar la contraseña, verificar la actual
        if (isset($validated['password'])) {
            if (!Hash::check($validated['password_actual'], $user->password)) {
                return response()->json(['message' => 'La contraseña actual no es correcta'], 422);
            }
            $validated['password'] = Hash::make($validated['password']);
        }

        unset($validated['password_actual']);
        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user->fresh(),
        ]);
    }
}
