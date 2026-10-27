<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskforceController extends MonitaDumaiController
{
    public function index(Request $request)
    {
        $options=$request->validate([
            'per_page'=>'nullable|in:10,25,50,100',
            'sales_cutoff'=>'nullable|date_format:Y-m-d|before_or_equal:today',
        ]);
        $cutoff=Carbon::parse($options['sales_cutoff']??now('Asia/Jakarta')->toDateString());
        $month=$cutoff->copy()->startOfMonth()->toDateString();
        $upload=DB::table('taskforce_uploads')->where('period_month',$month)->orderByDesc('id')->first();
        $headers=$upload && $upload->headers ? json_decode($upload->headers,true,512,JSON_THROW_ON_ERROR) : ['ID Outlet'];
        $base=$this->taskforceOutlets($this->suryaTapScopes(),$upload?->id);
        $latest=DB::table('taskforce_push_so_actions')->where('period_month',$month)
            ->selectRaw('id_outlet, MAX(id) as action_id')->groupBy('id_outlet');
        $outlets=$base->leftJoinSub($latest,'latest_action','latest_action.id_outlet','=','outlet.id_outlet')
            ->leftJoin('taskforce_push_so_actions as action','action.id','=','latest_action.action_id')
            ->select('outlet.id_outlet','outlet.nama_outlet','outlet.tap','assignment.cells','assignment.row_position',
                'action.visited','action.visited_at','action.notes','action.username as action_by','action.updated_at as action_updated_at')
            ->orderBy('assignment.row_position')->orderBy('assignment.id')
            ->paginate((int)($options['per_page']??25))->appends($request->only(['sales_cutoff','per_page']));
        $outlets->getCollection()->transform(function($outlet) {
            $outlet->uploaded_cells=$outlet->cells ? json_decode($outlet->cells,true,512,JSON_THROW_ON_ERROR) : [(string)$outlet->id_outlet];
            return $outlet;
        });
        $canAct=$this->canAct();
        return response()->view('surya.taskforce.push-so',compact('outlets','headers','cutoff','canAct','upload'))
            ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
    }

    public function store(Request $request, string $idOutlet)
    {
        abort_unless($this->canAct(),403,'Hanya asmen yang dapat mengisi tindak lanjut.');
        $data=$request->validate(['sales_cutoff'=>'required|date_format:Y-m-d|before_or_equal:today',
            'visited'=>'required|boolean','visited_at'=>'nullable|required_if:visited,1|date_format:Y-m-d|before_or_equal:today',
            'notes'=>'nullable|string|max:3000']);
        if ($data['visited'] && !Carbon::parse($data['visited_at'])->isSameMonth(Carbon::parse($data['sales_cutoff']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['visited_at'=>'Tanggal visit harus berada dalam bulan tindak lanjut.']);
        }
        $uploadId=DB::table('taskforce_uploads')->where('period_month',Carbon::parse($data['sales_cutoff'])->startOfMonth()->toDateString())->max('id');
        $outlet=$this->taskforceOutlets($this->suryaTapScopes(),$uploadId)->where('outlet.id_outlet',$idOutlet)->first();
        abort_unless($outlet,404);
        $month=Carbon::parse($data['sales_cutoff'])->startOfMonth()->toDateString();
        // Append a new event so previous notes and the responsible user remain auditable.
        DB::table('taskforce_push_so_actions')->insert([
            'id_outlet'=>$idOutlet,'period_month'=>$month,'visited'=>(bool)$data['visited'],
            'visited_at'=>$data['visited'] ? $data['visited_at'] : null,'notes'=>$data['notes']??null,
            'user_id'=>(string)auth()->id(),'username'=>auth()->user()->username,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return response()->json(['message'=>'Tindak lanjut outlet berhasil disimpan.']);
    }

    private function canAct(): bool
    {
        return auth()->check() && str_starts_with(strtolower(auth()->user()->username),'asmen_');
    }

    private function taskforceOutlets(?array $scopes, ?int $uploadId)
    {
        return DB::table('appsdumais as outlet')
            ->join('taskforce_upload_outlets as assignment','assignment.id_outlet','=','outlet.id_outlet')
            ->where('assignment.upload_id',$uploadId??0)
            ->when($scopes!==null,fn($query)=>$query->whereIn('outlet.tap',$scopes));
    }

    public function upload(Request $request, \App\Services\TaskforceUpload $importer)
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(),403);
        $data=$request->validate(['taskforce_month'=>'required|date_format:Y-m',
            'taskforce_file'=>'required|file|mimes:xlsx,xls,csv,txt|extensions:xlsx,xls,csv|max:5120']);
        try {
            $result=$importer->import($request->file('taskforce_file'),$data['taskforce_month'].'-01',auth()->user()->username);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw \Illuminate\Validation\ValidationException::withMessages(['taskforce_file'=>'File gagal dibaca. Gunakan template Excel/CSV yang benar. Daftar sebelumnya tidak berubah.']);
        }
        return redirect()->route('surya.transaction-upload')->with('success',"Taskforce {$data['taskforce_month']}: {$result['count']} outlet disimpan, {$result['duplicates']} duplikat diabaikan. Status visit dan catatan tetap tersimpan.");
    }

    public function template()
    {
        abort_unless(auth()->check() && auth()->user()->isSuperAdmin(),403);
        return response()->streamDownload(function() { echo "\xEF\xBB\xBFID Outlet\r\n"; },'template-taskforce.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
