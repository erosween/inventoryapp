<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuryaVisit extends Model
{
    protected $table = 'surya_visits';

    protected $fillable = [
        'visited_at', 'id_outlet', 'idtap', 'idsf', 'latitude', 'longitude',
        'accuracy', 'distance_meters', 'photo_path', 'gps_status', 'notes',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'distance_meters' => 'float',
    ];
}
