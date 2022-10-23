<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTagihan extends Model
{
    use HasFactory, HasUuid;
    protected $guarded = ['id'];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class);
    }
    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
