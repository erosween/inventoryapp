<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobileSalesReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'tgl',
        'id_outlet',
        'idtap',
        'idsf',
        'owner_whatsapp',
        'total_qty',
        'total_amount',
        'whatsapp_opened_at',
        'whatsapp_shared_at',
    ];

    protected $casts = [
        'tgl' => 'date',
        'total_qty' => 'integer',
        'total_amount' => 'integer',
        'whatsapp_opened_at' => 'datetime',
        'whatsapp_shared_at' => 'datetime',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(MobilePenjualan::class, 'receipt_id');
    }
}
