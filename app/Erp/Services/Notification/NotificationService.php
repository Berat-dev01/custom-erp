<?php

namespace App\Erp\Services\Notification;

use App\Erp\Models\Check;
use App\Erp\Models\Invoice;
use App\Erp\Models\LeaveRequest;
use App\Erp\Models\Product;
use App\Erp\Notifications\LeaveRequestNotification;
use App\Erp\Notifications\LowStockNotification;
use App\Erp\Notifications\OverdueInvoiceNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendOverdueInvoiceAlerts(): int
    {
        $overdueInvoices = Invoice::where('status', 'overdue')
            ->with('invoiceable')
            ->limit(100)
            ->get();

        if ($overdueInvoices->isEmpty()) {
            return 0;
        }

        $financeUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['erp_admin', 'erp_finance']))->get();

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            foreach ($financeUsers as $user) {
                try {
                    $user->notify(new OverdueInvoiceNotification($invoice));
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('OverdueInvoiceNotification failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return $count;
    }

    public function sendLowStockAlerts(): int
    {
        $lowStockProducts = Product::where('track_stock', true)
            ->where('reorder_point', '>', 0)
            ->with('stockLevels')
            ->get()
            ->filter(fn ($p) => $p->stockLevels->sum('quantity') <= (float) $p->reorder_point);

        if ($lowStockProducts->isEmpty()) {
            return 0;
        }

        $inventoryUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['erp_admin', 'erp_inventory']))->get();

        $count = 0;
        foreach ($lowStockProducts as $product) {
            $currentStock = (float) $product->stockLevels->sum('quantity');
            foreach ($inventoryUsers as $user) {
                try {
                    $user->notify(new LowStockNotification($product, $currentStock));
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('LowStockNotification failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return $count;
    }

    public function sendCheckDueDateAlerts(): int
    {
        $count = Check::whereNotIn('status', ['cashed', 'cancelled'])
            ->whereBetween('due_date', [today(), today()->addDays(3)])
            ->count();

        if ($count === 0) {
            return 0;
        }

        $financeUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['erp_admin', 'erp_finance']))->get();

        foreach ($financeUsers as $user) {
            try {
                $user->notifications()->create([
                    'id'              => \Illuminate\Support\Str::uuid(),
                    'type'            => 'App\\Erp\\Notifications\\CheckDueNotification',
                    'notifiable_type' => get_class($user),
                    'notifiable_id'   => $user->id,
                    'data'            => json_encode(['type' => 'check_due_soon', 'count' => $count]),
                ]);
            } catch (\Throwable $e) {
                Log::error('CheckDueNotification failed', ['error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    public function notifyLeaveRequest(LeaveRequest $request, string $event): void
    {
        try {
            $notification = new LeaveRequestNotification($request, $event);

            if ($event === 'submitted') {
                $managers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['erp_admin', 'erp_hr']))->get();
                foreach ($managers as $user) {
                    $user->notify($notification);
                }
            } else {
                $empUser = User::where('email', $request->employee?->email)->first();
                if ($empUser) {
                    $empUser->notify($notification);
                }
            }
        } catch (\Throwable $e) {
            Log::error('notifyLeaveRequest failed', ['error' => $e->getMessage()]);
        }
    }
}
