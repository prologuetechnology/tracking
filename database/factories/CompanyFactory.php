<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'name' => $this->faker->company(),
            'website' => 'https://'.$this->faker->unique()->domainName(),
            'phone' => $this->faker->numerify('555-###-####'),
            'email' => $this->faker->unique()->companyEmail(),
            'pipeline_company_id' => $this->faker->unique()->numberBetween(1000, 9999),
            'logo_image_id' => null,
            'banner_image_id' => null,
            'footer_image_id' => null,
            'theme_id' => Theme::factory(),
            'enable_map' => false,
            'enable_documents' => false,
            'requires_brand' => false,
            'brand' => null,
        ];
    }

    public function branded(?string $brand = null): static
    {
        return $this->state(fn (): array => [
            'requires_brand' => true,
            'brand' => $brand ?? strtoupper($this->faker->lexify('????')),
        ]);
    }
}
