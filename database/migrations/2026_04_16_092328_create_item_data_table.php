<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_data', function (Blueprint $table) {
            $table->id();
            $table->string('item')->nullable();
            $table->string('description')->nullable();
            $table->string('item_group')->nullable();
            $table->string('group_desc')->nullable();
            $table->string('desc_2')->nullable();
            $table->string('old_partno')->nullable();
            $table->string('unit')->nullable();
            $table->string('div_code')->nullable();
            $table->string('divisi')->nullable();
            $table->string('customer')->nullable();
            $table->string('customer_desc')->nullable();
            $table->string('model')->nullable();
            $table->string('unique_code')->nullable();
            $table->string('classification')->nullable();
            $table->string('destination')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_data');
    }
};
