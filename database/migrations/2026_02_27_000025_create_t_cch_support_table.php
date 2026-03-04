<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Log
        Schema::create('t_cch_audit_log', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('cch_id');
            $table->string('action', 100)->comment('e.g. UPDATE_COUNT_BY_CUSTOMER, APPROVE_CLOSE');
            $table->string('block_name', 100)->nullable()->comment('e.g. basic, primary, closing');
            $table->unsignedBigInteger('changed_by');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('cch_users');
        });

        // Notifications
        Schema::create('t_cch_notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('cch_id');
            $table->enum('notification_type', ['A_Alert', 'CCH_Email', 'Close_Request', 'Question', 'Horizontal_Deployment']);
            $table->unsignedBigInteger('sent_to')->nullable()->comment('NULL = broadcast to all QA personnel');
            $table->text('message')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('cch_id')->references('cch_id')->on('t_cch')->cascadeOnDelete();
            $table->foreign('sent_to')->references('id')->on('cch_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cch_notifications');
        Schema::dropIfExists('t_cch_audit_log');
    }
};
