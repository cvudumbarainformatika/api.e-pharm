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
}
