<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    // public function definition()
    // {
    //     $jenis = ['tunai', 'tunai'];
    //     $tempo = [null, $this->faker->dateTimeThisYear()];
    //     $supplier = [null, mt_rand(1, 2)];
    //     $p = mt_rand(0, 1);
    //     return [
    //         'nama' => 'BEBAN',
    //         'reff' => 'BBN' . $this->faker->slug(),
    //         'faktur' => $this->faker->word(1),
    //         'tanggal' => $this->faker->dateTimeThisYear(),
    //         'tempo' => $tempo[$p],
    //         'perusahaan_id' => $supplier[$p],
    //         'jenis' => $jenis[$p],
    //         'status' => 1,
    //     ];
    // }
    public function definition()
    {
        $jenis = ['tunai', 'tunai'];
        $tempo = [null, $this->faker->dateTimeThisYear()];
        $supplier = [null, mt_rand(1, 2)];
        $p = mt_rand(0, 1);
        return [
            'nama' => 'PENERIMAAN',
            'reff' => 'TRM' . $this->faker->slug(),
            'faktur' => $this->faker->word(1),
            'tanggal' => $this->faker->dateTimeThisYear(),
            'tempo' => $tempo[$p],
            'customer_id' => $supplier[$p],
            'jenis' => $jenis[$p],
            'status' => 1,
        ];
    }
    // public function definition()
    // {
    //     $jenis = ['tunai', 'hutang'];
    //     $tempo = [null, $this->faker->dateTimeThisYear()];
    //     $supplier = [null, mt_rand(1, 2)];
    //     $p = mt_rand(0, 1);
    //     return [
    //         'nama' => 'PEMBELIAN',
    //         'reff' => 'PBL' . $this->faker->slug(),
    //         'faktur' => $this->faker->word(1),
    //         'tanggal' => $this->faker->dateTimeThisYear(),
    //         'tempo' => $tempo[$p],
    //         'perusahaan_id' => $supplier[$p],
    //         'jenis' => $jenis[$p],
    //         'status' => 1,
    //     ];
    // }
    // public function definition()
    // {
    //     $jenis = ['tunai', 'piutang', 'tunai'];
    //     $tempo = [null, $this->faker->dateTimeThisYear(), null];
    //     $dokter = [mt_rand(1, 2), null, null];
    //     $customer = [null, mt_rand(1, 2), null];
    //     $p = mt_rand(0, 2);
    //     return [
    //         'nama' => 'PENJUALAN',
    //         'reff' => 'PJL' . $this->faker->slug(),
    //         'faktur' => $this->faker->word(),
    //         'tanggal' => $this->faker->dateTimeThisYear(),
    //         'tempo' => $tempo[$p],
    //         'dokter_id' => $dokter[$p],
    //         'customer_id' => $customer[$p],
    //         'jenis' => $jenis[$p],
    //         'status' => 1,
    //     ];
    // }
}
