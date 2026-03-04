<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Q&A - Questions (threaded)
        Schema::create('t_cch_questions', function (Blueprint $table) {
            $table->id('question_id');
            $table->unsignedBigInteger('cch_id');
            $table->unsignedBigInteger('asked_by');
            $table->text('question_text');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('asked_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('asked_by')->references('id')->on('cch_users');
        });

        // Q&A - Responses
        Schema::create('t_cch_question_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('responded_by');
            $table->text('response_text');
            $table->timestamp('responded_at')->useCurrent();

            $table->foreign('question_id')->references('question_id')->on('t_cch_questions')->cascadeOnDelete();
            $table->foreign('responded_by')->references('id')->on('cch_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_question_responses');
        Schema::dropIfExists('t_cch_questions');
    }
};
