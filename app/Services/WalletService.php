<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'status' => 'active',
            ]
        );
    }

    public function credit(Wallet $wallet, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $this->ensureWalletActive($wallet);

            $amount = (float) $data['amount'];
            $before = (float) $wallet->balance;
            $after = $before + $amount;

            $wallet->update(['balance' => $after]);

            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => $data['type'],
                'direction' => 'credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'gateway_name' => $data['gateway_name'] ?? null,
                'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'approved_by' => $data['approved_by'] ?? null,
            ]);

            $this->sendCreditNotification($wallet, $transaction);

            return $transaction;
        });
    }

    public function debit(Wallet $wallet, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $data) {
            $this->ensureWalletActive($wallet);

            $amount = (float) $data['amount'];
            $before = (float) $wallet->balance;
            abort_if($before < $amount, 422, 'Insufficient wallet balance.');

            $after = $before - $amount;
            $wallet->update(['balance' => $after]);

            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => $data['type'],
                'direction' => 'debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'gateway_name' => $data['gateway_name'] ?? null,
                'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
                'status' => $data['status'] ?? 'completed',
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'approved_by' => $data['approved_by'] ?? null,
            ]);

            $this->sendDebitNotification($wallet, $transaction);

            return $transaction;
        });
    }

    private function ensureWalletActive(Wallet $wallet): void
    {
        abort_if($wallet->status !== 'active', 422, 'Wallet is not active.');
    }

    private function sendCreditNotification(Wallet $wallet, WalletTransaction $transaction): void
    {
        $user = $wallet->user()->first();

        if (! $user) {
            return;
        }

        if ($transaction->type === 'refund') {
            $title = 'Refund Received';
            $message = 'আপনার wallet-এ '.number_format((float) $transaction->amount, 2).' টাকা refund করা হয়েছে।';
        } else {
            $title = 'Wallet Credited';
            $message = 'আপনার wallet-এ '.number_format((float) $transaction->amount, 2).' টাকা যোগ হয়েছে।';
        }

        $this->notificationService->sendToUser($user, $transaction->type === 'refund' ? 'refund' : 'wallet_credit', $title, $message, [
            'wallet_transaction_id' => $transaction->id,
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
            'amount' => (float) $transaction->amount,
        ]);
    }

    private function sendDebitNotification(Wallet $wallet, WalletTransaction $transaction): void
    {
        $user = $wallet->user()->first();

        if (! $user) {
            return;
        }

        $title = 'Wallet Debited';
        $message = 'আপনার wallet থেকে '.number_format((float) $transaction->amount, 2).' টাকা কাটা হয়েছে।';

        if ($transaction->type === 'meal_charge') {
            $title = 'Meal Charge Deducted';
            $message = $transaction->note
                ? $transaction->note.' এর জন্য '.number_format((float) $transaction->amount, 2).' টাকা কাটা হয়েছে।'
                : 'Meal order-এর জন্য '.number_format((float) $transaction->amount, 2).' টাকা কাটা হয়েছে।';
        }

        $this->notificationService->sendToUser($user, 'wallet_debit', $title, $message, [
            'wallet_transaction_id' => $transaction->id,
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
            'amount' => (float) $transaction->amount,
        ]);
    }
}
