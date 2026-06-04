<?php

namespace App\Erp\Services\Bank;

use App\Erp\Models\BankAccount;
use App\Erp\Models\BankTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BankService
{
    public function currentBalance(BankAccount $account): float
    {
        $deposits    = (float) $account->transactions()->where('type', 'deposit')->sum('amount');
        $withdrawals = (float) $account->transactions()->where('type', 'withdrawal')->sum('amount');
        $transfers   = (float) $account->transactions()->where('type', 'transfer')->sum('amount');

        return (float) $account->opening_balance + $deposits - $withdrawals + $transfers;
    }

    public function transfer(BankAccount $from, BankAccount $to, float $amount, Carbon $date, ?string $description = null, int $userId = 1): void
    {
        abort_if($amount <= 0, 422, __('Transfer tutarı sıfırdan büyük olmalıdır.'));
        abort_if($from->id === $to->id, 422, __('Aynı hesaba transfer yapılamaz.'));

        DB::transaction(function () use ($from, $to, $amount, $date, $description, $userId): void {
            BankTransaction::create([
                'bank_account_id'  => $from->id,
                'type'             => 'withdrawal',
                'amount'           => $amount,
                'transaction_date' => $date,
                'description'      => $description ?? __('Transfer çıkışı → ').$to->name,
                'reference'        => 'TRF-'.now()->format('YmdHis'),
                'created_by'       => $userId,
            ]);

            BankTransaction::create([
                'bank_account_id'  => $to->id,
                'type'             => 'deposit',
                'amount'           => $amount,
                'transaction_date' => $date,
                'description'      => $description ?? __('Transfer girişi ← ').$from->name,
                'reference'        => 'TRF-'.now()->format('YmdHis'),
                'created_by'       => $userId,
            ]);
        });
    }

    /**
     * CSV formatı: tarih(Y-m-d), açıklama, tutar(+ giriş / - çıkış), referans(opsiyonel)
     *
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importStatement(BankAccount $account, UploadedFile $file, int $userId): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $lines = array_filter(explode("\n", file_get_contents($file->getRealPath())));

        foreach ($lines as $i => $line) {
            $cols = str_getcsv(trim($line));

            if (count($cols) < 3) {
                $errors[] = "Satır ".($i + 1).": Eksik sütun";
                continue;
            }

            [$rawDate, $description, $rawAmount] = $cols;
            $reference = $cols[3] ?? null;

            $date = Carbon::createFromFormat('Y-m-d', trim($rawDate));
            if (! $date) {
                $errors[] = "Satır ".($i + 1).": Geçersiz tarih formatı";
                $skipped++;
                continue;
            }

            $amount = (float) str_replace(['.', ','], ['', '.'], trim($rawAmount));

            if ($amount === 0.0) {
                $skipped++;
                continue;
            }

            BankTransaction::create([
                'bank_account_id'  => $account->id,
                'type'             => $amount > 0 ? 'deposit' : 'withdrawal',
                'amount'           => abs($amount),
                'transaction_date' => $date,
                'description'      => trim($description),
                'reference'        => $reference ? trim($reference) : null,
                'created_by'       => $userId,
            ]);

            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    public function reconcile(BankAccount $account, array $transactionIds): int
    {
        return BankTransaction::where('bank_account_id', $account->id)
            ->whereIn('id', $transactionIds)
            ->update(['is_reconciled' => true]);
    }
}
