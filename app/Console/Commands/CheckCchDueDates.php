<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CchRequest;
use App\Services\CchNotificationService;
use Carbon\Carbon;

class CheckCchDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cch:check-due-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check CCH requests due dates and send reminders to Admin PICs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting CCH due date check...');

        // Ambil semua request yang CCH-nya belum closed
        $requests = CchRequest::whereHas('cch', function ($query) {
            $query->whereNotIn('status', ['closed', 'closed_by_manager']);
        })->whereNotNull('due_date')->get();

        $today = Carbon::today();
        $count = 0;

        foreach ($requests as $request) {
            $cch = $request->cch;
            
            // Jika block 9 sudah disubmit, berarti occurrence & outflow sudah selesai, tidak perlu di-remind lagi
            if ($cch->b9_status === 'submitted') {
                continue;
            }

            $dueDate = Carbon::parse($request->due_date)->startOfDay();
            // diffInDays dengan parameter false akan menghasilkan nilai negatif jika $dueDate di masa lalu
            $diffDays = (int) $today->diffInDays($dueDate, false);

            $statusLabel = null;

            if ($diffDays === 3) {
                $statusLabel = 'H-3';
            } elseif ($diffDays === 0) {
                $statusLabel = 'Hari H';
            } elseif ($diffDays < 0) {
                $statusLabel = 'Overdue';
            }

            if ($statusLabel) {
                CchNotificationService::notifyDueDateReminder($cch, $request, $statusLabel, $diffDays);
                $count++;
            }
        }

        $this->info("Completed. Sent {$count} reminders.");
        return 0;
    }
}
