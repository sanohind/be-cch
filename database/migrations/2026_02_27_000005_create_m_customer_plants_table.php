<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_customer_plants', function (Blueprint $table) {
            $table->id('customer_plant_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('plant_name', 200);
            $table->string('plant_location', 200)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('customer_id')->references('customer_id')->on('m_customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_customer_plants');
    }
};
