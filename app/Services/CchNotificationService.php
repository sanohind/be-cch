<?php

namespace App\Services;

use App\Mail\CchBlockSubmittedMail;
use App\Mail\CchClosedMail;
use App\Mail\CchCommentAddedMail;
use App\Mail\CchCreatedMail;
use App\Mail\CchReadyToCloseMail;
use App\Models\Cch;
use App\Models\CchNotification;
use App\Models\CchOccurrencePic;
use App\Models\CchOutflowPic;
use App\Models\CchRequest;
use App\Models\CchUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CchNotificationService
{
    /** Sphere department ID for QC */
    private const QC_DEPT_ID = 7;

    /** Block number → human-readable name */
    private static array $blockNames = [
        1  => 'Basic Information',
        2  => 'Primary Information',
        3  => 'SRTA (Sorting/Rework/Temporary Action)',
        4  => 'Temporary Countermeasure',
        5  => 'Request to PIC',
        6  => 'Risk Assessment',
        7  => 'Design For Assembly',
        8  => 'Occurrence Analysis',
        9  => 'Outflow Analysis',
        10 => 'Closing (Block 10)',
    ];

    // ───────────────────────────────────────────────────────────────────────

    /**
     * Fired when a brand-new CCH is created (Block 1 submit / store).
     */
    public static function notifyCchCreated(Cch $cch, string $creatorName): void
    {
        try {
            $cch->loadMissing('basic');
            $rank    = $cch->basic?->importance_internal ?? '-';
            $subject = $cch->basic?->subject ?? $cch->cch_number;
            $url     = self::cchUrl($cch->cch_id);

            $users = self::getManagerQcUsers();

            // Rank A → tambahkan Presdir/GM
            if ($rank === 'A') {
                $users = array_merge($users, self::getPresdirGmUsers());
            }

            $users = self::uniqueUsers($users);

            $rankLabel = $rank === 'A' ? '[Rank A] ' : '';
            $message = "{$rankLabel}CCH Baru: {$subject}";

            foreach ($users as $user) {
                self::logToDb($cch->cch_id, 'CCH_Email', $user['id'], $message);
                Mail::to($user['email'])->queue(new CchCreatedMail(
                    $cch->cch_number, $subject, $rank, $creatorName, $url
                ));
            }
        } catch (\Throwable $e) {
            Log::error('[CchNotification] notifyCchCreated failed: ' . $e->getMessage());
        }
    }

    // ───────────────────────────────────────────────────────────────────────

    /**
     * Fired when any block is submitted.
     * For block 10 it also sends the "ready to close" email.
     */
    public static function notifyBlockSubmitted(Cch $cch, int $blockNumber, string $submitterName): void
    {
        try {
            $cch->loadMissing('basic');
            $rank     = $cch->basic?->importance_internal ?? '-';
            $subject  = $cch->basic?->subject ?? $cch->cch_number;
            $blockName = self::$blockNames[$blockNumber] ?? "Block {$blockNumber}";
            $url      = self::cchUrl($cch->cch_id);

            $users = self::getManagerQcUsers();

            if ($rank === 'A') {
                $users = array_merge($users, self::getPresdirGmUsers());
            }

            // Tambahkan notifikasi untuk admin PIC department mulai dari blok 5 disubmit
            if ($blockNumber >= 5) {
                $users = array_merge($users, self::getRequestedAdminPics($cch->cch_id));
            }

            $users = self::uniqueUsers($users);

            $rankLabel = $rank === 'A' ? '[Rank A] ' : '';
            $message = "{$rankLabel}CCH {$subject} — {$blockName} telah disubmit";

            foreach ($users as $user) {
                self::logToDb($cch->cch_id, 'CCH_Email', $user['id'], $message);
                Mail::to($user['email'])->queue(new CchBlockSubmittedMail(
                    $cch->cch_number, $subject, $rank, $blockName, $submitterName, $url
                ));
            }

            // Block 10 submitted = semua blok selesai → kirim notif siap close
            if ($blockNumber === 10) {
                self::notifyReadyToClose($cch, $rank, $subject, $url, $users);
            }
        } catch (\Throwable $e) {
            Log::error('[CchNotification] notifyBlockSubmitted failed: ' . $e->getMessage());
        }
    }

    // ───────────────────────────────────────────────────────────────────────

    private static function notifyReadyToClose(Cch $cch, string $rank, string $subject, string $url, array $users): void
    {
        try {
            $rankLabel = $rank === 'A' ? '[Rank A] ' : '';
            $message = "{$rankLabel}CCH {$subject} siap untuk di-close";

            foreach ($users as $user) {
                self::logToDb($cch->cch_id, 'Close_Request', $user['id'], $message);
                Mail::to($user['email'])->queue(new CchReadyToCloseMail(
                    $cch->cch_number, $subject, $rank, $url
                ));
            }
        } catch (\Throwable $e) {
            Log::error('[CchNotification] notifyReadyToClose failed: ' . $e->getMessage());
        }
    }

    // ───────────────────────────────────────────────────────────────────────

    /**
     * Fired when CCH is closed.
     * Sends to the CCH creator only.
     */
    public static function notifyCchClosed(Cch $cch, string $closedByName): void
    {
        try {
            $cch->loadMissing(['basic', 'inputBy']);
            $subject = $cch->basic?->subject ?? $cch->cch_number;
            $url     = self::cchUrl($cch->cch_id);

            $creatorUser = $cch->inputBy;
            $message = "CCH {$subject} telah di-close";

            if ($creatorUser && $creatorUser->email) {
                self::logToDb($cch->cch_id, 'CCH_Email', $creatorUser->id, $message);
                Mail::to($creatorUser->email)->queue(new CchClosedMail(
                    $cch->cch_number, $subject, $closedByName, $url
                ));
            }
        } catch (\Throwable $e) {
            Log::error('[CchNotification] notifyCchClosed failed: ' . $e->getMessage());
        }
    }

    // ───────────────────────────────────────────────────────────────────────

    /**
     * Fired when an author comment is added.
     */
    public static function notifyCommentAdded(
        Cch $cch,
        string $commentSubject,
        string $commentBody,
        string $commenterName,
        int $blockNumber
    ): void {
        try {
            $cch->loadMissing(['basic', 'inputBy']);
            $subject   = $cch->basic?->subject ?? $cch->cch_number;
            $blockName = self::$blockNames[$blockNumber] ?? "Block {$blockNumber}";
            $url       = self::cchUrl($cch->cch_id);

            $users = [];

            // Creator
            if ($cch->inputBy?->email) {
                $users[] = ['id' => $cch->inputBy->id, 'email' => $cch->inputBy->email];
            }

            // Admin in charge
            if ($cch->admin_in_charge && $cch->admin_in_charge !== $cch->input_by) {
                $admin = CchUser::find($cch->admin_in_charge);
                if ($admin?->email) {
                    $users[] = ['id' => $admin->id, 'email' => $admin->email];
                }
            }

            // PIC dari Block 8 (occurrence)
            $occurrencePics = CchOccurrencePic::where('cch_id', $cch->cch_id)->pluck('pic_user_id');
            foreach ($occurrencePics as $picId) {
                $picUser = CchUser::find($picId);
                if ($picUser?->email) {
                    $users[] = ['id' => $picUser->id, 'email' => $picUser->email];
                }
            }

            // PIC dari Block 9 (outflow)
            $outflowPics = CchOutflowPic::where('cch_id', $cch->cch_id)->pluck('pic_user_id');
            foreach ($outflowPics as $picId) {
                $picUser = CchUser::find($picId);
                if ($picUser?->email) {
                    $users[] = ['id' => $picUser->id, 'email' => $picUser->email];
                }
            }

            // Manager QC
            $users = array_merge($users, self::getManagerQcUsers());

            // Admin PIC department dari Block 5
            $users = array_merge($users, self::getRequestedAdminPics($cch->cch_id));

            $users = self::uniqueUsers($users);
            $message = "[CCH {$cch->cch_number}] Komentar baru: {$commentSubject}";

            foreach ($users as $user) {
                self::logToDb($cch->cch_id, 'Question', $user['id'], $message);
                Mail::to($user['email'])->queue(new CchCommentAddedMail(
                    $cch->cch_number,
                    $subject,
                    $commentSubject,
                    $commentBody,
                    $commenterName,
                    $blockName,
                    $url
                ));
            }
        } catch (\Throwable $e) {
            Log::error('[CchNotification] notifyCommentAdded failed: ' . $e->getMessage());
        }
    }

    // ─── Internal helpers ───────────────────────────────────────────────────

    private static function logToDb(int $cchId, string $type, int $userId, string $message): void
    {
        try {
            CchNotification::create([
                'cch_id'            => $cchId,
                'notification_type' => $type, // Must be one of ENUM ('A_Alert','CCH_Email','Close_Request','Question','Horizontal_Deployment')
                'sent_to'           => $userId,
                'message'           => $message,
                'is_sent'           => true,
                'sent_at'           => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[CchNotification] DB logging failed: ' . $e->getMessage());
        }
    }

    private static function getManagerQcUsers(): array
    {
        return CchUser::where('sphere_role_level', 5)
            ->where('sphere_department_id', self::QC_DEPT_ID)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email'])
            ->toArray();
    }

    private static function getPresdirGmUsers(): array
    {
        return CchUser::where('sphere_role_level', 4)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email'])
            ->toArray();
    }

    private static function getRequestedAdminPics(int $cchId): array
    {
        $divisionIds = CchRequest::where('cch_id', $cchId)->pluck('division_id')->toArray();
        if (empty($divisionIds)) {
            return [];
        }

        return CchUser::where('sphere_role_level', 2)
            ->whereIn('sphere_department_id', $divisionIds)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email'])
            ->toArray();
    }

    private static function uniqueUsers(array $users): array
    {
        // Duplicate check based on ID
        $unique = [];
        $ids = [];
        foreach ($users as $user) {
            if (!in_array($user['id'], $ids)) {
                $ids[] = $user['id'];
                $unique[] = $user;
            }
        }
        return $unique;
    }

    private static function cchUrl(int $cchId): string
    {
        $base = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5176')), '/');
        return "{$base}/cch/{$cchId}";
    }
}

