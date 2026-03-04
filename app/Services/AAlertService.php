<?php

namespace App\Services;

use App\Models\CchUser;
// use App\Models\CchNotification; // TODO: re-enable when CchNotification model is properly set up
// use Illuminate\Support\Facades\Mail;

class AAlertService
{
    /**
     * Handle A-Alert Trigger
     * 
     * @param int $cchId
     * @param string $cchNumber
     * @param string $subject
     */
    public static function trigger($cchId, $cchNumber, $subject)
    {
        $message = "A-Alert: CCH {$cchNumber} — {$subject}";

        // TODO: Insert ke t_cch_notifications (disabled sementara)
        // CchNotification::create([
        //     'cch_id' => $cchId,
        //     'notification_type' => 'A_Alert',
        //     'sent_to' => null,
        //     'message' => $message,
        //     'is_sent' => true,
        //     'sent_at' => now()
        // ]);

        // Ambil semua QA / Division Manager untuk notifikasi
        $qaManagers = CchUser::whereIn('cch_role', ['qa_manager', 'division_manager', 'admin'])
                             ->where('is_active', true)
                             ->get();

        // TODO: Kirim Email via queue (disabled sementara)
        foreach ($qaManagers as $user) {
            // Mail::to($user->email)->queue(new AAlertMail($cchNumber, $subject));
            \Log::info("A-Alert triggered for CCH {$cchNumber} — notify: {$user->email}");
        }
    }
}
