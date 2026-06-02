<?php

namespace App\Erp\Services\EFatura\Drivers;

use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\EFaturaDriver;
use App\Erp\Services\EFatura\EFaturaResult;
use Illuminate\Support\Str;

class NullDriver implements EFaturaDriver
{
    public function sendInvoice(Invoice $invoice): EFaturaResult
    {
        return EFaturaResult::success(
            uuid: (string) Str::uuid(),
            ettn: strtoupper(Str::random(16)),
            status: 'accepted',
        );
    }

    public function cancelInvoice(string $uuid): bool
    {
        return true;
    }

    public function checkStatus(string $uuid): string
    {
        return 'accepted';
    }

    public function downloadPdf(string $uuid): string
    {
        return '';
    }

    public function isRegistered(string $vkn): bool
    {
        return true;
    }
}
