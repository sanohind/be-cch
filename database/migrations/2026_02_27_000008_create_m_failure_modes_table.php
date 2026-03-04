<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_failure_modes', function (Blueprint $table) {
            $table->id('failure_mode_id');
            $table->string('failure_mode_code', 50)->unique();
            $table->string('failure_mode_name', 200);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_failure_modes');
    }
};
