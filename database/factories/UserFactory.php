<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kobapp_user_id' => (string) fake()->unique()->numberBetween(1, 999999999),
            'name' => fake()->unique()->bothify('user####??'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * テストでは人数の上限にかからないよう、ふだんは運営者にしておく（上限のテストでは free() / monitor() を使う）。
     * plan は一括代入できない項目なので、作ったあとに入れる。
     */
    public function configure(): static
    {
        return $this->afterMaking(fn (User $user) => $user->forceFill(['plan' => 'admin']));
    }

    public function free(): static
    {
        return $this->afterMaking(fn (User $user) => $user->forceFill(['plan' => 'free']));
    }

    public function monitor(): static
    {
        return $this->afterMaking(fn (User $user) => $user->forceFill(['plan' => 'monitor']));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
