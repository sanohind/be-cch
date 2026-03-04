<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_closing_attachments', function (Blueprint $table) {
            $table->enum('attachment_type', ['final_report', 'supporting'])
                  ->default('supporting')
                  ->after('cch_id')
                  ->comment('final_report = Item 5 Final Report; supporting = lampiran tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_closing_attachments', function (Blueprint $table) {
            $table->dropColumn('attachment_type');
        });
    }
};
