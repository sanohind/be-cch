<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('customer_code', 50)->unique();
            $table->string('customer_name', 200);
            $table->boolean('has_rank_system')->default(true);
            $table->boolean('is_toyota')->default(false);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_customers');
    }
};
