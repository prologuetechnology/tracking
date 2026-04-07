<?php

namespace Database\Factories;

use App\Enums\ImageTypeEnum;
use App\Models\ImageType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<ImageType>
 */
class ImageTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Arr::random(ImageTypeEnum::values()),
        ];
    }
}
