<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prize>
 */
class PrizeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(2, true),
            'description' => null,
            'is_miss' => false,
        ];
    }

    public function miss(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'ハズレ',
            'is_miss' => true,
        ]);
    }

    public function takenBy(Participant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $participant->event_id,
            'participant_id' => $participant->id,
            'acquired_at' => now(),
        ]);
    }
}
