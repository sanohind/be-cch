<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSO Bridge Table
 *
 * Tabel ini berfungsi sebagai jembatan antara Sphere SSO dan sistem CCH.
 * Data diisi otomatis saat user pertama kali login via Sphere SSO.
 * Tidak ada password — auth sepenuhnya via Sphere JWT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cch_users', function (Blueprint $table) {
            $table->id();

            // Sphere SSO Reference
            $table->unsignedBigInteger('sphere_user_id')->unique()->comment('ID user di Sphere SSO (JWT sub)');
            $table->string('username', 100)->unique();
            $table->string('full_name', 200);
            $table->string('email', 200)->unique();

            // Data dari Sphere (di-cache)
            $table->string('sphere_role', 100)->nullable()->comment('Role slug dari Sphere');
            $table->integer('sphere_role_level')->nullable();
            $table->unsignedBigInteger('sphere_department_id')->nullable()->comment('Department ID dari Sphere');
            $table->string('sphere_department_code', 100)->nullable();
            $table->string('sphere_department_name', 200)->nullable();

            // CCH-specific assignment (di-set oleh admin CCH)
            $table->unsignedBigInteger('division_id')->nullable()->comment('Mapping ke division CCH (opsional)');
            $table->unsignedBigInteger('plant_id')->nullable()->comment('Plant yang di-assign ke user ini');
            $table->enum('cch_role', ['operator', 'qa_manager', 'division_manager', 'admin'])
                  ->default('operator')
                  ->comment('Role di sistem CCH, di-assign manual oleh admin CCH');

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->foreign('division_id')->references('division_id')->on('m_divisions')->nullOnDelete();
            $table->foreign('plant_id')->references('plant_id')->on('m_plants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cch_users');
    }
};
