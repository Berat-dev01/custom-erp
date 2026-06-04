<?php

namespace App\Erp\Notifications;

use App\Erp\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'overdue_invoice',
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount'         => $this->invoice->remainingAmount(),
            'due_date'       => $this->invoice->due_date?->toDateString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Vadesi Geçmiş Fatura: ').$this->invoice->invoice_number)
            ->line(__('Fatura No: ').$this->invoice->invoice_number)
            ->line(__('Kalan Tutar: ').number_format($this->invoice->remainingAmount(), 2, ',', '.').' ₺')
            ->line(__('Vade Tarihi: ').$this->invoice->due_date?->format('d.m.Y'));
    }
}
