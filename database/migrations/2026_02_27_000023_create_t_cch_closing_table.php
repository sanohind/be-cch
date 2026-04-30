<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Block 10 - Closing
 * - cost_total adalah GENERATED COLUMN (computed di DB)
 * - Approval flow: submitted_by → approved_by
 */
return new class extends Migration
{
    public function up(): void
    {
        // Block 10 - Closing
        Schema::create('t_cch_closing', function (Blueprint $table) {
            $table->id('closing_id');
            $table->unsignedBigInteger('cch_id')->unique();

            // Item 1
            $table->enum('importance_customer_final', ['A', 'B', 'C', 'Undetermined', 'Not_Applicable']);

            // Item 2
            $table->enum('count_by_customer_final', ['YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined']);

            // Item 3 & 4
            $table->text('countermeasure_occurrence')->nullable();
            $table->text('countermeasure_outflow')->nullable();

            // Item 6 - Costs
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('cost_to_customer', 15, 2)->default(0);
            $table->decimal('cost_to_external', 15, 2)->default(0);
            $table->decimal('cost_internal', 15, 2)->default(0);
            // cost_total will be added as GENERATED COLUMN via raw statement after table creation

            // Item 7
            $table->enum('is_recurrence', ['YES', 'NO']);

            // Item 8
            $table->enum('horizontal_deployment', ['YES', 'NO']);

            // Item 9
            $table->text('author_comment')->nullable();

            // Approval
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('currency_id')->references('currency_id')->on('m_currencies')->nullOnDelete();
        });

        // Add GENERATED COLUMN for cost_total (MySQL computed column)
        \DB::statement('ALTER TABLE t_cch_closing ADD COLUMN cost_total DECIMAL(15,2) GENERATED ALWAYS AS (cost_to_customer + cost_to_external + cost_internal) STORED AFTER cost_internal');

        // Block 10 - Attachments
        Schema::create('t_cch_closing_attachments', function (Blueprint $table) {
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
        Schema::dropIfExists('t_cch_closing_attachments');
        Schema::dropIfExists('t_cch_closing');
    }
};
