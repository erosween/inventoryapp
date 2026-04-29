<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePenjualan extends Model
{
    use HasFactory;

    protected $table = 'mobile_penjualan';

    protected $fillable = [
        'tgl',
        'id_outlet',
        'idtap',
        'idsf',
        'iddenom',
        'qty',
        'latitude',
        'longitude',
        'keterangan',
        'status',
    ];
}
