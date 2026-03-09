<?php

namespace App\Contracts;

interface GatewayInterface
{
    public function authenticate(): bool;
    
    public function createTransaction(array $data): array;
    
    public function refundTransaction(string $transactionId): array;
    
    public function getTransactions(): array;
    
    public function getName(): string;
    
    public function isActive(): bool;
}
