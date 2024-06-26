<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;


class sisaStockController extends Controller
{
    public function index(){
        $idtaps = DB::table('stockawalfeb')->pluck('idtap')->toArray(); // Ganti 'your_table' dengan nama tabel yang sesuai
        // $idtaps == session('idtap')
        $stocks = []; // Inisialisasi array untuk menyimpan data stock
    
        foreach ($idtaps as $idtap) {
            $stocks[$idtap] = []; // Inisialisasi array untuk setiap idtap
            for ($i = 1; $i <= 42; $i++) {
                // Ambil data stock dari database sesuai dengan idtap dan iddenom
                // Ganti query berdasarkan struktur data dan relasi yang sesuai
                $stockValue = DB::table('stockawalfeb')
                    ->where('idtap', $idtap)
                    ->where('iddenom', $i)
                    ->sum('stock');
    
                // Simpan nilai stock ke dalam array
                $stocks[$idtap][$i] = $stockValue;
            }
        }
    
        // Kirim data ke view 'stock.remaining' dengan variabel $idtaps dan $stocks
        return view('sisastock', compact('idtaps', 'stocks'));
    }
}
