<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_anyone_can_view_product_details(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
                'amount' => $product->amount,
                'description' => $product->description,
                'is_active' => $product->is_active,
            ]);
    }

    public function test_manager_can_create_product(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $token = $manager->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/products', [
                'name' => 'New Product',
                'amount' => 99.99,
                'description' => 'Product description',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Product created successfully',
                'product' => [
                    'name' => 'New Product',
                    'amount' => 99.99,
                    'description' => 'Product description',
                    'is_active' => true,
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'amount' => 99.99,
        ]);
    }

    public function test_regular_user_cannot_create_product(): void
    {
        $user = User::factory()->create(['role' => 'USER']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/products', [
                'name' => 'New Product',
                'amount' => 99.99,
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_manager_can_update_product(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $token = $manager->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Product',
                'amount' => 149.99,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product updated successfully',
                'product' => [
                    'name' => 'Updated Product',
                    'amount' => 149.99,
                ]
            ]);
    }

    public function test_manager_can_delete_product(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $token = $manager->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product deleted successfully']);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_cannot_delete_product_used_in_transactions(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $token = $manager->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();

        // Simulate product being used in a transaction
        $product->transactionProducts()->create([
            'transaction_id' => 1,
            'quantity' => 1,
            'unit_price' => $product->amount,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete product. It has been used in transactions.']);
    }
}
