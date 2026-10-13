<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePenjualan extends Model
{
    use HasFactory;

    protected $table = 'mobile_penjualan';

    protected $fillable = [
        'receipt_id',
        'tgl',
        'id_outlet',
        'idtap',
        'idsf',
        'iddenom',
        'qty',
        'unit_price',
        'latitude',
        'longitude',
        'keterangan',
        'status',
        'stock_reserved_at',
    ];

    protected $casts = [
        'stock_reserved_at' => 'datetime',
    ];

    public function receipt()
    {
        return $this->belongsTo(MobileSalesReceipt::class, 'receipt_id');
    }
}
