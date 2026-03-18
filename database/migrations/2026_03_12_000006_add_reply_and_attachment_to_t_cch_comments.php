<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_cch_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_comment_id')->nullable()->after('comment_type');
            $table->string('attachment_path')->nullable()->after('description');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->integer('attachment_size_kb')->nullable()->after('attachment_name');

            $table->index('parent_comment_id');
            $table->foreign('parent_comment_id')
                  ->references('comment_id')
                  ->on('t_cch_comments')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('t_cch_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_comment_id']);
            $table->dropIndex(['parent_comment_id']);
            $table->dropColumn(['parent_comment_id', 'attachment_path', 'attachment_name', 'attachment_size_kb']);
        });
    }
};

