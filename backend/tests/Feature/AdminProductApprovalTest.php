<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductAudit;

class AdminProductApprovalTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create a regular user
        $this->user = User::factory()->create();
    }

    public function non_admins_cannot_access_approval_endpoints()
    {
        $product = Product::factory()->create(['status' => 'submitted']);

        $this->actingAs($this->user)
             ->getJson('/api/v1/admin/products/submitted')
             ->assertStatus(403);

        $this->actingAs($this->user)
             ->postJson("/api/v1/admin/products/{$product->id}/approve")
             ->assertStatus(403);

        $this->actingAs($this->user)
             ->postJson("/api/v1/admin/products/{$product->id}/reject", ['reason' => 'test'])
             ->assertStatus(403);
    }

    public function guests_cannot_access_approval_endpoints()
    {
        $product = Product::factory()->create(['status' => 'submitted']);

        $this->getJson('/api/v1/admin/products/submitted')->assertUnauthorized();
        $this->postJson("/api/v1/admin/products/{$product->id}/approve")->assertUnauthorized();
        $this->postJson("/api/v1/admin/products/{$product->id}/reject", ['reason' => 'test'])->assertUnauthorized();
    }


    public function it_returns_only_submitted_products()
    {
        Product::factory()->create(['status' => 'submitted', 'name' => 'Submitted Product']);
        Product::factory()->create(['status' => 'draft']);
        Product::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($this->admin)
                         ->getJson('/api/v1/admin/products/submitted');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Submitted Product']);
    }

    public function it_can_approve_a_submitted_product()
    {
        $product = Product::factory()->create(['status' => 'submitted']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'submitted']);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/products/{$product->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'approved']);

        // Assert audit log was created
        $this->assertDatabaseHas('product_audits', [
            'product_id' => $product->id,
            'action' => 'approved',
            'performed_by' => $this->admin->id,
        ]);
    }

    public function it_cannot_approve_a_non_submitted_product()
    {
        $product = Product::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/products/{$product->id}/approve");

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'draft']);
    }

    public function it_can_reject_a_submitted_product()
    {
        $product = Product::factory()->create(['status' => 'submitted']);
        $rejectionReason = 'This product does not meet our quality standards.';

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/products/{$product->id}/reject", [
                             'reason' => $rejectionReason,
                         ]);

        $response->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'rejected']);

        // Assert audit log was created with the correct reason
        $this->assertDatabaseHas('product_audits', [
            'product_id' => $product->id,
            'action' => 'rejected',
            'performed_by' => $this->admin->id,
            'reason' => $rejectionReason,
        ]);
    }

    public function it_cannot_reject_a_non_submitted_product()
    {
        $product = Product::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/products/{$product->id}/reject", [
                             'reason' => 'test reason',
                         ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'approved']);
    }

    public function rejection_requires_a_reason()
    {
        $product = Product::factory()->create(['status' => 'submitted']);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/products/{$product->id}/reject", [
                             'reason' => '',
                         ]);

        $response->assertStatus(422); // Unprocessable Entity for validation failure
        $response->assertJsonValidationErrors('reason');
    }
}
