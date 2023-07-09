<?php

namespace App\Models;

use App\Http\Controllers\Api\v1\LaporanController;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuid;
    protected $guarded = ['id'];
    protected $appends = ['stok'];


    public function getStokAttribute($reff)
    {
        $header = (object) array(
            'from' => date('Y-m-d'),
            'product_id' => $this->id
        );
        $singleDet = new LaporanController;
        $stokMasuk = $singleDet->getSingleDetails($header, 'PEMBELIAN');
        $returPembelian = $singleDet->getSingleDetails($header, 'RETUR PEMBELIAN');
        $stokKeluar = $singleDet->getSingleDetails($header, 'PENJUALAN');
        $returPenjualan = $singleDet->getSingleDetails($header, 'RETUR PENJUALAN');
        $penyesuaian = $singleDet->getSingleDetails($header, 'FORM PENYESUAIAN');

        // $produk = Product::where('id', $header->product_id)->first();

        $data = Transaction::select('id', 'nama')->where('status', 1)->where('reff', $reff)->first();
        $qty = 0;
        if ($data) {
            // $apem = collect($data->detail_transaction)->groupBy('product_id');
            // $apem = collect($data['detail_transaction'])->groupBy('product_id');
            // $qty = $apem[$header->product_id][0]->qty;
            $apem = DetailTransaction::select('id', 'qty', 'product_id')->where('transaction_id', $data->id)
                ->where('product_id', $header->product_id)->first();
            $qty = $data->nama === 'PENJUALAN' ? $apem->qty : -$apem->qty;
        }

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

        $sebelum = $masukBefore - $keluarBefore + $retJualBefore - $retBeliBefore + $penyeBefore;
        $berjalan = $masukPeriod - $keluarPeriod + $retJualPeriod - $retBeliPeriod + $penyePeriod - $qty;
        $awal = $this->stok_awal + $sebelum;
        $sekarang = $awal + $berjalan;
        // $produk->stok_awal = $awal;
        // $produk->stokSekarang = $sekarang;
        // $produk->stokBerjalan = $berjalan;


        return $sekarang;;
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
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('barcode', 'LIKE', '%' . $query . '%');
            // ->orWhere('expired', 'LIKE', '%' . $query . '%');
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
