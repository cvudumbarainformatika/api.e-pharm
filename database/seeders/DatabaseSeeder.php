<?php

namespace Database\Seeders;

use App\Models\Beban;
use App\Models\BebanTransaction;
use App\Models\Customer;
use App\Models\DetailPenerimaan;
use App\Models\DetailTransaction;
use App\Models\Dokter;
use App\Models\Kategori;
use App\Models\Merk;
use App\Models\Penerimaan;
use App\Models\Perusahaan;
use App\Models\Product;
use App\Models\Rak;
use App\Models\Satuan;
use App\Models\SatuanBesar;
use App\Models\Setting\Info;
use App\Models\Supplier;
use App\Models\Transaction;
// use App\Models\Transaction::detail_transaction();
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
            'name' => 'root',
            'email' => 'root@app.com',
            'role' => 'root',
            'level' => 1,
            'password' => bcrypt('sekarep123456789')
        ]);
        User::create([
            'name' => 'owner',
            'email' => 'owner@app.com',
            'role' => 'owner',
            'level' => 2,
            'password' => bcrypt('12345')
        ]);
        User::create([
            'name' => 'kasir',
            'email' => 'kasir@app.com',
            'role' => 'kasir',
            'level' => 5,
            'password' => bcrypt('12345')
        ]);
        User::create([
            'name' => 'kasir2',
            'email' => 'kasir2@app.com',
            'role' => 'kasir',
            'level' => 5,
            'password' => bcrypt('12345')
        ]);
        User::create([
            'name' => 'Gudang',
            'email' => 'gudang@app.com',
            'role' => 'gudang',
            'level' => 5,
            'password' => bcrypt('12345')
        ]);
        Satuan::create([
            'nama' => 'PCS'
        ]);
        Satuan::create([
            'nama' => 'KAPLET'
        ]);
        SatuanBesar::create([
            'nama' => 'KARDUS'
        ]);
        SatuanBesar::create([
            'nama' => 'KOTAK'
        ]);
        Dokter::create([
            'nama' => 'Sugeng',
            'alamat' => 'Jl. kenangan yang sulit untuk dilupakan',
            'kontak' => '0976765476834762'
        ]);
        Dokter::create([
            'nama' => 'Handoko',
            'alamat' => 'Jl. kenangan yang menolak untuk dilupakan',
            'kontak' => '09767476834762'
        ]);
        Supplier::create([
            'nama' => 'Agung Podomoro',
            'alamat' => 'Jl. untuk kembali',
            'perusahaan_id' => 1,
            'kontak' => '0992839879872',
            'saldo_awal_hutang' => '1000000'
        ]);
        Supplier::create([
            'nama' => 'Hasan Sadikin',
            'alamat' => 'Jl. untuk Pulang',
            'perusahaan_id' => 2,
            'kontak' => '0992839879845',
            'saldo_awal_hutang' => '9000000'
        ]);
        Kategori::create([
            'nama' => 'GENERIK'
        ]);
        Kategori::create([
            'nama' => 'NON-GENERIK'
        ]);
        Rak::create([
            'nama' => '1 A ATAS'
        ]);
        Rak::create([
            'nama' => '1 A BAWAH'
        ]);
        Product::create([
            'barcode' => 214323231,
            'nama' => 'Paracetamol',
            'merk_id' => 1,
            'satuan_id' => 1,
            'pengali' => 10,
            'satuan_besar_id' => 1,
            'harga_beli' => 5500,
            'harga_jual_umum' => 8000,
            'harga_jual_resep' => 9000,
            'harga_jual_cust' => 9500,
            'stok_awal' => 12,
            'rak_id' => 1,
            'kategori_id' => 1
        ]);
        Product::create([
            'barcode' => 212323232,
            'nama' => 'Sanmol',
            'merk_id' => 1,
            'satuan_id' => 1,
            'pengali' => 10,
            'satuan_besar_id' => 1,
            'harga_beli' => 7500,
            'harga_jual_umum' => 10000,
            'harga_jual_resep' => 11000,
            'harga_jual_cust' => 10500,
            'stok_awal' => 12,
            'rak_id' => 1,
            'kategori_id' => 1
        ]);
        Product::create([
            'barcode' => 312323232,
            'nama' => 'Vitamin C',
            'merk_id' => 2,
            'satuan_id' => 2,
            'pengali' => 10,
            'satuan_besar_id' => 2,
            'harga_beli' => 4500,
            'harga_jual_umum' => 5500,
            'harga_jual_resep' => 7000,
            'harga_jual_cust' => 6500,
            'stok_awal' => 12,
            'rak_id' => 2,
            'kategori_id' => 2
        ]);
        Merk::create([
            'nama' => 'KALBE'
        ]);
        Merk::create([
            'nama' => 'KOPLO'
        ]);
        Beban::create([
            'nama' => 'BIAYA ADMINISTRASI'
        ]);
        Beban::create([
            'nama' => 'BEBAN LISTRIK'
        ]);
        Beban::create([
            'nama' => 'BEBAN PDAM'
        ]);
        Beban::create([
            'nama' => 'BIAYA ONGKIR'
        ]);
        Beban::create([
            'nama' => 'BEBAN GAJI PEGAWAI'
        ]);
        Beban::create([
            'nama' => 'BIAYA LAIN-LAIN'
        ]);
        Beban::create([
            'nama' => 'BAYAR HUTANG'
        ]);
        Customer::create([
            'nama' => 'Suhar',
            'alamat' => 'Jl. mana yang akan kau pilih',
            'kontak' => '00904293040',
            'saldo_awal_piutang' => 100000
        ]);
        Customer::create([
            'nama' => 'Sahili',
            'alamat' => 'Jl. mana yang akan kau lalui',
            'kontak' => '00904293041',
            'saldo_awal_piutang' => 1000000
        ]);
        Penerimaan::create([
            'nama' => 'PENDAPATAN PIUTANG'
        ]);
        Penerimaan::create([
            'nama' => 'PENDAPATAN LAIN-LAIN'
        ]);
        Perusahaan::create([
            'nama' => 'PT. KALBE'
        ]);
        Perusahaan::create([
            'nama' => 'PT. AMPUNAN'
        ]);
        Info::create([
            'nama' => 'eAchy',
            'infos' => [
                'nama' => 'apotek sehat selalu',
                'alamat' => 'alamat belum di isi',
                'tlp' => 'nomor telepon belum ada'
            ],
            'levels' => [
                'owner' => 2,
                'manager' => 3,
                'admin' => 4,
                'kasir' => 5,
                'gudang' => 5,
            ],
            'themes' => [
                'primary'   => '#30268f',
                'secondary' => '#06b8b8',
                'accent'    => '#9C27B0',
                'primary-light'   => '#cac5f0',
                'dark'      => '#0d101a',
                'dark-page' => '#0d101a',
                'dark-light' => '#262e47',
                'positive'  => '#198754',
                'negative'  => '#dc3545',
                'info'      => '#0d6efd',
                'warning'   => '#d6a100',
                'danger'   => '#990000',
                'white'   => '#ffffff',
            ],
            'menus' => [
                ['name' => 'dashboard', 'icon' => 'icon-mat-dashboard', 'link' => 'dashboard', 'submenus' => []],
                [
                    'name' => 'master',
                    'icon' => 'icon-mat-dataset',
                    'link' => 'master',
                    'submenus' => [
                        ['name' => 'Satuan', 'icon' => 'icon-mat-gas_meter', 'link' => 'satuan'],
                        ['name' => 'Rak', 'icon' => 'icon-mat-table_rows', 'link' => 'rak'],
                        ['name' => 'Kategori', 'icon' => 'icon-mat-category', 'link' => 'kategori'],
                        ['name' => 'Distributor', 'icon' => 'icon-mat-rv_hookup', 'link' => 'supplier'],
                        ['name' => 'Dokter', 'icon' => 'icon-mat-medication', 'link' => 'dokter'],
                        ['name' => 'Produk', 'icon' => 'icon-mat-workspaces', 'link' => 'produk'],
                        ['name' => 'Beban', 'icon' => 'icon-mat-assessment', 'link' => 'beban'],
                        ['name' => 'Penerimaan', 'icon' => 'icon-mat-attach_money', 'link' => 'penerimaan'],
                        ['name' => 'Customer', 'icon' => 'icon-mat-local_shipping', 'link' => 'customer'],
                        ['name' => 'Merk', 'icon' => 'icon-mat-auto_awesome_mosaic', 'link' => 'merk'],
                        ['name' => 'Perusahaan', 'icon' => 'icon-mat-business', 'link' => 'perusahaan']

                    ]
                ],
                [
                    'name' => 'transaksi',
                    'icon' => 'icon-mat-sync_alt',
                    'link' => 'transaksi',
                    'submenus' => [
                        ['name' => 'Pembelian', 'value' => 'pembelian', 'icon' => 'icon-mat-inventory_2', 'link' => '/pembelian/PBL-'],
                        ['name' => 'Penjualan', 'value' => 'penjualan', 'icon' => 'icon-mat-shopping_bag', 'link' => '/penjualan/PJL-'],
                        ['name' => 'Transaksi Penerimaan', 'value' => 'transaksi.penerimaan', 'icon' => 'icon-mat-account_balance_wallet', 'link' => '/transaksi/penerimaan'],
                        ['name' => 'Beban Biaya', 'value' => 'biaya', 'icon' => 'icon-mat-payment', 'link' => '/biaya'],
                        ['name' => 'Retur', 'value' => 'retur', 'icon' => 'icon-mat-assignment_return', 'link' => '/retur']

                    ]
                ],
                [
                    'name' => 'history',
                    'icon' => 'icon-mat-history',
                    'link' => 'history',
                    'submenus' => [
                        ['name' => 'Seluruhnya', 'value' => 'all', 'icon' => 'icon-mat-density_small'],
                        ['name' => 'Draft', 'value' => 'draft', 'icon' => 'icon-mat-insert_drive_file'],
                        ['name' => 'Pembelian', 'value' => 'PEMBELIAN', 'icon' => 'icon-mat-inventory_2'],
                        ['name' => 'Penjualan', 'value' => 'PENJUALAN', 'icon' => 'icon-mat-shopping_bag'],
                        ['name' => 'Transaksi Penerimaan', 'value' => 'PENERIMAAN', 'icon' => 'icon-mat-account_balance_wallet'],
                        ['name' => 'Beban Biaya', 'value' => 'BEBAN', 'icon' => 'icon-mat-payment'],
                        ['name' => 'Retur Pembelian', 'value' => 'RETUR PEMBELIAN', 'icon' => 'icon-mat-assignment_return'],
                        ['name' => 'Retur Penjualan', 'value' => 'RETUR PENJUALAN', 'icon' => 'icon-mat-assignment_return'],
                        ['name' => 'Form Penyesuaian', 'value' => 'FORM PENYESUAIAN', 'icon' => 'icon-mat-tune']
                    ]
                ],
                ['name' => 'laporan', 'icon' => 'icon-mat-description', 'link' => 'laporan', 'submenus' => []],
                [
                    'name' => 'setting',
                    'icon' => 'icon-mat-settings',
                    'link' => 'setting',
                    'submenus' => [
                        ['name' => 'User', 'value' => 'user', 'icon' => 'icon-mat-density_small'],
                        ['name' => 'Menu', 'value' => 'menu', 'icon' => 'icon-mat-insert_drive_file']
                    ]
                ]
            ]
        ]);


        // Transaction::factory()->count(50)
        //     ->has(DetailTransaction::factory()->count(3), 'detail_transaction')
        //     ->create();
        // Transaction::factory()->count(50)
        //     ->has(BebanTransaction::factory()->count(1), 'beban_transaction')
        //     ->create();
        // Transaction::factory()->count(50)
        //     ->has(DetailPenerimaan::factory()->count(1), 'penerimaan_transaction')
        //     ->create();
        // Transaction::create([
        //     'nama' => 'di isi nama',
        //     'tanggal' => '2020/08/12',
        //     'total' => 70000,
        //     'ongkir' => 0,
        //     'potongan' => 0,
        //     'bayar' => 100000,
        //     'kembali' => 30000,
        //     'status' => 0,
        // ]);
        // DetailTransaction::create([
        //     'transaction_id' => 1,
        //     'product_id' => 1,
        //     'qty' => 3,
        //     'harga' => 10000,
        //     'sub_total' => 30000
        // ]);
        // BebanTransaction::create([
        //     'transaction_id' => 1,
        //     'beban_id' => 1,
        //     'sub_total' => 10000,
        //     'keterangan' => 'seeder jadi ya gitu lah'
        // ]);
    }
}
