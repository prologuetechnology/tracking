<?php

namespace Database\Factories;

use App\Models\ImageType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));

        return [
            'name' => $name,
            'image_type_id' => ImageType::factory(),
            'file_path' => 'images/'.Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999).'.png',
        ];
    }
}
