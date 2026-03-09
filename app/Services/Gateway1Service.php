<?php

namespace App\Services;

use App\Contracts\GatewayInterface;
use App\Models\Gateway as GatewayModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Gateway1Service implements GatewayInterface
{
    private ?string $token = null;
    private GatewayModel $gateway;

    public function __construct(GatewayModel $gateway)
    {
        $this->gateway = $gateway;
    }

    public function authenticate(): bool
    {
        try {
            $response = Http::post($this->gateway->base_url . '/login', [
                'email' => $this->gateway->auth_config['login']['email'],
                'token' => $this->gateway->auth_config['login']['token']
            ]);

            if ($response->successful()) {
                $this->token = $response->json('token');
                return true;
            }

            Log::error('Gateway1 authentication failed', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Gateway1 authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function createTransaction(array $data): array
    {
        if (!$this->token && !$this->authenticate()) {
            throw new \Exception('Authentication failed');
        }

        try {
            $response = Http::withToken($this->token)
                ->post($this->gateway->base_url . '/transactions', [
                    'amount' => $data['amount'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'cardNumber' => $data['card_number'],
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
            Log::error('Gateway1 transaction error', ['error' => $e->getMessage()]);
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
        if (!$this->token && !$this->authenticate()) {
            throw new \Exception('Authentication failed');
        }

        try {
            $response = Http::withToken($this->token)
                ->post($this->gateway->base_url . "/transactions/{$transactionId}/charge_back");

            $responseData = $response->json();

            return [
                'success' => $response->successful(),
                'response' => $responseData,
                'error' => $response->successful() ? null : $responseData['message'] ?? 'Refund failed'
            ];
        } catch (\Exception $e) {
            Log::error('Gateway1 refund error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTransactions(): array
    {
        if (!$this->token && !$this->authenticate()) {
            throw new \Exception('Authentication failed');
        }

        try {
            $response = Http::withToken($this->token)
                ->get($this->gateway->base_url . '/transactions');

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : 'Failed to fetch transactions'
            ];
        } catch (\Exception $e) {
            Log::error('Gateway1 get transactions error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
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
