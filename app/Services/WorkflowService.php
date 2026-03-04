<?php

namespace App\Services;

use App\Models\Cch;

class WorkflowService
{
    /**
     * Check if user is allowed to edit this block, and if previous block is submitted.
     * Also auto-assigns admin_in_charge if this is the first edit.
     * Throws an exception or returns error array.
     */
    public static function checkBlockAccess(Cch $cch, array $sphereUser, int $blockNumber)
    {
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = $sphereUser['id'];

        // Blocks 1
        if ($blockNumber === 1) {
            if ($cch->input_by != $userId && !in_array($roleLevel, [1, 2])) {
                return ['success' => false, 'message' => 'Hanya pembuat (requester) atau admin yang dapat mengedit tiket ini'];
            }
            return null; // OK
        }

        // Blocks 2 to 9
        if ($blockNumber >= 2 && $blockNumber <= 9) {
            // Check Previous Block Submitted
            $prevBlockStatus = $blockNumber == 2 ? $cch->b1_status : $cch->{'b'.($blockNumber - 1).'_status'};
            if ($prevBlockStatus !== 'submitted') {
                return ['success' => false, 'message' => "Block " . ($blockNumber - 1) . " belum disubmit secara final."];
            }

            // Check Admin role (1 = Superadmin, 2 = Admin)
            if (!in_array($roleLevel, [1, 2])) {
                return ['success' => false, 'message' => "Hanya Admin (Level 1 atau 2) yang dapat mengisi form ini."];
            }

            // Check Admin Assignment
            if (!$cch->admin_in_charge) {
                // Auto assign if none
                $cch->update(['admin_in_charge' => $userId]);
            } else {
                if ($cch->admin_in_charge != $userId && $roleLevel !== 1) { // Superadmin can override, normally only assigned admin
                    return ['success' => false, 'message' => "Tiket ini sudah di-handle oleh admin lain."];
                }
            }
            return null;
        }

        // Block 10
        if ($blockNumber === 10) {
            $prevBlockStatus = $cch->b9_status;
            if ($prevBlockStatus !== 'submitted') {
                return ['success' => false, 'message' => "Block 9 belum disubmit secara final."];
            }

            $basic = $cch->basic;
            $importance = $basic ? $basic->importance_internal : '';

            if ($importance === 'A') {
                if ($roleLevel !== 4 && $roleLevel !== 1) { // 4 = Presdir/GM, 1 = superadmin override
                    return ['success' => false, 'message' => "Dikarenakan Ranking internal tiket adalah A, tiket hanya dapat di close oleh Presdir/GM (Level 4)."];
                }
            } else {
                if ($roleLevel > 5 && $roleLevel !== 1) { // 5 = Manager. Anyone level 1, 2, 4, 5
                    return ['success' => false, 'message' => "Tiket hanya dapat di close oleh Manager (Level 5) keatas."];
                }
            }

            return null; // OK
        }

        return ['success' => false, 'message' => 'Status block tidak valid'];
    }

    /**
     * Update the cch block status and overall status
     */
    public static function updateBlockStatus(Cch $cch, int $blockNumber, bool $isDraft)
    {
        $statusKey = "b{$blockNumber}_status";
        $newBlockStatus = $isDraft ? 'draft' : 'submitted';

        $cch->$statusKey = $newBlockStatus;

        // Overall logic
        if ($blockNumber === 1 && !$isDraft && $cch->status === 'draft') {
            $cch->status = 'submitted';
            $cch->submitted_at = now();
        }

        if ($blockNumber >= 2 && $blockNumber <= 9 && $cch->status === 'submitted') {
            $cch->status = 'in_progress';
        }

        if ($blockNumber === 10 && !$isDraft) {
            $cch->status = 'closed_by_manager';
        }

        $cch->save();
    }

    /**
     * Optional rules conditionally
     */
    public static function applyDraftRules(array $rules, bool $isDraft)
    {
        if ($isDraft) {
            foreach ($rules as $key => $rule) {
                if (is_string($rule)) {
                    $rules[$key] = str_replace(['required|', 'required'], ['nullable|', 'nullable'], $rule);
                } elseif (is_array($rule)) {
                    if (($idx = array_search('required', $rule)) !== false) {
                        $rule[$idx] = 'nullable';
                        $rules[$key] = $rule;
                    }
                }
            }
        }
        return $rules;
    }

    /**
     * Check if a user is allowed to view a block that is currently in "draft" status.
     * Rule: Only the allowed editor (requester for b1, admin in charge for b2-b9, etc) or superadmin can view it.
     */
    public static function checkCanViewDraft(Cch $cch, array $sphereUser, int $blockNumber): bool
    {
        $userId = $sphereUser['id'] ?? null;
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);

        // Superadmin always sees everything
        if ($roleLevel === 1) {
            return true;
        }

        if ($blockNumber === 1) {
            return $cch->input_by == $userId;
        }

        if ($blockNumber >= 2 && $blockNumber <= 9) {
            // For block 2-9, only the assigned admin in charge can view the draft.
            // If admin_in_charge is not set yet, logically no one drafted it yet, but just in case.
            return $cch->admin_in_charge == $userId;
        }

        if ($blockNumber === 10) {
            // Manager level (<= 5)
            return $roleLevel <= 5;
        }

        return false;
    }
}
