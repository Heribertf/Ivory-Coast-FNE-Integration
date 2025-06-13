<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceSyncService;

class SyncInvoicesToFne extends Command
{
    protected $signature = 'fne:sync-invoices 
                            {--full : Perform a full sync of all invoices}
                            {--recent : Sync only recently modified invoices}
                            {--hours=24 : Hours to look back for recent sync}';

    protected $description = 'Sync invoices from main database to FNE database';

    public function handle(InvoiceSyncService $syncService)
    {
        if ($this->option('full')) {
            $this->info('Starting full invoice sync...');
            $syncService->syncAllInvoices();
            $this->info('Full invoice sync completed.');
            return;
        }

        $hours = $this->option('hours');
        $this->info("Syncing invoices modified in last {$hours} hours...");
        $syncService->syncRecentInvoices($hours);
        $this->info('Recent invoice sync completed.');
    }
}
