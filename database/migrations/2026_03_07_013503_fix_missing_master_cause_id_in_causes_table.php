<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_cch_causes', function (Blueprint $table) {
            if (!Schema::hasColumn('t_cch_causes', 'master_cause_id')) {
                $table->unsignedBigInteger('master_cause_id')->nullable()->after('primary_factor')
                      ->comment('Pilih cause dari m_causes DB');
                $table->foreign('master_cause_id')->references('id')->on('m_causes')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_cch_causes', function (Blueprint $table) {
            if (Schema::hasColumn('t_cch_causes', 'master_cause_id')) {
                $table->dropForeign(['master_cause_id']);
                $table->dropColumn('master_cause_id');
            }
        });
    }
};
