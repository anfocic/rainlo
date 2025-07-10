<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            //logic can be added here tht runs after model creation
        });
      }

    //Static is a new feature in PHP 8.1
    //$this->state() is a method on the Factory class
    //$this refers to the UserFactory instance
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
