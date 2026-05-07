<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreWalletPaymentRequest;
use App\Services\WalletPaymentService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletPaymentController extends Controller
{
    public function __construct(
        private WalletPaymentService $walletPaymentService,
        private WalletService $walletService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return response()->json([
            'wallet_payment_requests' => $wallet->walletPaymentRequests()->latest()->get(),
        ]);
    }

    public function store(StoreWalletPaymentRequest $request): JsonResponse
    {
        $paymentRequest = $this->walletPaymentService->createRequest($request->user(), $request->validated());

        return response()->json([
            'message' => 'Wallet payment request created successfully.',
            'wallet_payment_request' => $paymentRequest,
        ], 201);
    }
}
