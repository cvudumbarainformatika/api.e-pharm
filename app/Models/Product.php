<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuid;
    protected $guarded = ['id'];

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
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('barcode', 'LIKE', '%' . $query . '%')
                ->orWhere('merk', 'LIKE', '%' . $query . '%');
        });

        $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $query) {
            return $search->where('jenis_kepegawaian_id', $query);
        });

        // $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $jenis) {
        //     return $search->whereHas('jenis', function ($search) use ($jenis) {
        //         $search->where('id', $jenis);
        //     });
        // });
    }
}
