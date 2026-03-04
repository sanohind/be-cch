<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 1 - Basic
        Schema::create('t_cch_basic', function (Blueprint $table) {
            $table->id('basic_id');
            $table->unsignedBigInteger('cch_id')->unique();

            // Item 1
            $table->string('subject', 500);

            // Item 2
            $table->unsignedBigInteger('division_id');

            // Item 3
            $table->enum('report_category', ['Customer', 'Market', 'Internal']);

            // Item 4
            $table->unsignedBigInteger('customer_id');

            // Item 5
            $table->unsignedBigInteger('customer_plant_id')->nullable();

            // Item 6
            $table->enum('defect_class', ['Quality trouble', 'Delivery trouble']);

            // Item 7
            $table->enum('line_stop', ['YES', 'NO']);

            // Item 8
            $table->enum('count_by_customer', ['YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined'])
                  ->default('Undetermined');

            // Item 9
            $table->date('month_of_counted')->nullable();

            // Item 10
            $table->enum('importance_internal', ['A', 'B', 'C', 'M', 'Not_Applicable']);
            $table->enum('importance_internal_class', ['1', '2', '3', '4'])->nullable();

            // Item 11
            $table->enum('importance_customer', ['A', 'B', 'C', 'Undetermined', 'Not_Applicable'])->default('Undetermined');
            $table->string('toyota_rank', 50)->nullable()->comment('Auto-converted from customer rank if is_toyota=true');

            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('division_id')->references('division_id')->on('m_divisions');
            $table->foreign('customer_id')->references('customer_id')->on('m_customers');
            $table->foreign('customer_plant_id')->references('customer_plant_id')->on('m_customer_plants')->nullOnDelete();
        });

        // Block 1 - Attachments
        Schema::create('t_cch_basic_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('file_name', 300);
            $table->string('file_path', 500);
            $table->integer('file_size_kb')->nullable()->comment('Max: 10240 KB (10 MB)');
            $table->unsignedBigInteger('uploaded_by')->comment('FK → cch_users.id');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('cch_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_basic_attachments');
        Schema::dropIfExists('t_cch_basic');
    }
};
