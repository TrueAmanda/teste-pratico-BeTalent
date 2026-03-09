<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Client;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $transactions = Transaction::with(['client', 'gateway', 'products'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['client', 'gateway', 'products']);
        
        return response()->json($transaction);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'card_number' => 'required|string|size:16',
            'cvv' => 'required|string|size:3',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $transaction = $this->paymentService->processTransaction($request->all());
            
            return response()->json([
                'message' => 'Transaction processed successfully',
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function refund(Transaction $transaction, Request $request)
    {
        if (!$request->user()->canRefund()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $success = $this->paymentService->refundTransaction($transaction);
            
            if ($success) {
                return response()->json([
                    'message' => 'Transaction refunded successfully',
                    'transaction' => $transaction->fresh()->load(['client', 'gateway', 'products'])
                ]);
            } else {
                return response()->json([
                    'message' => 'Refund failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refund failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
