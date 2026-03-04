<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Block 9 - Outflow Analysis
 * Struktur serupa dengan Block 8 (Occurrence).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Block 9 - Outflow Header
        Schema::create('t_cch_outflow', function (Blueprint $table) {
            $table->id('outflow_id');
            $table->unsignedBigInteger('cch_id')->unique();
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown']);

            // If Own_plant / Sanoh_group
            $table->unsignedBigInteger('responsible_plant_id')->nullable();
            $table->string('responsible_office', 200)->nullable();
            $table->string('responsible_plant_detail', 200)->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->text('process_comment')->nullable();

            // If Supplier
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('supplier_process_id')->nullable();
            $table->text('supplier_process_comment')->nullable();

            $table->text('author_comment')->nullable();
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('responsible_plant_id')->references('plant_id')->on('m_plants')->nullOnDelete();
            $table->foreign('process_id')->references('process_id')->on('m_processes')->nullOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('m_suppliers')->nullOnDelete();
            $table->foreign('supplier_process_id')->references('process_id')->on('m_processes')->nullOnDelete();
        });

        // Block 9 - Outflow Causes (multiple rows)
        Schema::create('t_cch_outflow_causes', function (Blueprint $table) {
            $table->id('out_cause_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('outflow_type', ['Uninspected_products', 'Mishandling', 'Undetected_nonconformity']);
            $table->unsignedBigInteger('cause_id');
            $table->text('cause_description');
            $table->integer('sort_order')->default(1);

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('cause_id')->references('cause_id')->on('m_occurrence_causes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_outflow_causes');
        Schema::dropIfExists('t_cch_outflow');
    }
};
