<?php

namespace App\Services;

use App\Models\Cch;

class WorkflowService
{
    private static function isSuperadmin(int $roleLevel): bool
    {
        return $roleLevel === 1;
    }

    private static function isAdmin(int $roleLevel): bool
    {
        // Existing convention: 1 = Superadmin, 2 = Admin
        return in_array($roleLevel, [1, 2], true);
    }

    private static function isManagerOrPresdirGm(int $roleLevel): bool
    {
        // Existing convention: 4 = Presdir/GM, 5 = Manager
        return in_array($roleLevel, [1, 4, 5], true);
    }

    private static function prevBlockStatusFor(Cch $cch, int $blockNumber): ?string
    {
        // New flow: 1 -> 2 -> 3 -> 4 -> 5 -> 8 -> 9 -> 10
        $map = [
            2  => 'b1_status',
            3  => 'b2_status',
            4  => 'b3_status',
            5  => 'b4_status',
            8  => 'b5_status',
            9  => 'b8_status',
            10 => 'b9_status',
            // Legacy blocks kept for compatibility (will be deprecated)
            6  => 'b5_status',
            7  => 'b6_status',
        ];
        $key = $map[$blockNumber] ?? null;
        return $key ? ($cch->$key ?? null) : null;
    }

    /**
     * Check if user is allowed to edit this block, and if previous block is submitted.
     * Also auto-assigns admin_in_charge if this is the first edit.
     * Throws an exception or returns error array.
     */
    public static function checkBlockAccess(Cch $cch, array $sphereUser, int $blockNumber)
    {
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = $sphereUser['id'];

        // Global lock: closed / closed_by_manager tickets are immutable
        if (in_array($cch->status ?? '', ['closed', 'closed_by_manager'], true)) {
            return ['success' => false, 'message' => 'Tiket sudah ditutup dan tidak dapat diedit.'];
        }

        // Block 1: hanya admin penerbit (input_by) / superadmin
        if ($blockNumber === 1) {
            if (!self::isAdmin($roleLevel)) {
                return ['success' => false, 'message' => 'Hanya Admin yang dapat menerbitkan dan mengisi Block 1.'];
            }
            if ($cch->input_by != $userId && !self::isSuperadmin($roleLevel)) {
                return ['success' => false, 'message' => 'Hanya Admin yang menerbitkan CCH ini yang dapat mengedit Block 1.'];
            }
            return null;
        }

        // Blocks 2-5, 8-9: publisher/admin_in_charge only (PIC for 8/9 handled in later module)
        if (in_array($blockNumber, [2, 3, 4, 5, 6, 7, 8, 9], true)) {
            $prevStatus = self::prevBlockStatusFor($cch, $blockNumber);
            if ($prevStatus !== 'submitted') {
                return ['success' => false, 'message' => 'Block sebelumnya belum disubmit secara final.'];
            }

            if (!self::isAdmin($roleLevel)) {
                return ['success' => false, 'message' => 'Hanya Admin yang dapat mengisi form ini.'];
            }

            // Enforce owner: hanya user yang menambahkan CCH (input_by) yang boleh mengedit
            if ($cch->input_by != $userId && !self::isSuperadmin($roleLevel)) {
                return ['success' => false, 'message' => 'Hanya user yang menambahkan CCH ini yang dapat mengedit blok.'];
            }

            return null;
        }

        // Block 10
        if ($blockNumber === 10) {
            $prevStatus = self::prevBlockStatusFor($cch, 10);
            if ($prevStatus !== 'submitted') {
                return ['success' => false, 'message' => 'Block 9 belum disubmit secara final.' ];
            }

            // Filling closing block: hanya pembuat CCH (input_by) atau superadmin yang boleh mengedit
            if (self::isSuperadmin($roleLevel)) {
                return null;
            }

            if ($cch->input_by == $userId) {
                return null;
            }

            // Manager/PresdirGM may not edit fields in Block 10 (only close trigger via separate endpoint)
            return ['success' => false, 'message' => 'Hanya pembuat CCH ini yang dapat mengisi Block 10.' ];
        }

        return ['success' => false, 'message' => 'Status block tidak valid'];
    }

    /**
     * Update the cch block status and overall status
     */
    public static function updateBlockStatus(Cch $cch, int $blockNumber, bool $isDraft, ?int $userId = null, ?string $submitterName = null)
    {
        $statusKey = "b{$blockNumber}_status";
        $newBlockStatus = $isDraft ? 'draft' : 'submitted';

        $cch->$statusKey = $newBlockStatus;

        // Overall logic
        if ($blockNumber === 1 && !$isDraft) {
            $cch->status = 'submitted';
            $cch->submitted_at = now();
            // Assign admin_in_charge at first publish (Block 1 submit)
            if ($userId !== null && !$cch->admin_in_charge) {
                $cch->admin_in_charge = $userId;
            }
        }

        if (!$isDraft && in_array($blockNumber, [2, 3, 4, 5, 8, 9, 10, 6, 7], true)) {
            if (in_array($cch->status, ['submitted', 'draft'], true)) {
                $cch->status = 'in_progress';
            }
        }

        $cch->save();

        // Send block submission email for blocks 2-10 (Block 1 handled separately in CchController)
        if (!$isDraft && $blockNumber !== 1) {
            try {
                $name = $submitterName;
                if (!$name && $userId) {
                    $user = \App\Models\CchUser::select('full_name', 'username')->find($userId);
                    $name = $user?->full_name ?? $user?->username ?? 'Admin';
                }
                \App\Services\CchNotificationService::notifyBlockSubmitted($cch, $blockNumber, $name ?? 'Admin');
            } catch (\Throwable $e) {
                \Log::error('[WorkflowService] Block notification failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Sanitize validated data for draft save: convert empty strings to null
     * agar kolom nullable di database bisa menerima nilai NULL.
     */
    public static function sanitizeDraftData(array $data, int $blockNumber): array
    {
        if ($blockNumber === 2) {
            $keys = ['failure_mode_id', 'defect_found_date', 'defect_found_date_end', 'part_number', 'part_name', 'phase', 'product_supply_form', 'product_category_id', 'product_family_id'];
        } elseif ($blockNumber === 4 || $blockNumber === 6) {
            $keys = ['author_comment'];
        } elseif ($blockNumber === 8 || $blockNumber === 9) {
            $keys = ['defect_made_by', 'responsible_plant_id', 'process_id', 'responsible_office', 'process_comment', 'supplier_id', 'supplier_process_id', 'supplier_process_comment', 'author_comment'];
        } elseif ($blockNumber === 10) {
            $keys = ['importance_customer_final', 'count_by_customer_final', 'currency_id', 'is_recurrence', 'horizontal_deployment'];
        } else {
            return $data;
        }

        foreach ($keys as $k) {
            if (array_key_exists($k, $data) && ($data[$k] === '' || $data[$k] === null)) {
                $data[$k] = null;
            }
        }
        return $data;
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
        if (self::isSuperadmin($roleLevel)) {
            return true;
        }

        if ($blockNumber === 1) {
            return $cch->input_by == $userId;
        }

        if (in_array($blockNumber, [2, 3, 4, 5, 8, 9, 6, 7], true)) {
            // Draft blocks are private to the owner admin.
            $ownerId = $cch->admin_in_charge ?: $cch->input_by;
            return $ownerId == $userId;
        }

        if ($blockNumber === 10) {
            $ownerId = $cch->admin_in_charge ?: $cch->input_by;
            return $ownerId == $userId;
        }

        return false;
    }
}
