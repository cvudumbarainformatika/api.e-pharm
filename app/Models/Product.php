<?php

namespace App\Models;

use App\Http\Controllers\Api\v1\LaporanBaruController;
use App\Http\Controllers\Api\v1\LaporanController;
use App\Models\Traits\HasUuid;
use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuid;
    protected $guarded = ['id'];
    // protected $appends = ['stok'];

    // protected function stok(): Attribute
    // {
    // return new Attribute(
    //     get:
    // )}
    public function getStokAttribute()
    {
        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => $this->id,
            'kode_produk' => $this->kode_produk,
        );
        $singleDet = new LaporanBaruController;
        $stokMasuk = $singleDet->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $singleDet->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $singleDet->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $singleDet->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $singleDet->getSingleDetails($header, 'FORM PENYESUAIAN');
        $distribusi = $singleDet->getSumSingleProduct($header);


        $masukBefore = collect($stokMasuk->before)->sum('qty');
        $masukPeriod = collect($stokMasuk->period)->sum('qty');
        $keluarBefore = collect($stokKeluar->before)->sum('qty');
        $keluarPeriod = collect($stokKeluar->period)->sum('qty');
        $retBeliBefore = collect($returPembelian->before)->sum('qty');
        $retBeliPeriod = collect($returPembelian->period)->sum('qty');
        $retJualBefore = collect($returPenjualan->before)->sum('qty');
        $retJualPeriod = collect($returPenjualan->period)->sum('qty');
        $penyeBefore = collect($penyesuaian->before)->sum('qty');
        $penyePeriod = collect($penyesuaian->period)->sum('qty');

        $distMB = collect($distribusi->masukbefore)->sum('qty');
        $distKB = collect($distribusi->keluarbefore)->sum('qty');
        $distMP = collect($distribusi->masukperiod)->sum('qty');
        $distKP = collect($distribusi->keluarperiod)->sum('qty');


        $sebelum = $masukBefore - $keluarBefore + $retJualBefore - $retBeliBefore + $penyeBefore + $distMB - $distKB;
        $berjalan = $masukPeriod - $keluarPeriod + $retJualPeriod - $retBeliPeriod + $penyePeriod + $distMP - $distKP;
        $awal = $this->stok_awal + $sebelum;
        $sekarang = $awal + $berjalan;
        // $sekarang = 0;
        // $produk->stok_awal = $awal;
        // $produk->stokSekarang = $sekarang;
        // $produk->stokBerjalan = $berjalan;


        return $this->attributes['stok'] = $sekarang;
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class); // kategori_id yang ada di tabel produk itu milik tabel kategori
    }

    public function rak()
    {
        return $this->belongsTo(Rak::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
    public function satuanBesar()
    {
        return $this->belongsTo(SatuanBesar::class);
    }

    public function merk()
    {
        return $this->belongsTo(Merk::class);
    }

    public function detail_transaksi()
    {
        return $this->hasMany(DetailTransaction::class);
    }
    public function ada()
    {
        return $this->hasOne(DetailTransaction::class);
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('barcode', 'LIKE', '%' . $query . '%')
                ->orWhere('kode_produk', 'LIKE', '%' . $query . '%');
        });

        $search->when($reqs['merk_id'] ?? false, function ($search, $query) {
            return $search->where('merk_id', $query);
        });

        $search->when($reqs['satuan_id'] ?? false, function ($search, $query) {
            return $search->where('satuan_id', $query);
        });

        $search->when($reqs['rak_id'] ?? false, function ($search, $query) {
            return $search->where('rak_id', $query);
        });
        $search->when($reqs['kategori_id'] ?? false, function ($search, $query) {
            return $search->where('kategori_id', $query);
        });

        // $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $jenis) {
        //     return $search->whereHas('jenis', function ($search) use ($jenis) {
        //         $search->where('id', $jenis);
        //     });
        // });
    }
}
