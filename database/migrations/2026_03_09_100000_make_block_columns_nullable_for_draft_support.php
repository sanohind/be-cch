<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Membuat kolom yang required pada submit menjadi nullable untuk mendukung Save Draft.
 * Save draft boleh menyimpan data partial (kolom kosong = NULL).
 * Submit final tetap divalidasi required di aplikasi (WorkflowService::applyDraftRules).
 */
return new class extends Migration
{
    public function up(): void
    {
        // t_cch_primary - Block 2
        Schema::table('t_cch_primary', function (Blueprint $table) {
            $table->unsignedBigInteger('failure_mode_id')->nullable()->change();
            $table->date('defect_found_date')->nullable()->change();
            $table->string('part_number', 100)->nullable()->change();
            $table->string('part_name', 200)->nullable()->change();
            $table->enum('phase', ['Trial', 'Trail_for_mass_production', 'Mass_production_first_3months', 'Mass_production_after_3months', 'Service_parts'])->nullable()->change();
            $table->enum('product_supply_form', ['Knock_down_product', 'Pass_through_product', 'Not_subject'])->nullable()->change();
        });

        // t_cch_closing - Block 10 (enum tetap, tambah nullable)
        Schema::table('t_cch_closing', function (Blueprint $table) {
            $table->enum('importance_customer_final', ['A', 'B', 'C', 'Undetermined', 'Not_Applicable'])->nullable()->change();
            $table->enum('count_by_customer_final', ['YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined'])->nullable()->change();
            $table->enum('is_recurrence', ['YES', 'NO'])->nullable()->change();
            $table->enum('horizontal_deployment', ['YES', 'NO'])->nullable()->change();
        });

        // t_cch_temporary - Block 4
        Schema::table('t_cch_temporary', function (Blueprint $table) {
            $table->text('author_comment')->nullable()->change();
        });

        // t_cch_ra - Block 6
        Schema::table('t_cch_ra', function (Blueprint $table) {
            $table->text('author_comment')->nullable()->change();
        });

        // t_cch_occurrence - Block 8
        Schema::table('t_cch_occurrence', function (Blueprint $table) {
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown'])->nullable()->change();
        });

        // t_cch_outflow - Block 9
        Schema::table('t_cch_outflow', function (Blueprint $table) {
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_primary', function (Blueprint $table) {
            $table->unsignedBigInteger('failure_mode_id')->nullable(false)->change();
            $table->date('defect_found_date')->nullable(false)->change();
            $table->string('part_number', 100)->nullable(false)->change();
            $table->string('part_name', 200)->nullable(false)->change();
            $table->enum('phase', ['Trial', 'Trail_for_mass_production', 'Mass_production_first_3months', 'Mass_production_after_3months', 'Service_parts'])->nullable(false)->change();
            $table->enum('product_supply_form', ['Knock_down_product', 'Pass_through_product', 'Not_subject'])->nullable(false)->change();
        });

        Schema::table('t_cch_closing', function (Blueprint $table) {
            $table->enum('importance_customer_final', ['A', 'B', 'C', 'Undetermined', 'Not_Applicable'])->nullable(false)->change();
            $table->enum('count_by_customer_final', ['YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined'])->nullable(false)->change();
            $table->enum('is_recurrence', ['YES', 'NO'])->nullable(false)->change();
            $table->enum('horizontal_deployment', ['YES', 'NO'])->nullable(false)->change();
        });

        Schema::table('t_cch_temporary', function (Blueprint $table) {
            $table->text('author_comment')->nullable(false)->change();
        });

        Schema::table('t_cch_ra', function (Blueprint $table) {
            $table->text('author_comment')->nullable(false)->change();
        });

        Schema::table('t_cch_occurrence', function (Blueprint $table) {
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown'])->nullable(false)->change();
        });

        Schema::table('t_cch_outflow', function (Blueprint $table) {
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown'])->nullable(false)->change();
        });
    }
};
