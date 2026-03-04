<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE: CCH menggunakan SSO via Sphere — tidak butuh tabel 'users' standar Laravel.
 * Auth user dikelola di tabel 'cch_users' (SSO bridge).
 * Migration ini hanya membuat tabel sessions & password_reset_tokens (dibutuhkan framework).
 */
return new class extends Migration
{
    public function up(): void
    {
        // CCH tidak menggunakan tabel 'users' standar — auth via Sphere SSO
        // User data ada di tabel 'cch_users'

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
