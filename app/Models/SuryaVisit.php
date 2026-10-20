<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SuryaVisit extends Model
{
    protected $table = 'surya_visits';

    protected $fillable = [
        'visited_at', 'checkout_at', 'checkout_reason', 'checkout_notes',
        'id_outlet', 'idtap', 'idsf', 'latitude', 'longitude',
        'visit_type', 'stock_owner_idsf', 'outlet_name', 'owner_name', 'phone_number', 'outlet_address',
        'accuracy', 'distance_meters', 'photo_path', 'photo_front_path',
        'photo_inside_path', 'gps_status', 'notes',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'checkout_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'distance_meters' => 'float',
    ];

    public function scopeInProgressForSalesForce(Builder $query, string $idsf, ?string $date = null): Builder
    {
        $visitDate = $date ?? now()->toDateString();

        return $query
            ->where('idsf', $idsf)
            ->whereDate('visited_at', $visitDate)
            ->whereNull('checkout_at')
            ->whereNotNull('photo_front_path')
            ->whereNotNull('photo_inside_path')
            ->where(function (Builder $progress) use ($idsf, $visitDate) {
                $progress->whereExists(function ($draftSales) use ($idsf, $visitDate) {
                    $draftSales->select(DB::raw(1))
                        ->from('mobile_penjualan')
                        ->whereColumn('mobile_penjualan.id_outlet', 'surya_visits.id_outlet')
                        ->where('mobile_penjualan.idsf', $idsf)
                        ->where('mobile_penjualan.status', 'draft_wa')
                        ->whereDate('mobile_penjualan.tgl', $visitDate);
                })->orWhereNotExists(function ($completedSales) use ($idsf, $visitDate) {
                    $completedSales->select(DB::raw(1))
                        ->from('mobile_penjualan')
                        ->whereColumn('mobile_penjualan.id_outlet', 'surya_visits.id_outlet')
                        ->where('mobile_penjualan.idsf', $idsf)
                        ->where('mobile_penjualan.status', '!=', 'draft_wa')
                        ->whereDate('mobile_penjualan.tgl', $visitDate);
                });
            });
    }
}
