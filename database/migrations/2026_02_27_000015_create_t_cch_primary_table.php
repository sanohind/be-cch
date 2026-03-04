<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 2 - Primary Information
        Schema::create('t_cch_primary', function (Blueprint $table) {
            $table->id('primary_id');
            $table->unsignedBigInteger('cch_id')->unique();

            // Item 1
            $table->unsignedBigInteger('failure_mode_id');

            // Item 2
            $table->date('defect_found_date');

            // Item 3 - defect qty (agreed with customer)
            $table->integer('defect_qty')->default(0);

            // Item 5 - Comment
            $table->text('comment')->nullable();

            // Item 6 & 7
            $table->string('part_number', 100);
            $table->string('part_name', 200)->comment('Must use Roman alphabet, not Katakana');

            // Item 8
            $table->unsignedBigInteger('product_category_id')->nullable();

            // Item 9
            $table->unsignedBigInteger('product_family_id')->nullable();

            // Item 10
            $table->enum('phase', [
                'Trial',
                'Trail_for_mass_production',
                'Mass_production_first_3months',
                'Mass_production_after_3months',
                'Service_parts',
            ]);

            // Item 11
            $table->enum('product_supply_form', ['Knock_down_product', 'Pass_through_product', 'Not_subject']);

            // Item 12-15
            $table->text('estimation_occurrence_outflow')->nullable();
            $table->text('possibility_spreading')->nullable();
            $table->text('qa_director_comment')->nullable();
            $table->text('author_comment')->nullable();

            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('failure_mode_id')->references('failure_mode_id')->on('m_failure_modes');
            $table->foreign('product_category_id')->references('category_id')->on('m_product_categories')->nullOnDelete();
            $table->foreign('product_family_id')->references('family_id')->on('m_product_families')->nullOnDelete();
        });

        // Block 2 - Photos
        Schema::create('t_cch_primary_photos', function (Blueprint $table) {
            $table->id('photo_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('photo_type', ['overall', 'rejection_area']);
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
        Schema::dropIfExists('t_cch_primary_photos');
        Schema::dropIfExists('t_cch_primary');
    }
};
