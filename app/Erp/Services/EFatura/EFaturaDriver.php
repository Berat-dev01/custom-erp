<?php

namespace App\Erp\Services\EFatura;

use App\Erp\Models\Invoice;

interface EFaturaDriver
{
    public function sendInvoice(Invoice $invoice): EFaturaResult;

    public function cancelInvoice(string $uuid): bool;

    public function checkStatus(string $uuid): string;

    public function downloadPdf(string $uuid): string;

    public function isRegistered(string $vkn): bool;
}
