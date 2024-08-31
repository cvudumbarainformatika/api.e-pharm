<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPemesanan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public function produk()
    {
        return $this->belongsTo(Product::class, 'kode_produk', 'kode_produk');
    }
}
