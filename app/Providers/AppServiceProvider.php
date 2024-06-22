<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tambahkan kode untuk View Composer di sini
    View::composer('*', function ($view) {
        $idtap = session('idtap'); // Tentukan nilai $idtap sesuai kebutuhan
    

        if($idtap =='SBP_DUMAI'){

        $n = DB::table('keluar')
        ->select(DB::raw('sum(status) as qty'))
        ->get();
        
        }else{
        
        $n = DB::table('keluar')
                ->select(DB::raw('sum(status) as qty'))
                ->where('penerima',$idtap)
                ->get();
            
        }

        $notif = $n->sum('qty');

        $view->with('notif', $notif); // Bagikan variabel $notif ke semua tampilan
    });
    }
}
