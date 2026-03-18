<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_primary', function (Blueprint $table) {
            $table->date('defect_found_date_end')->nullable()->after('defect_found_date')->comment('End date for defect date range');
            $table->text('spreading_detail')->nullable()->after('possibility_spreading')->comment('Detail when possibility of defects spreading = YES');
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_primary', function (Blueprint $table) {
            $table->dropColumn(['defect_found_date_end', 'spreading_detail']);
        });
    }
};
