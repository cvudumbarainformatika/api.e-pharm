<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function dokter()
    {
        return $this->belongsTo(Dokter::class);
    }
    public function penerimaan_transaction()
    {
        return $this->hasMany(DetailPenerimaan::class);
    }
    public function beban_transaction()
    {
        return $this->hasMany(BebanTransaction::class);
    }
    public function detail_transaction()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    // public function
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('faktur', 'LIKE', '%' . $query . '%')
                ->orWhere('reff', 'LIKE', '%' . $query . '%');
            // ->orWhere('nama', 'LIKE', '%' . $query . '%')
        });

        $search->when($reqs['user_id'] ?? false, function ($search, $query) {
            return $search->where('user_id', $query);
        });

        $search->when($reqs['supplier_id'] ?? false, function ($search, $query) {
            return $search->where('supplier_id', $query);
        });

        $search->when($reqs['customer_id'] ?? false, function ($search, $query) {
            return $search->where('customer_id', $query);
        });
        $search->when($reqs['dokter_id'] ?? false, function ($search, $query) {
            return $search->where('dokter_id', $query);
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
