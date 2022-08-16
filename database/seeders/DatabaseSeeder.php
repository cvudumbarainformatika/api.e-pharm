<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Kategori;
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
            'name' => 'atm'
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
            'nama' => 'generik'
        ]);
        Rak::create([
            'nama' => '1 A atas'
        ]);
    }
}
