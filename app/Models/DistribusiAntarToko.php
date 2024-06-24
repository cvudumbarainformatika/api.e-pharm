<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiAntarToko extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function header()
    {
        return $this->belongsTo(HeaderDistribusi::class, 'nodistribusi', 'nodistribusi');
    }
    public function produk()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
