<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_plants', function (Blueprint $table) {
            $table->id('plant_id');
            $table->string('plant_code', 50)->unique();
            $table->string('plant_name', 200);
            $table->string('office', 200)->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('division_id')->references('division_id')->on('m_divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_plants');
    }
};
