<?php

namespace App\Services;

use App\Contracts\GatewayInterface;
use App\Models\Gateway as GatewayModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Gateway2Service implements GatewayInterface
{
    private GatewayModel $gateway;

    public function __construct(GatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function authenticate(): bool
    {
        // Gateway 2 uses headers for authentication, no login endpoint
        return true;
    }

    public function createTransaction(array $data): array
    {
        try {
            $headers = $this->getAuthHeaders();
            
            $response = Http::withHeaders($headers)
                ->post($this->gateway->base_url . '/transacoes', [
                    'valor' => $data['amount'],
                    'nome' => $data['name'],
                    'email' => $data['email'],
                    'numeroCartao' => $data['card_number'],
                    'cvv' => $data['cvv']
                ]);

            $responseData = $response->json();

            return [
                'success' => $response->successful(),
                'external_id' => $responseData['id'] ?? null,
                'status' => $response->successful() ? 'approved' : 'rejected',
                'response' => $responseData,
                'error' => $response->successful() ? null : $responseData['message'] ?? 'Transaction failed'
            ];
        } catch (\Exception $e) {
            Log::error('Gateway2 transaction error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'external_id' => null,
                'status' => 'rejected',
                'response' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    public function refundTransaction(string $transactionId): array
    {
        try {
            $headers = $this->getAuthHeaders();
            
            $response = Http::withHeaders($headers)
                ->post($this->gateway->base_url . '/transacoes/reembolso', [
                    'id' => $transactionId
                ]);

            $responseData = $response->json();

            return [
                'success' => $response->successful(),
                'response' => $responseData,
                'error' => $response->successful() ? null : $responseData['message'] ?? 'Refund failed'
            ];
        } catch (\Exception $e) {
            Log::error('Gateway2 refund error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTransactions(): array
    {
        try {
            $headers = $this->getAuthHeaders();
            
            $response = Http::withHeaders($headers)
                ->get($this->gateway->base_url . '/transacoes');

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : 'Failed to fetch transactions'
            ];
        } catch (\Exception $e) {
            Log::error('Gateway2 get transactions error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getAuthHeaders(): array
    {
        return [
            'Gateway-Auth-Token' => $this->gateway->auth_config['headers']['Gateway-Auth-Token'],
            'Gateway-Auth-Secret' => $this->gateway->auth_config['headers']['Gateway-Auth-Secret'],
        ];
    }

    public function getName(): string
    {
        return $this->gateway->name;
    }

    public function isActive(): bool
    {
        return $this->gateway->is_active;
    }
}
