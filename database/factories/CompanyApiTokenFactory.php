<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyApiToken>
 */
class CompanyApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'api_token' => 'token-'.Str::lower(Str::random(24)),
            'bol' => 'BOL'.$this->faker->unique()->numberBetween(10000, 99999),
            'is_valid' => true,
        ];
    }

    public function invalid(): static
    {
        return $this->state(fn (): array => [
            'is_valid' => false,
        ]);
    }
}
