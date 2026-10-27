<?php

use App\Support\DenomGrouping;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denom_grouping_archive', function (Blueprint $table) {
            $table->unsignedInteger('idbarang')->primary();
            $table->text('original_row');
            $table->timestamp('archived_at');
        });
        foreach (DB::table('denom')->orderBy('idbarang')->get() as $row) {
            DB::table('denom_grouping_archive')->insert([
                'idbarang'=>$row->idbarang, 'original_row'=>json_encode($row, JSON_THROW_ON_ERROR), 'archived_at'=>now(),
            ]);
        }
        Schema::table('denom', function (Blueprint $table) {
            $table->string('product', 2)->nullable();
            $table->string('product_variant', 12)->nullable();
            $table->string('stock_form', 6)->nullable();
            $table->string('inject_source_id', 25)->nullable();
            $table->boolean('allow_do')->default(false);
            $table->index(['product', 'product_variant'], 'denom_product_variant_idx');
            $table->index(['inject_source_id', 'stock_form'], 'denom_inject_source_form_idx');
        });
        DB::transaction(function () {
            foreach (DB::table('denom')->orderBy('idbarang')->get() as $row) {
                $metadata = DenomGrouping::infer($row);
                // Keep SP for legacy mobile compatibility; repair a misclassified
                // SA output that previously still carried product_group=PV.
                if ($metadata['product'] === 'SA' && $row->product_group === 'PV') $metadata['product_group'] = 'SA';
                DB::table('denom')->where('idbarang', $row->idbarang)->update($metadata);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (DB::table('denom_grouping_archive')->get() as $archived) {
                $row = json_decode($archived->original_row, true, 512, JSON_THROW_ON_ERROR);
                DB::table('denom')->where('idbarang', $archived->idbarang)->update(['product_group'=>$row['product_group']]);
            }
        });
        Schema::table('denom', function (Blueprint $table) {
            $table->dropIndex('denom_product_variant_idx');
            $table->dropIndex('denom_inject_source_form_idx');
            $table->dropColumn(['product', 'product_variant', 'stock_form', 'inject_source_id', 'allow_do']);
        });
        Schema::drop('denom_grouping_archive');
    }
};
