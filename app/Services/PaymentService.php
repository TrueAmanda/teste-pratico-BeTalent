<?php

namespace App\Services;

use App\Contracts\GatewayInterface;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Client;
use App\Models\Product;
use App\Models\TransactionProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function processTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Create or find client
            $client = Client::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name']]
            );

            // Calculate total amount from products
            $totalAmount = 0;
            $productsData = [];

            foreach ($data['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];
                $subtotal = $product->amount * $quantity;
                
                $totalAmount += $subtotal;
                $productsData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->amount
                ];
            }

            // Get available gateways first
            $gateways = Gateway::active()
                ->orderByPriority()
                ->get();

            if ($gateways->isEmpty()) {
                throw new \Exception('No active gateways available');
            }

            // Create transaction with the first available gateway
            $transaction = Transaction::create([
                'client_id' => $client->id,
                'gateway_id' => $gateways->first()->id,
                'status' => 'pending',
                'amount' => $totalAmount,
                'card_last_numbers' => substr($data['card_number'], -4),
            ]);

            // Attach products to transaction
            foreach ($productsData as $productData) {
                TransactionProduct::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $productData['product']->id,
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price']
                ]);
            }

            // Try to process with available gateways
            $gateway = $this->processWithGateways($transaction, $data, $gateways);
            
            if ($gateway) {
                $transaction->gateway_id = $gateway->id;
                $transaction->save();
            } else {
                $transaction->status = 'rejected';
                $transaction->save();
                throw new \Exception('All gateways failed to process the transaction');
            }

            return $transaction->load(['client', 'gateway', 'products']);
        });
    }

    private function processWithGateways(Transaction $transaction, array $data, $gateways): ?Gateway
    {
        foreach ($gateways as $gatewayModel) {
            try {
                $gatewayService = $this->getGatewayService($gatewayModel);
                
                if (!$gatewayService->isActive()) {
                    continue;
                }

                $result = $gatewayService->createTransaction([
                    'amount' => (int) ($transaction->amount * 100), // Convert to cents
                    'name' => $transaction->client->name,
                    'email' => $transaction->client->email,
                    'card_number' => $data['card_number'],
                    'cvv' => $data['cvv']
                ]);

                if ($result['success']) {
                    $transaction->external_id = $result['external_id'];
                    $transaction->status = $result['status'];
                    $transaction->gateway_response = $result['response'];
                    $transaction->save();

                    Log::info("Transaction processed successfully", [
                        'transaction_id' => $transaction->id,
                        'gateway' => $gatewayModel->name,
                        'external_id' => $result['external_id']
                    ]);

                    return $gatewayModel;
                } else {
                    Log::warning("Gateway failed to process transaction", [
                        'transaction_id' => $transaction->id,
                        'gateway' => $gatewayModel->name,
                        'error' => $result['error']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Gateway error", [
                    'transaction_id' => $transaction->id,
                    'gateway' => $gatewayModel->name,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }

    public function refundTransaction(Transaction $transaction): bool
    {
        if (!$transaction->external_id || $transaction->status !== 'approved') {
            throw new \Exception('Transaction cannot be refunded');
        }

        try {
            $gatewayService = $this->getGatewayService($transaction->gateway);
            $result = $gatewayService->refundTransaction($transaction->external_id);

            if ($result['success']) {
                $transaction->status = 'refunded';
                $transaction->gateway_response = $result['response'];
                $transaction->save();

                Log::info("Transaction refunded successfully", [
                    'transaction_id' => $transaction->id,
                    'gateway' => $transaction->gateway->name
                ]);

                return true;
            }

            Log::error("Refund failed", [
                'transaction_id' => $transaction->id,
                'gateway' => $transaction->gateway->name,
                'error' => $result['error']
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Refund error", [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function getGatewayService(Gateway $gateway): GatewayInterface
    {
        return match ($gateway->name) {
            'Gateway 1' => new Gateway1Service($gateway),
            'Gateway 2' => new Gateway2Service($gateway),
            default => throw new \Exception("Unknown gateway: {$gateway->name}")
        };
    }
}
