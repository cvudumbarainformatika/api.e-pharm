<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->hasMany(Product::class); // kategori itu ada banyak di tabel produk
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
        });

        // $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $query) {
        //     return $search->where('jenis_kepegawaian_id', $query);
        // });

        // $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $jenis) {
        //     return $search->whereHas('jenis', function ($search) use ($jenis) {
        //         $search->where('id', $jenis);
        //     });
        // });
    }
}
