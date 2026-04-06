<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseHue = $this->faker->numberBetween(0, 359);
        $primaryHue = $this->faker->numberBetween(0, 359);
        $accentHue = $this->faker->numberBetween(0, 359);

        return [
            'name' => Str::title($this->faker->unique()->words(2, true)),
            'colors' => [
                'root' => [
                    'background' => "{$baseHue} 70% 99%",
                    'foreground' => "{$baseHue} 90% 8%",
                    'muted' => "{$baseHue} 60% 97%",
                    'mutedForeground' => "{$baseHue} 50% 15%",
                    'popover' => "{$baseHue} 100% 99%",
                    'popoverForeground' => "{$baseHue} 90% 8%",
                    'card' => "{$baseHue} 70% 99%",
                    'cardForeground' => "{$baseHue} 90% 8%",
                    'border' => "{$baseHue} 66% 92%",
                    'input' => "{$baseHue} 66% 92%",
                    'primary' => "{$primaryHue} 70% 42%",
                    'primaryForeground' => "{$primaryHue} 100% 99%",
                    'secondary' => "{$baseHue} 50% 96%",
                    'secondaryForeground' => "{$baseHue} 40% 26%",
                    'accent' => "{$accentHue} 70% 48%",
                    'accentForeground' => "{$accentHue} 50% 96%",
                    'destructive' => '349 75% 58%',
                    'destructiveForeground' => '349 80% 97%',
                    'ring' => "{$baseHue} 85% 80%",
                ],
            ],
            'radius' => '0.5rem',
            'is_system' => false,
            'derive_from' => $this->faker->randomElement(['primary', 'accent']),
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'is_system' => true,
        ]);
    }
}
