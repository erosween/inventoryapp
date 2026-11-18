<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tracking_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('id_outlet', 50)->index();
            $table->string('outlet_name', 200)->nullable();
            $table->string('sf', 150);
            $table->string('iddenom', 25)->index();
            $table->string('product_group', 12)->default('PV');
            $table->string('identifier_type', 10); // sn | number
            $table->string('input_mode', 10); // single | range
            $table->string('serial_start', 24);
            $table->string('serial_end', 24);
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['identifier_type', 'serial_start', 'serial_end'], 'pt_sales_serial_range_idx');
            $table->index(['sale_date', 'id_outlet'], 'pt_sales_date_outlet_idx');
        });

        Schema::create('product_tracking_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 12); // warehouse | scan
            $table->date('scan_date')->nullable();
            $table->string('filename', 255);
            $table->longText('headers')->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->string('status', 16)->default('mapping'); // mapping | complete | failed
            $table->unsignedInteger('row_count')->default(0);
            $table->string('username', 150);
            $table->timestamps();

            $table->index(['kind', 'status', 'created_at'], 'pt_upload_kind_status_idx');
        });

        Schema::create('product_tracking_digipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('product_tracking_uploads')->cascadeOnDelete();
            $table->string('kind', 12);
            $table->string('serial_number', 24)->nullable();
            $table->string('phone_number', 24)->nullable();
            $table->string('id_outlet', 50)->nullable();
            $table->string('sf', 150)->nullable();
            $table->date('sold_at')->nullable();
            $table->string('product_name', 200)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();

            $table->index(['kind', 'serial_number'], 'pt_digipos_kind_sn_idx');
            $table->index(['kind', 'phone_number'], 'pt_digipos_kind_phone_idx');
            $table->index(['id_outlet', 'kind'], 'pt_digipos_outlet_kind_idx');
            $table->index(['upload_id', 'serial_number'], 'pt_digipos_upload_sn_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tracking_digipos');
        Schema::dropIfExists('product_tracking_uploads');
        Schema::dropIfExists('product_tracking_sales');
    }
};
