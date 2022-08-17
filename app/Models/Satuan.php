<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->hasMany(Product::class); // satuan iu punya banyak row di product
    }
}
