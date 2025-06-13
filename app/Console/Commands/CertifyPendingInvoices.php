<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvNum;
use App\Models\FneInvoice;
use App\Services\FneApiService;

class CertifyPendingInvoices extends Command
{
    protected $signature = 'fne:certify-pending {--limit=10 : Number of invoices to process}';
    protected $description = 'Certify pending invoices with FNE API';

    /**
     * Execute the console command.
     */
    public function handle(FneApiService $fneService)
    {
        $limit = $this->option('limit');

        $this->info("Starting certification of up to {$limit} pending invoices...");

        $pendingInvoices = FneInvoice::whereDoesntHave('fneCertification')
            ->orWhereHas('fneCertification', function ($query) {
                $query->where('certification_status', 'failed');
            })
            ->orderBy('InvDate', 'asc') // Process oldest first
            ->limit($limit)
            ->get();

        if ($pendingInvoices->isEmpty()) {
            $this->info('No pending invoices found.');
            return;
        }

        $successful = 0;
        $failed = 0;

        foreach ($pendingInvoices as $invoice) {
            $this->line("Processing invoice: {$invoice->InvNumber}");

            try {
                $result = $fneService->certifyInvoice($invoice);

                if ($result['success']) {
                    $this->info("✓ Certified: {$invoice->InvNumber}");
                    $successful++;

                    // Rate limiting - sleep for 1 second between successful requests
                    sleep(1);
                } else {
                    $this->error("✗ Failed: {$invoice->InvNumber} - {$result['error']}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error: {$invoice->InvNumber} - {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\nSummary: {$successful} successful, {$failed} failed");

        if ($failed > 0) {
            return 1; // Exit with error code if any failures
        }

        return 0;
    }
}
