<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test products
        Product::factory()->create([
            'name' => 'Test Product 1',
            'amount' => 10.00,
            'is_active' => true,
        ]);
        
        Product::factory()->create([
            'name' => 'Test Product 2',
            'amount' => 25.50,
            'is_active' => true,
        ]);
    }

    public function test_public_user_can_create_transaction(): void
    {
        $product1 = Product::first();
        $product2 = Product::skip(1)->first();

        $response = $this->postJson('/api/purchase', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'card_number' => '5569000000006063',
            'cvv' => '010',
            'products' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'transaction' => [
                    'id',
                    'client',
                    'gateway',
                    'status',
                    'amount',
                    'card_last_numbers',
                    'products',
                ]
            ]);

        // Check if client was created
        $this->assertDatabaseHas('clients', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        // Check if transaction was created
        $this->assertDatabaseHas('transactions', [
            'amount' => 45.50, // 2 * 10.00 + 1 * 25.50
            'card_last_numbers' => '6063',
        ]);
    }

    public function test_transaction_validation_requires_required_fields(): void
    {
        $response = $this->postJson('/api/purchase', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email', 
                'card_number',
                'cvv',
                'products'
            ]);
    }

    public function test_authenticated_user_can_list_transactions(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta'
            ]);
    }

    public function test_authenticated_user_can_view_transaction_details(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Create a test transaction
        $client = Client::factory()->create();
        $transaction = $client->transactions()->create([
            'gateway_id' => 1,
            'status' => 'approved',
            'amount' => 100.00,
            'card_last_numbers' => '6063',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'client',
                'gateway',
                'status',
                'amount',
                'card_last_numbers',
                'products'
            ]);
    }

    public function test_finance_user_can_refund_transaction(): void
    {
        $financeUser = User::factory()->create(['role' => 'FINANCE']);
        $token = $financeUser->createToken('test-token')->plainTextToken;

        // Create a test transaction
        $client = Client::factory()->create();
        $transaction = $client->transactions()->create([
            'gateway_id' => 1,
            'status' => 'approved',
            'amount' => 100.00,
            'card_last_numbers' => '6063',
            'external_id' => 'test-external-id',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_refund_transaction(): void
    {
        $regularUser = User::factory()->create(['role' => 'USER']);
        $token = $regularUser->createToken('test-token')->plainTextToken;

        // Create a test transaction
        $client = Client::factory()->create();
        $transaction = $client->transactions()->create([
            'gateway_id' => 1,
            'status' => 'approved',
            'amount' => 100.00,
            'card_last_numbers' => '6063',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/transactions/{$transaction->id}/refund");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }
}
