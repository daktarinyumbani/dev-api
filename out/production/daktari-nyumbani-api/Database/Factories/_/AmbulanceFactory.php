<?php

namespace Database\Factories;

use App\Models\Ambulance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmbulanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ambulance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'company_name' => $this->faker->company(),
            'reg_number' => $this->faker->ean8(),
            'current_hospital' => $this->faker->company(),
            'bio' => $this->faker->realTextBetween(20, 40),
            'address' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(-6.8, -6.16),
            'longitude' => $this->faker->latitude(39.2833333333, 35.75),
            'cost' => $this->faker->numberBetween(10000, 120000)
        ];
    }
}
