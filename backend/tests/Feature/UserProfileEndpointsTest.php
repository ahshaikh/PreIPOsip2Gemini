<?php
// V-FINAL-1730-TEST-82 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class UserProfileEndpointsTest extends TestCase
{
    use RefreshDatabase;

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
}