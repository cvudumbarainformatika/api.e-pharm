<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'infos' => 'array',
        'themes' => 'array',
        'menus' => 'array',
        'levels' => 'array',
    ];
}
