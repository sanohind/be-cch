<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 3 - SRTA Header
        Schema::create('t_cch_srta', function (Blueprint $table) {
            $table->id('srta_id');
            $table->unsignedBigInteger('cch_id')->unique();
            $table->text('author_comment')->nullable();
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });

        // Block 3 - SRTA Screening Rows (multiple per CCH)
        Schema::create('t_cch_srta_screening', function (Blueprint $table) {
            $table->id('screening_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('location', ['Customer_Completed_cars', 'Customer_Sorting', 'Depot', 'Internal', 'Supplier']);
            $table->integer('ng_qty')->default(0);
            $table->integer('ok_qty')->default(0);
            $table->enum('action_taken', ['Conversion', 'Replacement', 'None'])->default('None');
            $table->text('action_result')->nullable();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });

        // Block 3 - SRTA Attachments
        Schema::create('t_cch_srta_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('file_name', 300);
            $table->string('file_path', 500);
            $table->integer('file_size_kb')->nullable()->comment('Max: 10240 KB');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_srta_attachments');
        Schema::dropIfExists('t_cch_srta_screening');
        Schema::dropIfExists('t_cch_srta');
    }
};
