<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AllowedDomain>
 */
class AllowedDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userId = User::query()->value('id') ?? User::factory()->create()->id;

        return [
            'domain' => $this->faker->domainName(),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }
}
