<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_cch_occurrence_pic', function (Blueprint $table) {
            $table->id('occurrence_pic_id');
            $table->unsignedBigInteger('cch_id');
            $table->unsignedBigInteger('pic_user_id');
            $table->enum('defect_made_by', ['Own_plant', 'Sanoh_group', 'Supplier', 'Unknown']);

            // If Own_plant / Sanoh_group (now: department/division instead of plant)
            $table->unsignedBigInteger('division_id')->nullable();
            $table->string('responsible_office', 200)->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->text('process_comment')->nullable();

            // If Supplier
            $table->string('supplier_id', 50)->nullable(); // ERP bp_code
            $table->unsignedBigInteger('supplier_process_id')->nullable();
            $table->text('supplier_process_comment')->nullable();

            $table->timestamps();

            $table->unique(['cch_id', 'pic_user_id']);
            $table->index(['cch_id', 'division_id']);

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('pic_user_id')->references('id')->on('cch_users')->cascadeOnDelete();
            $table->foreign('process_id')->references('process_id')->on('m_processes')->nullOnDelete();
            $table->foreign('supplier_process_id')->references('process_id')->on('m_processes')->nullOnDelete();
        });

        Schema::create('t_cch_outflow_pic', function (Blueprint $table) {
            $table->id('outflow_pic_id');
            $table->unsignedBigInteger('cch_id');
            $table->unsignedBigInteger('pic_user_id');
            $table->enum('defect_made_by', ['Own_plant', 'Other_sanoh_plant', 'Supplier', 'Unknown']);

            // If Own_plant / Other_sanoh_plant (now: department/division instead of plant)
            $table->unsignedBigInteger('division_id')->nullable();
            $table->string('responsible_office', 200)->nullable();
            $table->string('responsible_department_detail', 200)->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->text('process_comment')->nullable();

            // If Supplier
            $table->string('supplier_id', 50)->nullable(); // ERP bp_code
            $table->unsignedBigInteger('supplier_process_id')->nullable();
            $table->text('supplier_process_comment')->nullable();

            $table->timestamps();

            $table->unique(['cch_id', 'pic_user_id']);
            $table->index(['cch_id', 'division_id']);

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('pic_user_id')->references('id')->on('cch_users')->cascadeOnDelete();
            $table->foreign('process_id')->references('process_id')->on('m_processes')->nullOnDelete();
            $table->foreign('supplier_process_id')->references('process_id')->on('m_processes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_outflow_pic');
        Schema::dropIfExists('t_cch_occurrence_pic');
    }
};

