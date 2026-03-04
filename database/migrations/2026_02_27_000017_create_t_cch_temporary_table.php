<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 4 - Temporary Countermeasures
        Schema::create('t_cch_temporary', function (Blueprint $table) {
            $table->id('temporary_id');
            $table->unsignedBigInteger('cch_id')->unique();
            $table->text('author_comment');
            $table->timestamps();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
        });

        // Block 4 - Attachments
        Schema::create('t_cch_temporary_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('file_name', 300);
            $table->string('file_path', 500);
            $table->integer('file_size_kb')->nullable()->comment('Max: 10240 KB');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('cch_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_temporary_attachments');
        Schema::dropIfExists('t_cch_temporary');
    }
};
