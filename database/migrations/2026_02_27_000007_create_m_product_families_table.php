<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_product_families', function (Blueprint $table) {
            $table->id('family_id');
            $table->unsignedBigInteger('category_id');
            $table->string('family_code', 50)->unique();
            $table->string('family_name', 200);
            $table->boolean('is_active')->default(true);

            $table->foreign('category_id')->references('category_id')->on('m_product_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_product_families');
    }
};
