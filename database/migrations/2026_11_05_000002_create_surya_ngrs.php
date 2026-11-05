<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('surya_ngrs_uploads',function(Blueprint $t){$t->uuid('id')->primary();$t->string('filename');$t->string('status',20);$t->unsignedBigInteger('uploaded_by')->nullable();$t->unsignedInteger('row_count')->default(0);$t->timestamps();});
        Schema::create('surya_ngrs_transactions',function(Blueprint $t){$t->id();$t->uuid('upload_id');$t->date('month');$t->date('transaction_date');$t->string('rs',20);$t->string('organization');$t->string('tap',40);$t->string('cluster',40);$t->unsignedBigInteger('trx');$t->unsignedBigInteger('rech');$t->index(['upload_id','month']);});
        Schema::create('surya_ngrs_monthly',function(Blueprint $t){$t->id();$t->uuid('upload_id');$t->date('month');$t->string('rs',20);$t->string('tap',40);$t->string('cluster',40);$t->unsignedBigInteger('trx');$t->unsignedBigInteger('rech');$t->unique(['upload_id','month','rs','tap','cluster'],'ngrs_monthly_unique');$t->index(['month','upload_id','tap']);$t->index('rs');});
        Schema::create('surya_ngrs_months',function(Blueprint $t){$t->date('month')->primary();$t->uuid('upload_id');$t->timestamp('updated_at')->nullable();});
    }
    public function down(): void { foreach(['surya_ngrs_months','surya_ngrs_monthly','surya_ngrs_transactions','surya_ngrs_uploads']as$table)Schema::dropIfExists($table); }
};
