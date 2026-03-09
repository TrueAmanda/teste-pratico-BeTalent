<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('test-token')->plainTextToken;

        User::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data'); // 5 created + 1 admin
    }

    public function test_manager_cannot_list_users(): void
    {
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $token = $manager->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/users');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role' => 'USER',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User created successfully',
                'user' => [
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                    'role' => 'USER',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => 'USER',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('test-token')->plainTextToken;
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/users/{$user->id}", [
                'name' => 'Updated User',
                'role' => 'MANAGER',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User updated successfully',
                'user' => [
                    'name' => 'Updated User',
                    'role' => 'MANAGER',
                ]
            ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('test-token')->plainTextToken;
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User deleted successfully']);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot delete your own account']);
    }

    public function test_user_role_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $manager = User::factory()->create(['role' => 'MANAGER']);
        $finance = User::factory()->create(['role' => 'FINANCE']);
        $user = User::factory()->create(['role' => 'USER']);

        // Test admin permissions
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isManager());
        $this->assertTrue($admin->isFinance());
        $this->assertTrue($admin->canManageUsers());
        $this->assertTrue($admin->canManageProducts());
        $this->assertTrue($admin->canManageGateways());
        $this->assertTrue($admin->canRefund());

        // Test manager permissions
        $this->assertFalse($manager->isAdmin());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isFinance());
        $this->assertFalse($manager->canManageUsers());
        $this->assertTrue($manager->canManageProducts());
        $this->assertFalse($manager->canManageGateways());
        $this->assertFalse($manager->canRefund());

        // Test finance permissions
        $this->assertFalse($finance->isAdmin());
        $this->assertFalse($finance->isManager());
        $this->assertTrue($finance->isFinance());
        $this->assertFalse($finance->canManageUsers());
        $this->assertTrue($finance->canManageProducts());
        $this->assertFalse($finance->canManageGateways());
        $this->assertTrue($finance->canRefund());

        // Test user permissions
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isManager());
        $this->assertFalse($user->isFinance());
        $this->assertFalse($user->canManageUsers());
        $this->assertFalse($user->canManageProducts());
        $this->assertFalse($user->canManageGateways());
        $this->assertFalse($user->canRefund());
    }
}
