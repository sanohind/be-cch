<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_cch', function (Blueprint $table) {
            $table->id('cch_id');
            $table->string('cch_number', 50)->unique()->comment('Format: CCH-{YYYY}-{NNNNN}');
            $table->enum('status', ['draft', 'submitted', 'in_progress', 'close_requested', 'closed'])->default('draft');
            $table->unsignedBigInteger('input_by')->comment('FK → cch_users.id');
            $table->unsignedBigInteger('division_id')->comment('FK → m_divisions.division_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('input_by')->references('id')->on('cch_users');
            $table->foreign('division_id')->references('division_id')->on('m_divisions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch');
    }
};
