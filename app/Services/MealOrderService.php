<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\MealOrder;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MealOrderService
{
    public function __construct(
        private WalletService $walletService,
        private DeliveryAssignmentService $deliveryAssignmentService,
    ) {
    }

    public function generateForDate(string $scheduleDate, User $actor): array
    {
        $date = Carbon::parse($scheduleDate);
        $customers = User::query()
            ->where('role', 'customer')
            ->where('status', 'active')
            ->with([
                'userWeeklySchedules.address.subarea',
                'userWeeklySchedules.items.mealPackage',
                'userCalendarOverrides' => fn ($query) => $query->whereDate('schedule_date', $date->toDateString()),
                'userCalendarOverrides.address.subarea',
                'userCalendarOverrides.items.mealPackage',
                'wallet',
            ])
            ->get();

        $generated = [];
        $skipped = [];

        foreach ($customers as $customer) {
            foreach (['lunch', 'dinner'] as $mealTime) {
                $slot = $this->resolveSlot($customer, $date, $mealTime);

                if ($slot === null || $slot['is_off']) {
                    $skipped[] = [
                        'user_id' => $customer->id,
                        'meal_time' => $mealTime,
                        'reason' => 'Meal is off or not scheduled.',
                    ];
                    continue;
                }

                if (empty($slot['items'])) {
                    $skipped[] = [
                        'user_id' => $customer->id,
                        'meal_time' => $mealTime,
                        'reason' => 'No meal items selected.',
                    ];
                    continue;
                }

                if (! $slot['address']) {
                    $skipped[] = [
                        'user_id' => $customer->id,
                        'meal_time' => $mealTime,
                        'reason' => 'Address is missing for this meal slot.',
                    ];
                    continue;
                }

                if (MealOrder::query()->where('user_id', $customer->id)->whereDate('schedule_date', $date->toDateString())->where('meal_time', $mealTime)->exists()) {
                    $skipped[] = [
                        'user_id' => $customer->id,
                        'meal_time' => $mealTime,
                        'reason' => 'Order already exists for this slot.',
                    ];
                    continue;
                }

                $subtotal = round(collect($slot['items'])->sum(fn ($item) => (float) $item->mealPackage->price * $item->quantity), 2);
                $deliveryCharge = $this->resolveDeliveryCharge();
                $totalAmount = round($subtotal + $deliveryCharge, 2);

                $wallet = $this->walletService->getOrCreateWallet($customer);
                if ((float) $wallet->balance < $totalAmount) {
                    $skipped[] = [
                        'user_id' => $customer->id,
                        'meal_time' => $mealTime,
                        'reason' => 'Insufficient wallet balance.',
                    ];
                    continue;
                }

                $generated[] = DB::transaction(function () use ($customer, $slot, $date, $mealTime, $subtotal, $deliveryCharge, $totalAmount, $actor, $wallet) {
                    $order = MealOrder::query()->create([
                        'user_id' => $customer->id,
                        'address_id' => $slot['address']->id,
                        'schedule_date' => $date->toDateString(),
                        'meal_time' => $mealTime,
                        'status' => 'confirmed',
                        'subtotal' => $subtotal,
                        'delivery_charge' => $deliveryCharge,
                        'total_amount' => $totalAmount,
                        'note' => 'Order generated manually by admin.',
                    ]);

                    $order->items()->createMany(
                        collect($slot['items'])->map(fn ($item) => [
                            'meal_package_id' => $item->meal_package_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->mealPackage->price,
                            'subtotal' => round((float) $item->mealPackage->price * $item->quantity, 2),
                        ])->all()
                    );

                    $transaction = $this->walletService->debit($wallet, [
                        'type' => 'meal_charge',
                        'amount' => $totalAmount,
                        'reference_type' => 'meal_order',
                        'reference_id' => $order->id,
                        'payment_method' => 'system',
                        'status' => 'completed',
                        'note' => ucfirst($mealTime).' meal charge for '.$date->toDateString(),
                        'created_by' => $actor->id,
                    ]);

                    $deliverymanId = $this->deliveryAssignmentService->resolveDeliverymanId($slot['address']);

                    Delivery::query()->create([
                        'meal_order_id' => $order->id,
                        'deliveryman_id' => $deliverymanId,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                        'note' => $deliverymanId ? 'Deliveryman auto assigned from subarea mapping.' : 'No deliveryman assigned automatically.',
                    ]);

                    $order->update([
                        'wallet_transaction_id' => $transaction->id,
                        'is_wallet_deducted' => true,
                        'deducted_at' => now(),
                    ]);

                    return $order->fresh()->load(['address.area', 'address.subarea', 'items.mealPackage', 'delivery.deliveryman', 'walletTransaction']);
                });
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
        ];
    }

    private function resolveSlot(User $user, Carbon $date, string $mealTime): ?array
    {
        $override = $user->userCalendarOverrides
            ->first(fn ($item) => $item->schedule_date->toDateString() === $date->toDateString() && $item->meal_time === $mealTime);

        if ($override) {
            return [
                'is_off' => (bool) $override->is_off,
                'address' => $override->address,
                'items' => $override->items,
            ];
        }

        $dayName = strtolower($date->englishDayOfWeek);
        $weekly = $user->userWeeklySchedules
            ->first(fn ($item) => $item->day_of_week === $dayName && $item->meal_time === $mealTime);

        if (! $weekly) {
            return null;
        }

        return [
            'is_off' => (bool) $weekly->is_off,
            'address' => $weekly->address,
            'items' => $weekly->items,
        ];
    }

    private function resolveDeliveryCharge(): float
    {
        $enabled = filter_var(Setting::query()->where('key', 'delivery_charge_enabled')->value('value') ?? '1', FILTER_VALIDATE_BOOL);
        if (! $enabled) {
            return 0;
        }

        return round((float) (Setting::query()->where('key', 'delivery_charge_amount')->value('value') ?? 0), 2);
    }
}
