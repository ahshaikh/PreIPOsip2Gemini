<?php
// V-TEST-SUITE-002 (AdminUserController Feature Tests)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create(['status' => 'active']);
        $this->user->assignRole('user');
        UserProfile::create(['user_id' => $this->user->id, 'first_name' => 'John', 'last_name' => 'Doe']);
        UserKyc::create(['user_id' => $this->user->id, 'status' => 'pending', 'pan_number' => 'ABCDE1234F']);
        Wallet::create(['user_id' => $this->user->id, 'balance' => 5000, 'locked_balance' => 500]);
    }

    // ==================== INDEX TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_list_users()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'username', 'email', 'status']
                ],
                'current_page',
                'total'
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_search_users_by_username()
    {
        User::factory()->create(['username' => 'searchme123'])->assignRole('user');
        User::factory()->create(['username' => 'other456'])->assignRole('user');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=searchme');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('searchme123', $data[0]['username']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_search_users_by_email()
    {
        User::factory()->create(['email' => 'findme@test.com'])->assignRole('user');

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=findme@test');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_list_users()
    {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_user_cannot_list_users()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    // ==================== SHOW TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_user_details()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'username',
                'email',
                'mobile',
                'status',
                'profile',
                'kyc',
                'wallet',
                'stats'
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function show_masks_sensitive_data()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$this->user->id}");

        $response->assertStatus(200);

        // PAN should be masked (last 4 chars only)
        $kyc = $response->json('kyc');
        $this->assertEquals('****234F', $kyc['pan_number']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function show_returns_controlled_response()
    {
        // Create subscription with plan for complete test
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$this->user->id}");

        $response->assertStatus(200);

        // Verify wallet shows only balance, not full transaction history
        $wallet = $response->json('wallet');
        $this->assertArrayHasKey('balance', $wallet);
        $this->assertArrayHasKey('locked_balance', $wallet);
        $this->assertArrayNotHasKey('transactions', $wallet);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function show_returns_stats_summary()
    {
        BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => 1,
            'payment_id' => 1,
            'type' => 'consistency',
            'amount' => 100,
            'multiplier_applied' => 1.0
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$this->user->id}");

        $response->assertStatus(200);
        $stats = $response->json('stats');

        $this->assertArrayHasKey('total_payments', $stats);
        $this->assertArrayHasKey('total_bonuses', $stats);
        $this->assertArrayHasKey('referral_count', $stats);
        $this->assertArrayHasKey('open_tickets', $stats);
        $this->assertEquals(100, $stats['total_bonuses']);
    }

    // ==================== STORE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_user()
    {
        $userData = [
            'username' => 'newuser123',
            'email' => 'newuser@test.com',
            'mobile' => '9876543210',
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'username' => 'newuser123',
            'email' => 'newuser@test.com'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_unique_username()
    {
        $userData = [
            'username' => $this->user->username, // Existing username
            'email' => 'different@test.com',
            'mobile' => '9876543211',
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_mobile_format()
    {
        $userData = [
            'username' => 'newuser456',
            'email' => 'new@test.com',
            'mobile' => '123', // Invalid format
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mobile']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_creates_associated_records()
    {
        $userData = [
            'username' => 'completeuser',
            'email' => 'complete@test.com',
            'mobile' => '9876543299',
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(201);
        $userId = $response->json('id');

        // Verify related records created
        $this->assertDatabaseHas('user_profiles', ['user_id' => $userId]);
        $this->assertDatabaseHas('user_kyc', ['user_id' => $userId]);
        $this->assertDatabaseHas('wallets', ['user_id' => $userId]);
    }

    // ==================== UPDATE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_user_status()
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$this->user->id}", [
                'status' => 'suspended'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'status' => 'suspended'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_validates_status_enum()
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$this->user->id}", [
                'status' => 'invalid_status'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ==================== SUSPEND TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_suspend_user_with_reason()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/suspend", [
                'reason' => 'Suspicious activity detected'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'status' => 'suspended'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function suspend_requires_reason()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/suspend", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function suspend_logs_activity()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/suspend", [
                'reason' => 'Policy violation'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'admin_suspended'
        ]);
    }

    // ==================== ADJUST BALANCE TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_credit_user_wallet()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/adjust-balance", [
                'type' => 'credit',
                'amount' => 1000,
                'description' => 'Promotional credit'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(6000, $response->json('new_balance')); // 5000 + 1000
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_debit_user_wallet()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/adjust-balance", [
                'type' => 'debit',
                'amount' => 500,
                'description' => 'Fee adjustment'
            ]);

        $response->assertStatus(200);
        $this->assertEquals(4500, $response->json('new_balance')); // 5000 - 500
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function debit_fails_for_insufficient_balance()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/adjust-balance", [
                'type' => 'debit',
                'amount' => 10000, // More than balance
                'description' => 'Large debit'
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Insufficient funds. Available: ₹5000']);
    }

    // ==================== BULK ACTION TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_bulk_activate_users()
    {
        $users = User::factory()->count(3)->create(['status' => 'suspended']);
        foreach ($users as $u) {
            $u->assignRole('user');
        }

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/bulk-action', [
                'user_ids' => $users->pluck('id')->toArray(),
                'action' => 'activate'
            ]);

        $response->assertStatus(200);
        foreach ($users as $u) {
            $this->assertDatabaseHas('users', [
                'id' => $u->id,
                'status' => 'active'
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_bulk_suspend_users()
    {
        $users = User::factory()->count(2)->create(['status' => 'active']);
        foreach ($users as $u) {
            $u->assignRole('user');
        }

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/bulk-action', [
                'user_ids' => $users->pluck('id')->toArray(),
                'action' => 'suspend'
            ]);

        $response->assertStatus(200);
        foreach ($users as $u) {
            $this->assertDatabaseHas('users', [
                'id' => $u->id,
                'status' => 'suspended'
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_bulk_award_bonus()
    {
        $users = User::factory()->count(2)->create();
        foreach ($users as $u) {
            $u->assignRole('user');
            Wallet::create(['user_id' => $u->id, 'balance' => 0]);
        }

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/bulk-action', [
                'user_ids' => $users->pluck('id')->toArray(),
                'action' => 'bonus',
                'data' => ['amount' => 500]
            ]);

        $response->assertStatus(200);
        foreach ($users as $u) {
            $this->assertEquals(500, $u->wallet->fresh()->balance);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function bulk_bonus_validates_amount()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/bulk-action', [
                'user_ids' => [$this->user->id],
                'action' => 'bonus',
                'data' => ['amount' => 0]
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid bonus amount']);
    }

    // ==================== IMPORT TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_import_users_from_csv()
    {
        Storage::fake('local');

        $csvContent = "username,email,mobile\nimportuser1,import1@test.com,9876543001\nimportuser2,import2@test.com,9876543002";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/import', [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['username' => 'importuser1']);
        $this->assertDatabaseHas('users', ['username' => 'importuser2']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function import_validates_csv_headers()
    {
        Storage::fake('local');

        $csvContent = "name,phone,addr\nJohn,123,test"; // Wrong headers
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/import', [
                'file' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => "Missing required column: 'username'. Expected headers: username, email, mobile"]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function import_skips_duplicate_users()
    {
        Storage::fake('local');

        $csvContent = "username,email,mobile\n{$this->user->username},{$this->user->email},9876543099";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/import', [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertEquals(1, $response->json('skipped'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function import_validates_email_format()
    {
        Storage::fake('local');

        $csvContent = "username,email,mobile\nbaduser,invalid-email,9876543003";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/import', [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertStringContainsString('Invalid email format', $response->json('errors')[0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function import_validates_mobile_format()
    {
        Storage::fake('local');

        $csvContent = "username,email,mobile\nbadmobile,valid@test.com,123"; // Invalid mobile
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/import', [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertStringContainsString('Invalid mobile format', $response->json('errors')[0]);
    }

    // ==================== EXPORT TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_export_users_to_csv()
    {
        $response = $this->actingAs($this->admin)
            ->get('/api/v1/admin/users/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="users_export.csv"');
    }
}
