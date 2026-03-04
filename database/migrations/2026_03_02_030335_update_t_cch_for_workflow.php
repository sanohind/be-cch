<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns to track the state
        Schema::table('t_cch', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_in_charge')->nullable()->after('input_by')
                  ->comment('Admin yang di-assign atau yang pertama handle tiket (Blok 2-9)');
            
            $table->enum('b1_status', ['empty', 'draft', 'submitted'])->default('draft')->after('closed_at');
            $table->enum('b2_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b1_status');
            $table->enum('b3_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b2_status');
            $table->enum('b4_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b3_status');
            $table->enum('b5_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b4_status');
            $table->enum('b6_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b5_status');
            $table->enum('b7_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b6_status');
            $table->enum('b8_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b7_status');
            $table->enum('b9_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b8_status');
            $table->enum('b10_status', ['empty', 'draft', 'submitted'])->default('empty')->after('b9_status');

            $table->foreign('admin_in_charge')->references('id')->on('cch_users');
        });

        // Modify ENUM status via raw SQL safely
        \DB::statement("ALTER TABLE t_cch MODIFY COLUMN status ENUM('draft', 'submitted', 'in_progress', 'close_requested', 'closed_by_manager', 'closed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_cch', function (Blueprint $table) {
            $table->dropForeign(['admin_in_charge']);
            $table->dropColumn([
                'admin_in_charge',
                'b1_status', 'b2_status', 'b3_status', 'b4_status', 'b5_status', 
                'b6_status', 'b7_status', 'b8_status', 'b9_status', 'b10_status'
            ]);
        });

        \DB::statement("ALTER TABLE t_cch MODIFY COLUMN status ENUM('draft', 'submitted', 'in_progress', 'close_requested', 'closed') DEFAULT 'draft'");
    }
};
