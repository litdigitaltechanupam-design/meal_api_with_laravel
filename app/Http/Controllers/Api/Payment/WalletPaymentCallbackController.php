<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\WalletPaymentCallbackRequest;
use App\Models\WalletPaymentRequest;
use App\Services\WalletPaymentService;
use Illuminate\Http\JsonResponse;

class WalletPaymentCallbackController extends Controller
{
    public function __construct(private WalletPaymentService $walletPaymentService)
    {
    }

    public function store(WalletPaymentCallbackRequest $request): JsonResponse
    {
        $paymentRequest = WalletPaymentRequest::query()->findOrFail($request->integer('wallet_payment_request_id'));
        $updatedRequest = $this->walletPaymentService->handleCallback($paymentRequest, $request->validated());

        return response()->json([
            'message' => 'Wallet payment callback processed successfully.',
            'wallet_payment_request' => $updatedRequest,
        ]);
    }
}
