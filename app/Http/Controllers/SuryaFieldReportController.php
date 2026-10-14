<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuryaFieldReportController extends Controller
{
    public function index(Request $request)
    {
        $idtap = session('idtap');
        $type = $request->input('type', 'consignment');
        abort_unless(in_array($type, ['consignment', 'survey', 'network'], true), 404);
        $table = ['consignment' => 'surya_sa_consignment_numbers', 'survey' => 'surya_competitor_surveys', 'network' => 'surya_network_reports'][$type];
        $rows = DB::table($table . ' as r')
            ->leftJoin('appsdumais as o', 'o.id_outlet', '=', 'r.id_outlet')
            ->leftJoin('idsf as s', 's.idsf', '=', 'r.idsf')
            ->when($idtap && $idtap !== 'SBP_DUMAI', fn ($q) => $q->where('r.idtap', $idtap))
            ->when($type === 'consignment', fn ($q) => $q->where('r.status', '<>', 'draft'))
            ->select('r.*', 'o.nama_outlet', 's.namasf')
            ->orderByDesc('r.reported_at')->paginate(25)->withQueryString();
        return view('surya.field-reports', compact('rows', 'type'));
    }
}
