<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_divisions', function (Blueprint $table) {
            $table->id('division_id');
            $table->string('division_code', 50)->unique();
            $table->string('division_name', 200);
            $table->enum('division_type', ['Japan', 'Overseas']);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_divisions');
    }
};
