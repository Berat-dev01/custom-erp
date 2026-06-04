<?php

namespace App\Erp\Notifications;

use App\Erp\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private LeaveRequest $leaveRequest,
        private string       $event, // submitted | approved | rejected
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'             => 'leave_request_'.$this->event,
            'leave_request_id' => $this->leaveRequest->id,
            'employee_name'    => $this->leaveRequest->employee?->full_name,
            'start_date'       => $this->leaveRequest->start_date?->toDateString(),
            'end_date'         => $this->leaveRequest->end_date?->toDateString(),
            'days'             => (float) $this->leaveRequest->days,
            'status'           => $this->leaveRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->event) {
            'submitted' => __('Yeni İzin Talebi: ').$this->leaveRequest->employee?->full_name,
            'approved'  => __('İzin Talebiniz Onaylandı'),
            'rejected'  => __('İzin Talebiniz Reddedildi'),
            default     => __('İzin Talebi Güncellendi'),
        };

        return (new MailMessage())->subject($subject)
            ->line(__('Çalışan: ').$this->leaveRequest->employee?->full_name)
            ->line(__('Tarih: ').$this->leaveRequest->start_date?->format('d.m.Y').' - '.$this->leaveRequest->end_date?->format('d.m.Y'))
            ->line(__('Süre: ').$this->leaveRequest->days.' '.__('gün'));
    }
}
