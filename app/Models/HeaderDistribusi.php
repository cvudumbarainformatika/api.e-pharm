<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderDistribusi extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public function details()
    {
        return $this->hasMany(DistribusiAntarToko::class, 'nodistribusi', 'nodistribusi');
    }
    public function asal()
    {
        return $this->belongsTo(Cabang::class, 'dari', 'kodecabang');
    }
    public function menuju()
    {
        return $this->belongsTo(Cabang::class, 'tujuan', 'kodecabang');
    }
}
