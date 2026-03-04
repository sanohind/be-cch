<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_product_categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_code', 50)->unique();
            $table->string('category_name', 200);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_product_categories');
    }
};
