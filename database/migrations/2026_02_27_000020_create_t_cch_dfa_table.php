<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Block 7 - Defective Factor Analysis
 * HANYA berlaku untuk:
 *   - Sanoh Industrial Japan (divisionType = 'Japan')
 *   - Supplier dari Jepang (supplier.country = 'Japan')
 * Visibility dikontrol di aplikasi (bukan di DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Block 7 - DFA Header
        Schema::create('t_cch_dfa', function (Blueprint $table) {
            $table->id('dfa_id');
            $table->unsignedBigInteger('cch_id')->unique();
            $table->text('occurrence_mechanism')->nullable();
            $table->text('outflow_mechanism')->nullable();
            $table->text('author_comment')->nullable();
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });

        // Block 7 - Attachments
        Schema::create('t_cch_dfa_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('attachment_type', ['analysis_sheet', 'corrective_action_sheet']);
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
        Schema::dropIfExists('t_cch_dfa_attachments');
        Schema::dropIfExists('t_cch_dfa');
    }
};
