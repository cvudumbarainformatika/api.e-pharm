<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DetailPenerimaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'penerimaan_id' => mt_rand(1, 2),
            'sub_total' => mt_rand(1, 100) * 10000,
            'keterangan' => $this->faker->sentences(3, true)

        ];
    }
}
