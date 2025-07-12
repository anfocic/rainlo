<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegistrationRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // FormRequest validation handles hashing via 'hashed' cast
        ]);

        event(new Registered($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->authenticate(); // Use the existing authenticate method from LoginRequest

            $user = $request->user();
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->successWithData([
                'user' => $user,
                'token' => $token,
            ], 'Login successful');
        });
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->user()->currentAccessToken()->delete();

            return $this->success(null, 'Logout successful');
        });
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $request->sendResetLinkEmail($request->email);

        return response()->json([
            'message' => 'Password reset email sent',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $request->resetPassword($request->email, $request->password);

        return response()->json([
            'message' => 'Password reset successful',
        ]);
    }
}
