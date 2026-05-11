<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\MealOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public function managementDashboard(User $actor): array
    {
        $today = Carbon::today()->toDateString();

        $mealOrders = MealOrder::query()->whereDate('schedule_date', $today);
        $deliveries = Delivery::query()->whereHas('mealOrder', fn ($query) => $query->whereDate('schedule_date', $today));
        $walletTransactions = WalletTransaction::query()->whereDate('created_at', $today);

        return [
            'total_customers' => User::query()->where('role', 'customer')->count(),
            'total_deliverymen' => User::query()->where('role', 'deliveryman')->count(),
            'today_lunch_orders' => (clone $mealOrders)->where('meal_time', 'lunch')->count(),
            'today_dinner_orders' => (clone $mealOrders)->where('meal_time', 'dinner')->count(),
            'today_delivered_lunch' => (clone $mealOrders)->where('meal_time', 'lunch')->where('status', 'delivered')->count(),
            'today_delivered_dinner' => (clone $mealOrders)->where('meal_time', 'dinner')->where('status', 'delivered')->count(),
            'today_failed_lunch' => (clone $mealOrders)->where('meal_time', 'lunch')->where('status', 'failed')->count(),
            'today_failed_dinner' => (clone $mealOrders)->where('meal_time', 'dinner')->where('status', 'failed')->count(),
            'today_total_sales' => round((float) (clone $mealOrders)->sum('total_amount'), 2),
            'today_total_refunds' => round((float) (clone $walletTransactions)->where('type', 'refund')->sum('amount'), 2),
            'wallet_total_balance' => round((float) Wallet::query()->sum('balance'), 2),
        ];
    }

    public function deliverymanDashboard(User $deliveryman): array
    {
        $today = Carbon::today()->toDateString();
        $query = $deliveryman->deliveries()->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $today));

        return [
            'today_assigned_lunch' => (clone $query)->where('status', 'assigned')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'lunch'))->count(),
            'today_assigned_dinner' => (clone $query)->where('status', 'assigned')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'dinner'))->count(),
            'today_delivered_lunch' => (clone $query)->where('status', 'delivered')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'lunch'))->count(),
            'today_delivered_dinner' => (clone $query)->where('status', 'delivered')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'dinner'))->count(),
            'today_failed_lunch' => (clone $query)->where('status', 'failed')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'lunch'))->count(),
            'today_failed_dinner' => (clone $query)->where('status', 'failed')->whereHas('mealOrder', fn ($q) => $q->where('meal_time', 'dinner'))->count(),
        ];
    }

    public function customerDashboard(User $customer): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();

        $todayOrders = $customer->mealOrders()->whereDate('schedule_date', $today->toDateString())->get()->keyBy('meal_time');
        $monthOrders = $customer->mealOrders()->whereBetween('schedule_date', [$monthStart, $monthEnd]);
        $refunds = $customer->walletTransactions()->where('type', 'refund')->whereBetween('created_at', [$monthStart, $monthEnd]);
        $wallet = $customer->wallet;

        return [
            'wallet_balance' => round((float) ($wallet?->balance ?? 0), 2),
            'today_lunch_status' => $todayOrders->get('lunch')?->status,
            'today_dinner_status' => $todayOrders->get('dinner')?->status,
            'current_month_lunch_count' => (clone $monthOrders)->where('meal_time', 'lunch')->count(),
            'current_month_dinner_count' => (clone $monthOrders)->where('meal_time', 'dinner')->count(),
            'current_month_total_bill' => round((float) (clone $monthOrders)->sum('total_amount'), 2),
            'current_month_refund_total' => round((float) $refunds->sum('amount'), 2),
        ];
    }

    public function mealOrderReport(array $filters): array
    {
        $query = MealOrder::query()
            ->with(['user', 'address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman']);

        $this->applyMealOrderFilters($query, $filters);
        $orders = $query->latest('schedule_date')->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => round((float) $orders->sum('total_amount'), 2),
                'refunded_count' => $orders->where('is_refunded', true)->count(),
                'refunded_amount' => round((float) $orders->where('is_refunded', true)->sum('total_amount'), 2),
                'lunch_orders' => $orders->where('meal_time', 'lunch')->count(),
                'dinner_orders' => $orders->where('meal_time', 'dinner')->count(),
            ],
            'meal_orders' => $orders,
        ];
    }

    public function deliveryReport(array $filters): array
    {
        $query = Delivery::query()
            ->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage', 'deliveryman']);

        $this->applyDeliveryFilters($query, $filters);
        $deliveries = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_deliveries' => $deliveries->count(),
                'assigned' => $deliveries->where('status', 'assigned')->count(),
                'picked' => $deliveries->where('status', 'picked')->count(),
                'delivered' => $deliveries->where('status', 'delivered')->count(),
                'failed' => $deliveries->where('status', 'failed')->count(),
                'lunch_deliveries' => $deliveries->filter(fn ($d) => $d->mealOrder?->meal_time === 'lunch')->count(),
                'dinner_deliveries' => $deliveries->filter(fn ($d) => $d->mealOrder?->meal_time === 'dinner')->count(),
            ],
            'deliveries' => $deliveries,
        ];
    }

    public function walletReport(array $filters): array
    {
        $query = WalletTransaction::query()->with('user');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $transactions = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_credit' => round((float) $transactions->where('direction', 'credit')->sum('amount'), 2),
                'total_debit' => round((float) $transactions->where('direction', 'debit')->sum('amount'), 2),
                'total_refund' => round((float) $transactions->where('type', 'refund')->sum('amount'), 2),
                'total_meal_charge' => round((float) $transactions->where('type', 'meal_charge')->sum('amount'), 2),
            ],
            'wallet_transactions' => $transactions,
        ];
    }

    public function refundReport(array $filters): array
    {
        $query = WalletTransaction::query()
            ->with(['user'])
            ->where('type', 'refund')
            ->where('reference_type', 'meal_order');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['area_id']) || ! empty($filters['subarea_id'])) {
            $query->whereHas('user.mealOrders.address', function ($addressQuery) use ($filters) {
                if (! empty($filters['area_id'])) {
                    $addressQuery->where('area_id', $filters['area_id']);
                }
                if (! empty($filters['subarea_id'])) {
                    $addressQuery->where('subarea_id', $filters['subarea_id']);
                }
            });
        }

        $refunds = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_refunded_orders' => $refunds->count(),
                'total_refunded_amount' => round((float) $refunds->sum('amount'), 2),
            ],
            'refunds' => $refunds,
        ];
    }

    public function deliverymanDeliveriesReport(User $deliveryman, array $filters): array
    {
        $query = $deliveryman->deliveries()->with(['mealOrder.user', 'mealOrder.address.area', 'mealOrder.address.subarea', 'mealOrder.items.mealPackage']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['schedule_date'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $filters['schedule_date']));
        }
        if (! empty($filters['meal_time'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('meal_time', $filters['meal_time']));
        }
        if (! empty($filters['date_from'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '>=', $filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '<=', $filters['date_to']));
        }

        $deliveries = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_deliveries' => $deliveries->count(),
                'lunch_deliveries' => $deliveries->filter(fn ($d) => $d->mealOrder?->meal_time === 'lunch')->count(),
                'dinner_deliveries' => $deliveries->filter(fn ($d) => $d->mealOrder?->meal_time === 'dinner')->count(),
                'delivered' => $deliveries->where('status', 'delivered')->count(),
                'failed' => $deliveries->where('status', 'failed')->count(),
            ],
            'deliveries' => $deliveries,
        ];
    }

    public function customerMealOrdersReport(User $customer, array $filters): array
    {
        $query = $customer->mealOrders()->with(['address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']);

        $this->applyCustomerFilters($query, $filters);
        $orders = $query->latest('schedule_date')->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_orders' => $orders->count(),
                'lunch_orders' => $orders->where('meal_time', 'lunch')->count(),
                'dinner_orders' => $orders->where('meal_time', 'dinner')->count(),
                'total_bill' => round((float) $orders->sum('total_amount'), 2),
            ],
            'meal_orders' => $orders,
        ];
    }

    public function customerWalletReport(User $customer, array $filters): array
    {
        $query = $customer->walletTransactions();

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $transactions = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_credit' => round((float) $transactions->where('direction', 'credit')->sum('amount'), 2),
                'total_debit' => round((float) $transactions->where('direction', 'debit')->sum('amount'), 2),
                'total_refund' => round((float) $transactions->where('type', 'refund')->sum('amount'), 2),
            ],
            'wallet_transactions' => $transactions,
        ];
    }

    public function customerRefundReport(User $customer, array $filters): array
    {
        $query = $customer->walletTransactions()->where('type', 'refund');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $refunds = $query->latest()->get();

        return [
            'filters' => $filters,
            'summary' => [
                'total_refunded_orders' => $refunds->count(),
                'total_refunded_amount' => round((float) $refunds->sum('amount'), 2),
            ],
            'refunds' => $refunds,
        ];
    }

    private function applyMealOrderFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['schedule_date'])) {
            $query->whereDate('schedule_date', $filters['schedule_date']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('schedule_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('schedule_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['meal_time'])) {
            $query->where('meal_time', $filters['meal_time']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['area_id'])) {
            $query->whereHas('address', fn ($addressQuery) => $addressQuery->where('area_id', $filters['area_id']));
        }
        if (! empty($filters['subarea_id'])) {
            $query->whereHas('address', fn ($addressQuery) => $addressQuery->where('subarea_id', $filters['subarea_id']));
        }
    }

    private function applyDeliveryFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['deliveryman_id'])) {
            $query->where('deliveryman_id', $filters['deliveryman_id']);
        }
        if (! empty($filters['schedule_date'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', $filters['schedule_date']));
        }
        if (! empty($filters['date_from'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '>=', $filters['date_from']));
        }
        if (! empty($filters['date_to'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->whereDate('schedule_date', '<=', $filters['date_to']));
        }
        if (! empty($filters['meal_time'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('meal_time', $filters['meal_time']));
        }
        if (! empty($filters['user_id'])) {
            $query->whereHas('mealOrder', fn ($orderQuery) => $orderQuery->where('user_id', $filters['user_id']));
        }
        if (! empty($filters['phone'])) {
            $query->whereHas('mealOrder.user', fn ($userQuery) => $userQuery->where('phone', 'like', '%'.$filters['phone'].'%'));
        }
        if (! empty($filters['area_id'])) {
            $query->whereHas('mealOrder.address', fn ($addressQuery) => $addressQuery->where('area_id', $filters['area_id']));
        }
        if (! empty($filters['subarea_id'])) {
            $query->whereHas('mealOrder.address', fn ($addressQuery) => $addressQuery->where('subarea_id', $filters['subarea_id']));
        }
    }

    private function applyCustomerFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['month'])) {
            $start = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()->toDateString();
            $end = Carbon::createFromFormat('Y-m', $filters['month'])->endOfMonth()->toDateString();
            $query->whereBetween('schedule_date', [$start, $end]);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('schedule_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('schedule_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['meal_time'])) {
            $query->where('meal_time', $filters['meal_time']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}
