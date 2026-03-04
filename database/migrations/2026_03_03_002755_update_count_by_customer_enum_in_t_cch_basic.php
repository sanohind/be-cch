<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter ENUM to add 'Not_Applicable' option
        DB::statement("ALTER TABLE `t_cch_basic` MODIFY `count_by_customer` ENUM('YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined', 'Not_Applicable') DEFAULT 'Undetermined'");
    }

    public function down(): void
    {
        // Revert to original enum values (will fail if rows have 'Not_Applicable')
        DB::statement("ALTER TABLE `t_cch_basic` MODIFY `count_by_customer` ENUM('YES', 'NO_Responsibility', 'NO_No_Responsibility', 'Undetermined') DEFAULT 'Undetermined'");
    }
};
