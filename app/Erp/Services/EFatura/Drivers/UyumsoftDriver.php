<?php

namespace App\Erp\Services\EFatura\Drivers;

use App\Erp\Models\Invoice;
use App\Erp\Services\EFatura\EFaturaDriver;
use App\Erp\Services\EFatura\EFaturaResult;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UyumsoftDriver implements EFaturaDriver
{
    private string $apiUrl;
    private string $username;
    private string $password;
    private bool   $testMode;

    public function __construct()
    {
        $this->apiUrl   = (string) config('erp.efatura.api_url', '');
        $this->username = (string) config('erp.efatura.username', '');
        $this->password = (string) config('erp.efatura.password', '');
        $this->testMode = (bool) config('erp.efatura.test_mode', true);
    }

    public function sendInvoice(Invoice $invoice): EFaturaResult
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->post("{$this->apiUrl}/invoices", $this->buildPayload($invoice));

            if (! $response->successful()) {
                Log::error('UyumsoftDriver::sendInvoice failed', ['status' => $response->status(), 'body' => $response->body()]);

                return EFaturaResult::failure($response->json('message', 'API error'));
            }

            return EFaturaResult::success(
                uuid: $response->json('uuid', ''),
                ettn: $response->json('ettn', ''),
                status: 'pending',
            );
        } catch (\Throwable $e) {
            Log::error('UyumsoftDriver::sendInvoice exception', ['error' => $e->getMessage()]);

            return EFaturaResult::failure($e->getMessage());
        }
    }

    public function cancelInvoice(string $uuid): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->delete("{$this->apiUrl}/invoices/{$uuid}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('UyumsoftDriver::cancelInvoice exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function checkStatus(string $uuid): string
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->get("{$this->apiUrl}/invoices/{$uuid}/status");

            return $response->json('status', 'pending');
        } catch (\Throwable $e) {
            Log::error('UyumsoftDriver::checkStatus exception', ['error' => $e->getMessage()]);

            return 'pending';
        }
    }

    public function downloadPdf(string $uuid): string
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->get("{$this->apiUrl}/invoices/{$uuid}/pdf");

            return $response->body();
        } catch (\Throwable $e) {
            Log::error('UyumsoftDriver::downloadPdf exception', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function isRegistered(string $vkn): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(10)
                ->get("{$this->apiUrl}/taxpayers/{$vkn}");

            return $response->successful() && (bool) $response->json('is_registered', false);
        } catch (\Throwable $e) {
            Log::warning('UyumsoftDriver::isRegistered exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function buildPayload(Invoice $invoice): array
    {
        $invoice->loadMissing(['items.product', 'invoiceable']);

        return [
            'test_mode'       => $this->testMode,
            'invoice_number'  => $invoice->invoice_number,
            'issue_date'      => $invoice->issue_date?->format('Y-m-d'),
            'due_date'        => $invoice->due_date?->format('Y-m-d'),
            'currency'        => $invoice->currency,
            'subtotal'        => (float) $invoice->subtotal,
            'tax_amount'      => (float) $invoice->tax_amount,
            'total'           => (float) $invoice->total,
            'buyer'           => [
                'name' => $invoice->invoiceable?->name ?? '',
                'vkn'  => $invoice->invoiceable?->tax_number ?? '',
            ],
            'lines' => $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity'    => (float) $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'tax_rate'    => (float) $item->tax_rate,
                'line_total'  => (float) $item->line_total,
            ])->toArray(),
        ];
    }
}
