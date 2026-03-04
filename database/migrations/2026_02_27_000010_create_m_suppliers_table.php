<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('supplier_code', 50)->unique();
            $table->string('supplier_name', 200);
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_suppliers');
    }
};
