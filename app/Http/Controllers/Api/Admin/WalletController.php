<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWalletCreditRequest;
use App\Http\Requests\Admin\StoreWalletDebitRequest;
use App\Http\Requests\Admin\UpdateWalletStatusRequest;
use App\Http\Requests\Admin\WalletTransactionIndexRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        $wallets = Wallet::query()
            ->with('user')
            ->when($actor->role === 'manager', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('role', 'customer')))
            ->latest()
            ->get();

        return response()->json(['wallets' => $wallets]);
    }

    public function show(Request $request, Wallet $wallet): JsonResponse
    {
        $this->ensureManageable($request->user(), $wallet);

        return response()->json([
            'wallet' => $wallet->load('user'),
        ]);
    }

    public function updateStatus(UpdateWalletStatusRequest $request, Wallet $wallet): JsonResponse
    {
        $actor = $request->user();
        abort_if($actor->role !== 'admin', 403, 'Only admin can update wallet status.');

        $wallet->update($request->validated());

        return response()->json([
            'message' => 'Wallet status updated successfully.',
            'wallet' => $wallet->fresh()->load('user'),
        ]);
    }

    public function transactions(WalletTransactionIndexRequest $request, Wallet $wallet): JsonResponse
    {
        $this->ensureManageable($request->user(), $wallet);
        $filters = $request->validated();

        $transactions = $wallet->walletTransactions()
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['direction']), fn ($query) => $query->where('direction', $filters['direction']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest()
            ->get();

        return response()->json([
            'filters' => $filters,
            'wallet_transactions' => $transactions,
        ]);
    }

    public function credit(StoreWalletCreditRequest $request, Wallet $wallet): JsonResponse
    {
        $this->ensureManageable($request->user(), $wallet);

        $transaction = $this->walletService->credit($wallet, array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        return response()->json([
            'message' => 'Wallet credited successfully.',
            'wallet' => $wallet->fresh()->load('user'),
            'wallet_transaction' => $transaction,
        ], 201);
    }

    public function debit(StoreWalletDebitRequest $request, Wallet $wallet): JsonResponse
    {
        $this->ensureManageable($request->user(), $wallet);

        $transaction = $this->walletService->debit($wallet, array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        return response()->json([
            'message' => 'Wallet debited successfully.',
            'wallet' => $wallet->fresh()->load('user'),
            'wallet_transaction' => $transaction,
        ], 201);
    }

    private function ensureManageable(User $actor, Wallet $wallet): void
    {
        if ($actor->role === 'admin') {
            return;
        }

        abort_unless($actor->role === 'manager' && $wallet->user->role === 'customer', 403, 'You do not have permission to manage this wallet.');
    }
}
