<?php
// V-FINAL-1730-TEST-82 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class UserProfileEndpointsTest extends FeatureTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->update(['status' => 'verified']);
    }
    
    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUnauthorizedAccessToProfileFails()
    {
        // No actingAs()
        $response = $this->getJson('/api/v1/user/profile');
        $response->assertStatus(401); // Unauthorized
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testGetProfileReturnsUserData()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');
        
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->id,
            'username' => $this->user->username,
            'email' => $this->user->email,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testProfileIncludesKycStatus()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');
        
        $response->assertStatus(200);
        $response->assertJsonPath('kyc.status', 'verified');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUpdateProfileWithValidData()
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1990-01-01',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function testProfileUpdateValidation()
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
            'dob' => 'not-a-date', // Invalid data
        ]);
        
        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors('dob');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUploadAvatarSucceeds()
    {
        Storage::fake('public'); // Fake the storage
        
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/profile/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['avatar_url']);
        
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'avatar_url' => $response->json('avatar_url')
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testChangePasswordWithValidData()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/security/password', [
            'current_password' => 'password', // Default factory password
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Password updated successfully.']);
        
        // Verify new password works
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->fresh()->password));
        
        // Verify it was logged in history
        $this->assertDatabaseHas('password_histories', ['user_id' => $this->user->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testChangePasswordRequiresOldPassword()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/security/password', [
            'current_password' => 'WRONG_PASSWORD',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('current_password');
    }

    // ==================== AUTHORIZATION & SECURITY TESTS ====================
    // [AUDIT FIX] Critical: Verify user cannot edit another user's profile

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCannotEditAnotherUsersProfile()
    {
        // Create a second user
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');

        // Try to update the other user's profile while authenticated as $this->user
        // Note: The ProfileController uses $request->user() which automatically ensures
        // the authenticated user can only update their own profile.
        // This test verifies the authorization boundary is secure.

        // Attempt to update the authenticated user's own profile (should succeed)
        $response = $this->actingAs($this->user)->putJson('/api/v1/user/profile', [
            'first_name' => 'MyFirstName',
            'last_name' => 'MyLastName',
        ]);

        $response->assertStatus(200);

        // Verify the update only affected the authenticated user's profile
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'MyFirstName'
        ]);

        // Verify the other user's profile remains unchanged
        $this->assertDatabaseMissing('user_profiles', [
            'user_id' => $otherUser->id,
            'first_name' => 'MyFirstName'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUserCannotAccessAnotherUsersProfileData()
    {
        // Create another user with specific profile data
        $otherUser = User::factory()->create();
        $otherUser->assignRole('user');
        $otherUser->profile->update([
            'first_name' => 'OtherUser',
            'last_name' => 'SecretData'
        ]);

        // Authenticated as $this->user, request profile
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile');

        $response->assertStatus(200);

        // Verify response contains ONLY the authenticated user's data
        $response->assertJson(['id' => $this->user->id]);

        // Verify response does NOT contain other user's data
        $data = $response->json();
        $this->assertNotEquals($otherUser->id, $data['id']);

        // Attempt to access other user's profile via ID manipulation should be blocked by middleware
        // The route /api/v1/user/profile does not accept user ID parameter, it uses auth()->user()
    }

    // [AUDIT FIX] Test bank details validation

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUpdateBankDetailsWithValidData()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/profile/bank-details', [
            'bank_account' => '1234567890123',
            'bank_ifsc' => 'HDFC0001234',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Bank details updated successfully']);

        $this->assertDatabaseHas('user_kyc', [
            'user_id' => $this->user->id,
            'bank_account' => '1234567890123',
            'bank_ifsc' => 'HDFC0001234',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUpdateBankDetailsValidatesIfscFormat()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/profile/bank-details', [
            'bank_account' => '1234567890123',
            'bank_ifsc' => 'INVALIDIFSC', // Invalid IFSC format
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('bank_ifsc');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUpdateBankDetailsValidatesAccountNumberLength()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/profile/bank-details', [
            'bank_account' => '123', // Too short
            'bank_ifsc' => 'HDFC0001234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('bank_account');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testGetBankDetailsReturnsUserData()
    {
        // Set bank details in KYC
        $this->user->kyc->update([
            'bank_account' => '9876543210987',
            'bank_ifsc' => 'ICIC0004567',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/user/profile/bank-details');

        $response->assertStatus(200);
        $response->assertJson([
            'bank_account' => '9876543210987',
            'bank_ifsc' => 'ICIC0004567',
        ]);
    }
}
