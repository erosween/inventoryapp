<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskforceController extends MonitaDumaiController
{
    public function index(Request $request)
    {
        [$cutoff,$upload,$headers,$options]=$this->context($request);
        $scopes=$this->suryaTapScopes();
        $taps=$this->taskforceOutlets($scopes,$upload?->id)->distinct()->orderBy('outlet.tap')->pluck('outlet.tap');
        $sfs=$this->taskforceOutlets($scopes,$upload?->id)->when(!empty($options['tap']),fn($q)=>$q->where('outlet.tap',$options['tap']))
            ->whereNotNull('outlet.sf')->whereRaw("TRIM(outlet.sf) <> ''")->distinct()->orderBy('outlet.sf')->pluck('outlet.sf');
        $outlets=$this->reportQuery($cutoff,$upload,$options)
            ->paginate((int)($options['per_page']??25))->appends($request->only(['sales_cutoff','per_page','tap','sf','columns','exclude']));
        $outlets->getCollection()->transform(function($outlet) {
            $outlet->uploaded_cells=$outlet->cells ? json_decode($outlet->cells,true,512,JSON_THROW_ON_ERROR) : [(string)$outlet->id_outlet];
            return $outlet;
        });
        $canAct=$this->canAct();
        return response()->view('surya.taskforce.push-so',compact('outlets','headers','cutoff','canAct','upload','taps','sfs','options'))
            ->header('Cache-Control','no-store, no-cache, must-revalidate, max-age=0');
    }

    private function context(Request $request): array
    {
        $options=$request->validate([
            'per_page'=>'nullable|in:10,25,50,100',
            'sales_cutoff'=>'nullable|date_format:Y-m-d|before_or_equal:today',
            'tap'=>'nullable|string|max:150','sf'=>'nullable|string|max:150',
            'columns'=>'nullable|array|max:63','columns.*'=>'required|array|min:1|max:100',
            'columns.*.*'=>'present|nullable|string|max:5000',
            'exclude'=>'nullable|array|max:63','exclude.*'=>'required|array|min:1|max:100',
            'exclude.*.*'=>'present|nullable|string|max:5000',
        ]);
        $cutoff=Carbon::parse($options['sales_cutoff']??now('Asia/Jakarta')->toDateString());
        $upload=DB::table('taskforce_uploads')->where('period_month',$cutoff->copy()->startOfMonth()->toDateString())->orderByDesc('id')->first();
        $headers=$upload && $upload->headers ? json_decode($upload->headers,true,512,JSON_THROW_ON_ERROR) : ['ID Outlet'];
        foreach(['columns','exclude'] as $mode) foreach($options[$mode]??[] as $key=>$values) {
            $this->columnExpression((string)$key,count($headers));
            $options[$mode][$key]=array_map(fn($value)=>(string)($value??''),$values);
        }
        return [$cutoff,$upload,$headers,$options];
    }

    private function reportQuery(Carbon $cutoff, $upload, array $options)
    {
        $latest=DB::table('taskforce_push_so_actions')->where('period_month',$cutoff->copy()->startOfMonth()->toDateString())
            ->selectRaw('id_outlet, MAX(id) as action_id')->groupBy('id_outlet');
        $query=$this->taskforceOutlets($this->suryaTapScopes(),$upload?->id)
            ->when(!empty($options['tap']),fn($q)=>$q->where('outlet.tap',$options['tap']))
            ->when(!empty($options['sf']),fn($q)=>$q->where('outlet.sf',$options['sf']))
            ->leftJoinSub($latest,'latest_action','latest_action.id_outlet','=','outlet.id_outlet')
            ->leftJoin('taskforce_push_so_actions as action','action.id','=','latest_action.action_id')
            ->select('outlet.id_outlet','outlet.nama_outlet','outlet.tap','assignment.cells','assignment.row_position',
                'action.visited','action.visited_at','action.notes','action.username as action_by','action.updated_at as action_updated_at')
            ->orderBy('assignment.row_position')->orderBy('assignment.id');
        $headerCount=$upload && $upload->headers ? count(json_decode($upload->headers,true,512,JSON_THROW_ON_ERROR)) : 1;
        foreach($options['columns']??[] as $key=>$values) $query->whereIn(DB::raw($this->columnExpression((string)$key,$headerCount)),$values);
        foreach($options['exclude']??[] as $key=>$values) $query->whereNotIn(DB::raw($this->columnExpression((string)$key,$headerCount)),$values);
        return $query;
    }

    private function columnExpression(string $key, int $count): string
    {
        if(ctype_digit($key) && (int)$key<$count) {
            $index=(int)$key;
            $json=DB::connection()->getDriverName()==='sqlite'
                ? "json_extract(assignment.cells, '$[{$index}]')"
                : "JSON_UNQUOTE(JSON_EXTRACT(assignment.cells, '$[{$index}]'))";
            return $index===0 ? "COALESCE({$json}, outlet.id_outlet, '')" : "COALESCE({$json}, '')";
        }
        if($key==='visit') return "CASE WHEN COALESCE(action.visited,0)=1 THEN 'Sudah visit' ELSE 'Belum visit' END";
        if($key==='date') return DB::connection()->getDriverName()==='sqlite'
            ? "COALESCE(strftime('%d/%m/%Y',action.visited_at),'')" : "COALESCE(DATE_FORMAT(action.visited_at,'%d/%m/%Y'),'')";
        if($key==='notes') return "COALESCE(action.notes,'')";
        throw \Illuminate\Validation\ValidationException::withMessages(['columns'=>'Kolom filter tidak valid.']);
    }

    public function filterValues(Request $request)
    {
        [$cutoff,$upload,$headers,$options]=$this->context($request);
        $input=$request->validate(['column'=>'required|string|max:10','q'=>'nullable|string|max:200','value_page'=>'nullable|integer|min:1|max:10000']);
        $key=$input['column'];
        $expression=$this->columnExpression($key,count($headers));
        unset($options['columns'][$key]);
        unset($options['exclude'][$key]);
        $query=$this->reportQuery($cutoff,$upload,$options)->reorder()->select([])->selectRaw($expression.' AS filter_value')->distinct();
        if(($input['q']??'')!=='') $query->whereRaw($expression.' LIKE ?',['%'.$input['q'].'%']);
        $values=$query->orderBy('filter_value')->offset(((int)($input['value_page']??1)-1)*100)->limit(101)->get();
        return response()->json(['values'=>$values->take(100)->pluck('filter_value')->map(fn($value)=>(string)$value)->values(),'has_more'=>$values->count()>100]);
    }

    public function download(Request $request)
    {
        [$cutoff,$upload,$headers,$options]=$this->context($request);
        $query=$this->reportQuery($cutoff,$upload,$options);
        return response()->streamDownload(function() use($query,$headers) {
            $output=fopen('php://output','w');
            fwrite($output,"\xEF\xBB\xBF");
            // Treat spreadsheet formulas as text when the CSV is opened in Excel.
            $safe=static fn($value)=>preg_match('/^[\s]*[=+@-]/u',(string)$value) ? "'".$value : $value;
            try {
                fputcsv($output,array_map($safe,array_merge($headers,['Visit Asmen','Tanggal Visit','Catatan Asmen'])));
                foreach($query->cursor() as $row) {
                    $cells=$row->cells ? json_decode($row->cells,true,512,JSON_THROW_ON_ERROR) : [(string)$row->id_outlet];
                    $cells=array_slice(array_pad($cells,count($headers),''),0,count($headers));
                    $cells[]=$row->visited?'Sudah visit':'Belum visit';
                    $cells[]=$row->visited_at ? Carbon::parse($row->visited_at)->format('d/m/Y') : '';
                    $cells[]=$row->notes??'';
                    fputcsv($output,array_map($safe,$cells));
                }
            } finally { fclose($output); }
        },'taskforce-'.$cutoff->format('Y-m').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, string $idOutlet)
    {
        abort_unless($this->canAct(),403,'Akun ini tidak memiliki hak mengisi tindak lanjut.');
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
        return response()->json(['message'=>'Tindak lanjut outlet berhasil disimpan.','updated_by'=>auth()->user()->username.' · '.now()->format('d/m H:i')]);
    }

    private function canAct(): bool
    {
        return auth()->check() && (auth()->user()->isSuperAdmin() || str_starts_with(strtolower(auth()->user()->username),'asmen_'));
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
