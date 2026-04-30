<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_cch_comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->unsignedBigInteger('cch_id');
            $table->unsignedTinyInteger('block_number'); // 1,2,3,4,5,8,9,10
            $table->enum('comment_type', ['question', 'answer', 'response']);
            $table->string('subject', 200);
            $table->text('description');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['cch_id', 'block_number']);
            $table->index(['cch_id', 'created_by']);

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_comments');
    }
};

