<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_tracking_digipos', 'serial_number')) {
            DB::table('product_tracking_digipos')->whereNull('serial_start')->update([
                'serial_start' => DB::raw('serial_number'),
                'serial_end' => DB::raw('serial_number'),
            ]);
        }
        DB::table('product_tracking_digipos as digipos')
            ->join('product_tracking_uploads as uploads', 'uploads.id', '=', 'digipos.upload_id')
            ->whereNull('digipos.scan_date')->where('uploads.kind', 'scan')
            ->update(['digipos.scan_date' => DB::raw('uploads.scan_date')]);

        $indexes = array_flip(DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())->where('table_name', 'product_tracking_digipos')
            ->pluck('index_name')->all());
        if (isset($indexes['pt_digipos_upload_sn_idx']) && ! isset($indexes['pt_digipos_upload_idx'])) {
            Schema::table('product_tracking_digipos', fn (Blueprint $table) => $table->index('upload_id', 'pt_digipos_upload_idx'));
        }
        Schema::table('product_tracking_digipos', function (Blueprint $table) use ($indexes) {
            foreach (['pt_digipos_kind_sn_idx', 'pt_digipos_kind_phone_idx'] as $index) {
                if (isset($indexes[$index])) $table->dropIndex($index);
            }
            if (isset($indexes['pt_digipos_upload_sn_idx'])) {
                $table->dropIndex('pt_digipos_upload_sn_idx');
            }
            $table->dropColumn(['serial_number', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::table('product_tracking_digipos', function (Blueprint $table) {
            $table->string('serial_number', 24)->nullable();
            $table->string('phone_number', 24)->nullable();
            $table->index(['kind', 'serial_number'], 'pt_digipos_kind_sn_idx');
            $table->index(['kind', 'phone_number'], 'pt_digipos_kind_phone_idx');
            $table->index(['upload_id', 'serial_number'], 'pt_digipos_upload_sn_idx');
        });

        DB::table('product_tracking_digipos')->update(['serial_number' => DB::raw('serial_start')]);
    }
};
