<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletPaymentRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WalletPaymentService
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function createRequest(User $user, array $data): WalletPaymentRequest
    {
        $wallet = $this->walletService->getOrCreateWallet($user);

        return WalletPaymentRequest::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'gateway_name' => $data['payment_method'],
            'status' => 'pending',
            'requested_at' => now(),
            'note' => $data['note'] ?? null,
        ]);
    }

    public function handleCallback(WalletPaymentRequest $request, array $data)
    {
        return DB::transaction(function () use ($request, $data) {
            if ($request->status === 'paid') {
                return $request->fresh();
            }

            $request->update([
                'gateway_name' => $data['gateway_name'],
                'gateway_transaction_id' => $data['gateway_transaction_id'],
                'status' => $data['status'],
                'paid_at' => $data['status'] === 'paid' ? Carbon::now() : null,
                'note' => $data['note'] ?? $request->note,
            ]);

            if ($data['status'] === 'paid') {
                $this->walletService->credit($request->wallet, [
                    'amount' => $request->amount,
                    'type' => 'top_up',
                    'payment_method' => $request->payment_method,
                    'reference_type' => 'payment',
                    'reference_id' => $request->id,
                    'gateway_name' => $data['gateway_name'],
                    'gateway_transaction_id' => $data['gateway_transaction_id'],
                    'status' => 'completed',
                    'note' => $request->note,
                    'created_by' => $request->user_id,
                ]);
            }

            return $request->fresh();
        });
    }
}
