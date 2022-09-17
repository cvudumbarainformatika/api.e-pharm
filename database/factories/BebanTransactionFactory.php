<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BebanTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'beban_id' => mt_rand(1, 7),
            'sub_total' => mt_rand(1, 100) * 10000,
            'keterangan' => $this->faker->sentences(3, true)
        ];
    }
}
