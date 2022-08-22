<?php

namespace Database\Seeders;

use App\Models\Beban;
use App\Models\BebanTransaction;
use App\Models\Customer;
use App\Models\DetailTransaction;
use App\Models\Dokter;
use App\Models\Kategori;
use App\Models\Merk;
use App\Models\Perusahaan;
use App\Models\Product;
use App\Models\Rak;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\Transaction;
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
            'role' => 'root',
            'password' => bcrypt('123456789')
        ]);
        User::create([
            'name' => 'kasir',
            'email' => 'kasir@app.com',
            'role' => 'kasir',
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
            'perusahaan_id' => 1,
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
            'expired' => '2022/08/31',
            'barcode' => 212323231,
            'nama' => 'Paracetamol',
            'merk_id' => 1,
            'satuan_id' => 1,
            'harga_beli' => 5500,
            'harga_jual_umum' => 8000,
            'harga_jual_resep' => 9000,
            'harga_jual_cust' => 9500,
            'stok_awal' => 12,
            'rak_id' => 1,
            'kategori_id' => 1
        ]);
        Merk::create([
            'nama' => 'KALBE'
        ]);
        Beban::create([
            'nama' => 'KALBE'
        ]);
        Customer::create([
            'nama' => 'Suhar',
            'alamat' => 'Jl. mana yang akan kau pilih',
            'kontak' => '00904293040',
            'saldo_awal_piutang' => 100000
        ]);
        Transaction::create([
            'nama' => 'di isi nama',
            'tanggal' => '2020/08/12',
            'total' => 70000,
            'ongkir' => 0,
            'potongan' => 0,
            'bayar' => 100000,
            'kembali' => 30000,
            'status' => 0,
        ]);
        DetailTransaction::create([
            'transaction_id' => 1,
            'product_id' => 1,
            'qty' => 3,
            'harga' => 10000,
            'sub_total' => 30000
        ]);
        BebanTransaction::create([
            'transaction_id' => 1,
            'beban_id' => 1,
            'sub_total' => 10000,
            'keterangan' => 'seeder jadi ya gitu lah'
        ]);
        Perusahaan::create([
            'nama' => 'PT. KALBE'
        ]);
    }
}
