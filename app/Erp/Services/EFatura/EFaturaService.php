<?php

namespace App\Erp\Services\EFatura;

use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\Drivers\NullDriver;
use App\Erp\Services\EFatura\Drivers\UyumsoftDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EFaturaService
{
    private EFaturaDriver $driver;

    public function __construct()
    {
        $this->driver = $this->resolveDriver();
    }

    public function isEnabled(): bool
    {
        return (bool) config('erp.efatura.enabled', false);
    }

    /**
     * Fatura onaylandığında çağrılır: tip belirler ve gönderim yapar.
     */
    public function processInvoice(Invoice $invoice): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($invoice->efatura_status !== 'none') {
            return;
        }

        $vkn = $invoice->invoiceable?->tax_number ?? '';

        try {
            $efaturaType = ($vkn && $this->driver->isRegistered($vkn)) ? 'efatura' : 'earshiv';

            $invoice->update([
                'efatura_type'   => $efaturaType,
                'efatura_status' => 'pending',
            ]);

            dispatch(new \App\Erp\Jobs\SendEFaturaJob($invoice));
        } catch (\Throwable $e) {
            Log::error('EFaturaService::processInvoice failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function send(Invoice $invoice): bool
    {
        try {
            $result = $this->driver->sendInvoice($invoice);

            if ($result->success) {
                $invoice->update([
                    'efatura_uuid'    => $result->uuid,
                    'efatura_ettn'    => $result->ettn,
                    'efatura_status'  => $result->status,
                    'efatura_sent_at' => now(),
                ]);
            } else {
                Log::error('EFaturaService::send failed', ['invoice_id' => $invoice->id, 'message' => $result->message]);
                $invoice->update(['efatura_status' => 'rejected']);
            }

            return $result->success;
        } catch (\Throwable $e) {
            Log::error('EFaturaService::send exception', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            $invoice->update(['efatura_status' => 'rejected']);

            return false;
        }
    }

    public function cancel(Invoice $invoice): bool
    {
        if (! $invoice->efatura_uuid) {
            return false;
        }

        try {
            $success = $this->driver->cancelInvoice($invoice->efatura_uuid);

            if ($success) {
                $invoice->update(['efatura_status' => 'cancelled']);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('EFaturaService::cancel exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function checkAndUpdateStatus(Invoice $invoice): string
    {
        if (! $invoice->efatura_uuid) {
            return 'none';
        }

        try {
            $status = $this->driver->checkStatus($invoice->efatura_uuid);
            $invoice->update(['efatura_status' => $status]);

            if ($status === 'accepted' && ! $invoice->efatura_pdf_path) {
                $this->downloadAndStorePdf($invoice);
            }

            return $status;
        } catch (\Throwable $e) {
            Log::error('EFaturaService::checkAndUpdateStatus exception', ['error' => $e->getMessage()]);

            return $invoice->efatura_status;
        }
    }

    private function downloadAndStorePdf(Invoice $invoice): void
    {
        try {
            $pdf = $this->driver->downloadPdf($invoice->efatura_uuid);

            if ($pdf) {
                $path = "efatura/{$invoice->efatura_uuid}.pdf";
                Storage::disk('local')->put($path, $pdf);
                $invoice->update(['efatura_pdf_path' => $path]);
            }
        } catch (\Throwable $e) {
            Log::error('EFaturaService::downloadAndStorePdf failed', ['error' => $e->getMessage()]);
        }
    }

    private function resolveDriver(): EFaturaDriver
    {
        if (! $this->isEnabled()) {
            return new NullDriver();
        }

        return match (config('erp.efatura.driver', 'null')) {
            'uyumsoft' => new UyumsoftDriver(),
            default    => new NullDriver(),
        };
    }
}
