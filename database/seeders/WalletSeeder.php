<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\WalletPaymentService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->first();
        $manager = User::query()->where('role', 'manager')->first();
        $customer = User::query()->where('role', 'customer')->first();

        if (! $customer || ! $manager) {
            return;
        }

        $walletService = app(WalletService::class);
        $walletPaymentService = app(WalletPaymentService::class);

        $wallet = $walletService->getOrCreateWallet($customer);

        $walletService->credit($wallet, [
            'amount' => 5000,
            'type' => 'top_up',
            'payment_method' => 'cash',
            'reference_type' => 'manual',
            'reference_id' => null,
            'status' => 'completed',
            'note' => 'Opening cash top-up',
            'created_by' => $manager->id,
            'approved_by' => $admin?->id,
        ]);

        $walletService->debit($wallet, [
            'amount' => 250,
            'type' => 'meal_charge',
            'payment_method' => 'system',
            'reference_type' => 'meal',
            'reference_id' => 1,
            'status' => 'completed',
            'note' => 'Sample meal charge',
            'created_by' => $admin?->id,
            'approved_by' => $admin?->id,
        ]);

        $paymentRequest = $walletPaymentService->createRequest($customer, [
            'amount' => 1200,
            'payment_method' => 'bkash',
            'note' => 'Sample bKash top-up request',
        ]);

        $walletPaymentService->handleCallback($paymentRequest, [
            'gateway_name' => 'bkash',
            'gateway_transaction_id' => 'BKASH-SAMPLE-1001',
            'status' => 'paid',
            'note' => 'Sample paid callback',
        ]);
    }
}
