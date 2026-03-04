<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_occurrence_causes', function (Blueprint $table) {
            $table->id('cause_id');
            $table->enum('primary_factor', ['Man', 'Method', 'Machine', 'Material', 'Design']);
            $table->string('secondary_cause', 200);
            $table->text('cause_detail')->nullable();
            $table->enum('cause_type', ['occurrence', 'outflow', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_occurrence_causes');
    }
};
