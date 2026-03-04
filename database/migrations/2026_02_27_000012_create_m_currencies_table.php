<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_currencies', function (Blueprint $table) {
            $table->id('currency_id');
            $table->string('currency_code', 10)->unique();
            $table->string('currency_name', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_currencies');
    }
};
