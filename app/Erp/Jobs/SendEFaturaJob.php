<?php

namespace App\Erp\Jobs;

use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\EFaturaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEFaturaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(private Invoice $invoice) {}

    public function handle(EFaturaService $service): void
    {
        $this->invoice->refresh();

        if ($this->invoice->efatura_status !== 'pending') {
            return;
        }

        $service->send($this->invoice);
    }
}
