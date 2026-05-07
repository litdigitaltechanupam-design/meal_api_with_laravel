<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
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

            return WalletTransaction::query()->create([
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

            return WalletTransaction::query()->create([
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
        });
    }

    private function ensureWalletActive(Wallet $wallet): void
    {
        abort_if($wallet->status !== 'active', 422, 'Wallet is not active.');
    }
}
