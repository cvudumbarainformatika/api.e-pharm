<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    use HasFactory, HasUuid;
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DetailTagihan::class);
    }
    public function kasir()
    {
        return $this->belongsTo(User::class);
    }
}
