<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('taskforce_visits')) Schema::create('taskforce_visits', function (Blueprint $t) {
            $t->id();$t->string('id_outlet',50);$t->date('period_month');$t->date('visited_at');
            $t->text('notes')->nullable();$t->text('follow_up')->nullable();
            $t->string('user_id',50);$t->string('username',150);
            $t->string('request_id',64)->unique();$t->unsignedBigInteger('legacy_action_id')->nullable()->unique();
            $t->timestamps();$t->index(['id_outlet','visited_at','id'],'tf_visits_outlet_date');
        });
        if(!Schema::hasTable('taskforce_push_so_actions')) return;
        // Old saves were status edits, not necessarily separate visits. Keep the
        // latest confirmed note per outlet/day; the full action audit remains intact.
        $latest=DB::table('taskforce_push_so_actions')->where('visited',true)->whereNotNull('visited_at')
            ->selectRaw('MAX(id) as action_id')->groupBy('id_outlet','visited_at');
        DB::table('taskforce_push_so_actions as a')->joinSub($latest,'last_save','last_save.action_id','=','a.id')
            ->select('a.*')->orderBy('a.id')->chunk(500,function($rows){
                $batch=[];foreach($rows as$r)$batch[]=[
                    'id_outlet'=>$r->id_outlet,'period_month'=>$r->period_month,'visited_at'=>$r->visited_at,
                    'notes'=>$r->notes,'follow_up'=>null,'user_id'=>$r->user_id,'username'=>$r->username,
                    'request_id'=>'legacy:'.$r->id,'legacy_action_id'=>$r->id,'created_at'=>$r->created_at,'updated_at'=>$r->updated_at,
                ];if($batch)DB::table('taskforce_visits')->insertOrIgnore($batch);
            });
    }
    public function down(): void { Schema::dropIfExists('taskforce_visits'); }
};
