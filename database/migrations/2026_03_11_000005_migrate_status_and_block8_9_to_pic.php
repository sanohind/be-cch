<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Normalize legacy statuses into new flow
        DB::table('t_cch')
            ->whereIn('status', ['closed_by_manager', 'close_requested'])
            ->update(['status' => 'closed']);

        // 2) Shrink ENUM to new set (after data normalized)
        DB::statement("ALTER TABLE t_cch MODIFY COLUMN status ENUM('draft','submitted','in_progress','closed') DEFAULT 'draft'");

        // 3) Migrate legacy Block 8/9 single-header data into per-PIC tables
        $cchs = DB::table('t_cch')->select('cch_id', 'input_by', 'admin_in_charge')->get();

        foreach ($cchs as $cch) {
            $ownerId = (int)($cch->admin_in_charge ?: $cch->input_by);
            if (!$ownerId) continue;

            // Block 8 (occurrence)
            $occ = DB::table('t_cch_occurrence')->where('cch_id', $cch->cch_id)->first();
            if ($occ) {
                $divisionId = null;
                if (!empty($occ->responsible_plant_id)) {
                    $divisionId = DB::table('m_plants')->where('plant_id', $occ->responsible_plant_id)->value('division_id');
                }

                DB::table('t_cch_occurrence_pic')->updateOrInsert(
                    ['cch_id' => $cch->cch_id, 'pic_user_id' => $ownerId],
                    [
                        'defect_made_by' => $occ->defect_made_by,
                        'division_id' => $divisionId,
                        'responsible_office' => $occ->responsible_office,
                        'process_id' => $occ->process_id,
                        'process_comment' => $occ->process_comment,
                        'supplier_id' => $occ->supplier_id,
                        'supplier_process_id' => $occ->supplier_process_id,
                        'supplier_process_comment' => $occ->supplier_process_comment,
                        'created_at' => $occ->created_at ?? now(),
                        'updated_at' => $occ->updated_at ?? now(),
                    ]
                );

                DB::table('t_cch_causes')
                    ->where('cch_id', $cch->cch_id)
                    ->where('cause_type', 'occurrence')
                    ->whereNull('pic_user_id')
                    ->update(['pic_user_id' => $ownerId]);
            }

            // Block 9 (outflow)
            $out = DB::table('t_cch_outflow')->where('cch_id', $cch->cch_id)->first();
            if ($out) {
                $divisionId = null;
                if (!empty($out->responsible_plant_id)) {
                    $divisionId = DB::table('m_plants')->where('plant_id', $out->responsible_plant_id)->value('division_id');
                }

                DB::table('t_cch_outflow_pic')->updateOrInsert(
                    ['cch_id' => $cch->cch_id, 'pic_user_id' => $ownerId],
                    [
                        'defect_made_by' => $out->defect_made_by,
                        'division_id' => $divisionId,
                        'responsible_office' => $out->responsible_office,
                        'responsible_department_detail' => $out->responsible_plant_detail,
                        'process_id' => $out->process_id,
                        'process_comment' => $out->process_comment,
                        'supplier_id' => $out->supplier_id,
                        'supplier_process_id' => $out->supplier_process_id,
                        'supplier_process_comment' => $out->supplier_process_comment,
                        'created_at' => $out->created_at ?? now(),
                        'updated_at' => $out->updated_at ?? now(),
                    ]
                );

                DB::table('t_cch_causes')
                    ->where('cch_id', $cch->cch_id)
                    ->where('cause_type', 'outflow')
                    ->whereNull('pic_user_id')
                    ->update(['pic_user_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        // Re-expand ENUM to include legacy values (best-effort)
        DB::statement("ALTER TABLE t_cch MODIFY COLUMN status ENUM('draft','submitted','in_progress','close_requested','closed_by_manager','closed') DEFAULT 'draft'");

        // Data rows in *_pic tables are kept (non-destructive rollback)
    }
};

