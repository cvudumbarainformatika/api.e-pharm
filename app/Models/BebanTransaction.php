<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BebanTransaction extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function beban()
    {
        return $this->belongsTo(Beban::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('keterangan', 'LIKE', '%' . $query . '%');
        });

        $search->when($reqs['transaction_id'] ?? false, function ($search, $query) {
            return $search->where('transaction_id', $query);
        });

        $search->when($reqs['beban_id'] ?? false, function ($search, $query) {
            return $search->where('beban_id', $query);
        });

        // $search->when($reqs['jenis_kepegawaian_id'] ?? false, function ($search, $jenis) {
        //     return $search->whereHas('jenis', function ($search) use ($jenis) {
        //         $search->where('id', $jenis);
        //     });
        // });
    }
}
