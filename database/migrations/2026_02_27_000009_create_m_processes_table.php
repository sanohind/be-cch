<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_processes', function (Blueprint $table) {
            $table->id('process_id');
            $table->string('process_code', 50)->unique();
            $table->string('process_name', 200);
            $table->unsignedBigInteger('plant_id')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('plant_id')->references('plant_id')->on('m_plants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_processes');
    }
};
