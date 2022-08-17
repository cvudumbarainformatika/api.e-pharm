<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Kategori;
use App\Models\Product;
use App\Models\Rak;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        User::create([
            'name' => 'admin',
            'email' => 'admin@app.com',
            'password' => bcrypt('123456789')
        ]);
        Satuan::create([
            'nama' => 'ATM'
        ]);
        Dokter::create([
            'nama' => 'Sugeng',
            'alamat' => 'Jl. kenangan yang sulit untuk dilupakan',
            'kontak' => '0976765476834762'
        ]);
        Supplier::create([
            'nama' => 'Agung Podomoro',
            'alamat' => 'Jl. untuk kembali',
            'perusahaan' => 'PT. Anugrah Indah Abadi',
            'kontak' => '0992839879872',
            'saldo_awal_hutang' => '8976889'
        ]);
        Kategori::create([
            'nama' => 'GENERIK'
        ]);
        Rak::create([
            'nama' => '1 A ATAS'
        ]);
        Product::create([
            'barcode' => 212323231,
            'nama' => 'Paracetamol',
            'merk' => 'Kalbe',
            'satuan_id' => 1,
            'harga_beli' => 5500,
            'harga_jual_umum' => 8000,
            'harga_jual_resep' => 9000,
            'harga_jual_cust' => 9500,
            'stok_awal' => 12,
            'rak_id' => 1,
            'kategori_id' => 1
        ]);
    }
}
