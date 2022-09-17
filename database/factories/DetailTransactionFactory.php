<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DetailTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $price = array(0, 7000, 8000, 9000);
        $id = mt_rand(1, 3);
        return [
            'product_id' => $id,
            'harga' => $price[$id],
            'qty' => mt_rand(1, 30)
        ];
    }
}
