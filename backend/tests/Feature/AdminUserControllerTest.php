<?php
// V-TEST-SUITE-002 (AdminUserController Feature Tests)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminUserControllerTest extends FeatureTestCase
{
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
        
        UserProfile::updateOrCreate(['user_id' => $this->user->id], ['first_name' => 'John', 'last_name' => 'Doe']);
        
        // Bypass state machine guards during test setup
        \Illuminate\Support\Facades\DB::table('user_kyc')->updateOrInsert(
            ['user_id' => $this->user->id],
            [
                'status' => 'pending',
                'pan_number' => 'ABCDE1234F',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        Wallet::updateOrCreate(['user_id' => $this->user->id], [
            'balance_paise' => 500000, // ₹5000 in paise
            'locked_balance_paise' => 50000 // ₹500 in paise
        ]);
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
        // V-WAVE1-FIX: Create proper FK relationships instead of hardcoded IDs
        $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
        ]);

        BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
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

    // [AUDIT FIX] Critical: Test creating user with existing email
    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_unique_email()
    {
        $userData = [
            'username' => 'newusername123',
            'email' => $this->user->email, // Existing email - SECURITY RISK if not validated
            'mobile' => '9876543211',
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Verify no duplicate user was created
        $this->assertDatabaseMissing('users', [
            'username' => 'newusername123',
            'email' => $this->user->email
        ]);
    }

    // [AUDIT FIX] Test creating user with existing mobile number
    #[\PHPUnit\Framework\Attributes\Test]
    public function store_validates_unique_mobile()
    {
        $userData = [
            'username' => 'newusername456',
            'email' => 'newemail@test.com',
            'mobile' => $this->user->mobile, // Existing mobile
            'password' => 'SecurePass123!',
            'role' => 'user'
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mobile']);
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

    // [AUDIT FIX] Critical: Test updating user profile data
    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_user_profile_fields()
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$this->user->id}", [
                'profile' => [
                    'first_name' => 'UpdatedFirst',
                    'last_name' => 'UpdatedLast',
                    'city' => 'UpdatedCity'
                ]
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'city' => 'UpdatedCity'
        ]);
    }

    // [AUDIT FIX] Test validation for updating with existing email
    #[\PHPUnit\Framework\Attributes\Test]
    public function update_validates_unique_email_on_change()
    {
        // Create another user
        $otherUser = User::factory()->create(['email' => 'existing@test.com']);
        $otherUser->assignRole('user');

        // Try to update current user with the other user's email
        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/users/{$this->user->id}", [
                'email' => 'existing@test.com' // This email already exists
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Verify user's email was not changed
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $this->user->email // Original email unchanged
        ]);
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

    // ==================== COMPREHENSIVE AUDIT LOGGING TESTS ====================
    // [AUDIT FIX] Verify all admin actions are properly logged for compliance

    #[\PHPUnit\Framework\Attributes\Test]
    public function block_logs_activity_with_reason()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/block", [
                'reason' => 'Fraudulent activity detected',
                'blacklist' => true
            ]);

        $response->assertStatus(200);

        // Verify activity was logged with complete details
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'admin_blocked'
        ]);

        // Verify the log description contains the reason
        $log = \App\Models\ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'admin_blocked')
            ->first();

        $this->assertStringContainsString('Fraudulent activity detected', $log->description);
        $this->assertStringContainsString('Blacklisted: Yes', $log->description);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unblock_logs_activity()
    {
        // First block the user
        $this->user->update(['status' => 'blocked']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/unblock");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'admin_unblocked'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unsuspend_logs_activity()
    {
        // First suspend the user
        $this->user->update(['status' => 'suspended']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/unsuspend");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'admin_unsuspended'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function force_payment_logs_activity()
    {
        // Create a plan and subscription for the user
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->user->id}/force-payment", [
                'subscription_id' => $subscription->id,
                'amount' => 5000,
                'reason' => 'Waived payment for loyal customer'
            ]);

        $response->assertStatus(200);

        // Verify activity was logged
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'admin_force_payment'
        ]);

        // Verify log contains amount and reason
        $log = \App\Models\ActivityLog::where('user_id', $this->user->id)
            ->where('action', 'admin_force_payment')
            ->first();

        $this->assertStringContainsString('₹5000', $log->description);
        $this->assertStringContainsString('Waived payment for loyal customer', $log->description);
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
            Wallet::create(['user_id' => $u->id, 'balance_paise' => 0, 'locked_balance_paise' => 0]);
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
            ->get('/api/v1/admin/users/export/csv');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="users_export.csv"');
    }
}
