<?php

namespace App\Erp\Jobs;

use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\EFaturaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckEFaturaStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function handle(EFaturaService $service): void
    {
        Invoice::where('efatura_status', 'pending')
            ->whereNotNull('efatura_uuid')
            ->chunkById(50, function ($invoices) use ($service): void {
                foreach ($invoices as $invoice) {
                    $service->checkAndUpdateStatus($invoice);
                }
            });
    }
}
