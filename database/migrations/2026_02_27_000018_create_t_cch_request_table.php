<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 5 - Requests (multiple per CCH, each to different department)
        Schema::create('t_cch_request', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('department', 200);
            $table->date('due_date');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_request');
    }
};
