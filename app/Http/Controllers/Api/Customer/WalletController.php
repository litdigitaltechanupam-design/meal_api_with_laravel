<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\WalletTransactionListRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return response()->json([
            'wallet' => $wallet->load('user'),
        ]);
    }

    public function transactions(WalletTransactionListRequest $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());
        $filters = $request->validated();

        $transactions = $wallet->walletTransactions()
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['direction']), fn ($query) => $query->where('direction', $filters['direction']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();

        return response()->json([
            'wallet_transactions' => $transactions,
        ]);
    }
}
