<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify ENUM to add 'attachment' value
        DB::statement("ALTER TABLE `t_cch_primary_photos` MODIFY COLUMN `photo_type` ENUM('overall', 'rejection_area', 'attachment') NOT NULL");
    }

    public function down(): void
    {
        // Revert: remove 'attachment' — rows with 'attachment' will need to be handled first
        DB::statement("UPDATE `t_cch_primary_photos` SET `photo_type` = 'overall' WHERE `photo_type` = 'attachment'");
        DB::statement("ALTER TABLE `t_cch_primary_photos` MODIFY COLUMN `photo_type` ENUM('overall', 'rejection_area') NOT NULL");
    }
};
