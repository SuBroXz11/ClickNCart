<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'address' => $this->faker->address(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => $this->faker->randomElement(['admin', 'vendor', 'customer']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'business_name' => $this->faker->company(),
            'tax_id' => $this->faker->bothify('TAX-####-####'),
            'profile_picture' => $this->faker->imageUrl(200, 200, 'people', true, 'Profile'), // you can change to null if not needed
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}