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
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(RegistrationRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            event(new Registered($user));

            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->created([
                'user' => $user,
                'token' => $token,
            ], 'Registration successful');
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $request->authenticate();

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
        return $this->executeWithErrorHandling(function () use ($request) {
            $status = $request->sendResetLinkEmail();

            if ($status === Password::RESET_LINK_SENT) {
                return $this->success(null, 'Password reset link sent to your email address');
            }

            return $this->error('Unable to send password reset link', 400);
        });
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request) {
            $status = $request->resetPassword();

            if ($status === Password::PASSWORD_RESET) {
                return $this->success(null, 'Password has been reset successfully');
            }

            $message = match ($status) {
                Password::INVALID_TOKEN => 'Invalid or expired reset token',
                Password::INVALID_USER => 'We could not find a user with that email address',
                default => 'Unable to reset password'
            };

            return $this->error($message, 400);
        });
    }
}
