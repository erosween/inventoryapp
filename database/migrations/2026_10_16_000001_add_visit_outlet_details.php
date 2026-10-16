<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->string('visit_type', 30)->default('pjp')->after('idsf');
            $table->string('outlet_name')->nullable()->after('visit_type');
            $table->string('owner_name')->nullable()->after('outlet_name');
            $table->string('phone_number', 40)->nullable()->after('owner_name');
            $table->text('outlet_address')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('surya_visits', function (Blueprint $table) {
            $table->dropColumn(['visit_type', 'outlet_name', 'owner_name', 'phone_number', 'outlet_address']);
        });
    }
};
