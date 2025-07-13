<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Laravel\Sanctum\Sanctum;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ],
                'timestamp'
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ],
                'timestamp'
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'timestamp'
            ])
            ->assertJson(['message' => 'Logout successful']);
    }

    public function test_registration_validation_works()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_validation_works()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_duplicate_email_registration_fails()
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_access_protected_route_with_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }

    public function test_user_cannot_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_token_is_created_on_successful_login()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertNotNull($token);
        $this->assertIsString($token);
    }

    public function test_token_is_deleted_on_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        // Use the actual token for authentication
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token->plainTextToken
        ]);

        $response->assertStatus(200);

        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    public function test_user_can_request_password_reset()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'john@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'timestamp'
            ])
            ->assertJson(['message' => 'Password reset link sent to your email address']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_validation_works()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_fails_for_nonexistent_user()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'john@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'timestamp'
            ])
            ->assertJson(['message' => 'Password has been reset successfully']);

        // Verify user can login with new password
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'newpassword123'
        ]);

        $loginResponse->assertStatus(200);
    }

    public function test_reset_password_validation_works()
    {
        $response = $this->postJson('/api/reset-password', [
            'email' => 'invalid-email',
            'token' => '',
            'password' => '123',
            'password_confirmation' => '456'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token', 'password']);
    }

    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'john@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
                'timestamp'
            ])
            ->assertJson(['message' => 'Invalid or expired reset token']);
    }
}
