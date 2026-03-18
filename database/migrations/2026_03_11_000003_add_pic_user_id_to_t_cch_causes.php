<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_causes', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_user_id')->nullable()->after('cch_id');
            $table->index(['cch_id', 'cause_type', 'pic_user_id']);
            $table->foreign('pic_user_id')->references('id')->on('cch_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_causes', function (Blueprint $table) {
            $table->dropForeign(['pic_user_id']);
            $table->dropIndex(['cch_id', 'cause_type', 'pic_user_id']);
            $table->dropColumn('pic_user_id');
        });
    }
};

