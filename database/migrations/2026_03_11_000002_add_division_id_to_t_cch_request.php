<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_request', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable()->after('department');
            $table->index(['cch_id', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_request', function (Blueprint $table) {
            $table->dropIndex(['cch_id', 'division_id']);
            $table->dropColumn('division_id');
        });
    }
};

