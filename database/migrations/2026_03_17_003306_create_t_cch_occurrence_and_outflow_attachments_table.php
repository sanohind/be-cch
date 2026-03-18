<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_cch_occurrence_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->float('file_size_kb')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('cch_users')->cascadeOnDelete();
        });

        Schema::create('t_cch_outflow_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->float('file_size_kb')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('cch_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_outflow_attachments');
        Schema::dropIfExists('t_cch_occurrence_attachments');
    }
};
