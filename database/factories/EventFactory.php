<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'max_prizes_per_participant' => null,
            'count_misses_toward_limit' => false,
            // テストでは、ふだんは開催予定が入っている（予定なしのテストでは withoutSchedule() を使う）
            'scheduled_start_at' => now()->addDay()->setTime(10, 0),
            'scheduled_minutes' => 120,
        ];
    }

    public function withoutSchedule(): static
    {
        return $this->state(fn (array $attributes) => ['scheduled_start_at' => null, 'scheduled_minutes' => null]);
    }

    /**
     * 1人あたりの獲得上限を設ける。ハズレも数に含めるかどうかも指定できる。
     */
    public function withPrizeLimit(int $limit, bool $countMisses = false): static
    {
        return $this->state(fn (array $attributes) => [
            'max_prizes_per_participant' => $limit,
            'count_misses_toward_limit' => $countMisses,
        ]);
    }
}
