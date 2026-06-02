<?php

namespace App\Erp\Notifications;

use App\Erp\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Product $product, private float $currentStock) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'low_stock',
            'product_id'     => $this->product->id,
            'product_name'   => $this->product->name,
            'sku'            => $this->product->sku,
            'current_stock'  => $this->currentStock,
            'reorder_point'  => (float) $this->product->reorder_point,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Düşük Stok Uyarısı: ').$this->product->name)
            ->line(__('Ürün: ').$this->product->name.' ('.$this->product->sku.')')
            ->line(__('Mevcut Stok: ').$this->currentStock)
            ->line(__('Yeniden Sipariş Noktası: ').(float) $this->product->reorder_point);
    }
}
