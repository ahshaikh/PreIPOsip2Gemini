<?php
// V-FINAL-1730-TEST-79 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Services\PaymentWebhookService;
use App\Services\FileUploadService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class ServiceIntegrationTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        // ProductSeeder is now self-contained - no UserSeeder coupling required
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_webhook_service_triggers_bonus_and_allocation()
    {
        // 1. Setup
        Queue::fake(); // We don't want jobs to run, just to be queued
        $user = User::factory()->create();
        $sub = Subscription::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $sub->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_123',
            'amount' => 1000,
            'amount_paise' => 100000, // V-PAYMENT-INTEGRITY-2026: Integer paise required
        ]);

        $service = $this->app->make(PaymentWebhookService::class);
        
        // 2. Act
        $service->handleSuccessfulPayment([
            'order_id' => 'order_123',
            'id' => 'pay_123'
        ]);

        // 3. Assert
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_email_service_queues_for_async_delivery()
    {
        Queue::fake();
        // Create EmailTemplate directly since no factory exists
        EmailTemplate::create([
            'slug' => 'test.email',
            'name' => 'Test Email',
            'subject' => 'Test Subject',
            'body' => 'Test body content',
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        $service = $this->app->make(\App\Services\EmailService::class);
        $service->send($user, 'test.email', []);

        Queue::assertPushed(\App\Jobs\ProcessEmailJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_service_calculates_upgrade_differential()
    {
        // BILLING DOCTRINE: Upgrade charges use flat differential (newAmount - oldAmount)
        // This is NOT time-based proration. See V-FINAL-1730-578 (V2.0 Proration).
        $user = User::factory()->create();
        $planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $planB = Plan::factory()->create(['monthly_amount' => 4000]);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $planA->id,
            'amount' => $planA->monthly_amount,
            'status' => 'active',
        ]);

        $service = $this->app->make(\App\Services\SubscriptionService::class);

        // Upgrade differential = newPlanAmount - currentPlanAmount
        // 4000 - 1000 = 3000
        $amount = $service->upgradePlan($sub, $planB);

        $this->assertEquals(3000, $amount);
        $this->assertDatabaseHas('payments', [
            'amount' => 3000,
            'payment_type' => \App\Enums\PaymentType::UPGRADE_CHARGE->value,
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_file_upload_service_validates_file_types()
    {
        $service = $this->app->make(FileUploadService::class);
        $fakeFile = UploadedFile::fake()->create('document.zip', 100);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("File validation failed: The file field must be a file of type: jpg, jpeg, png, pdf.");

        $service->upload($fakeFile); // Default mimes
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_file_upload_service_scans_for_viruses()
    {
        $service = $this->app->make(FileUploadService::class);
        // We use the "eicar" standard test file name to trigger the mock scanner
        // Create with image/jpeg mime type to pass validation, name triggers virus scan
        $virusFile = UploadedFile::fake()->create('eicar.com', 100, 'image/jpeg');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Malware detected in file.");

        $service->upload($virusFile, [
            'allowed_mimes' => 'jpg,jpeg,png,pdf,com', // Include .com extension
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_file_upload_service_encrypts_sensitive_files()
    {
        Storage::fake('public');
        $service = $this->app->make(FileUploadService::class);

        // Create a fake JPEG image with known, identifiable content
        // UploadedFile::fake()->image() creates valid image with proper MIME headers
        $fakeImage = UploadedFile::fake()->image('secret.jpg', 10, 10);

        // Read original content before upload for strict comparison
        $originalContent = file_get_contents($fakeImage->getRealPath());

        $path = $service->upload($fakeImage, [
            'path' => 'secure',
            'encrypt' => true
        ]);

        // 1. Get raw content from disk
        $rawContent = Storage::disk('public')->get($path);

        // 2. STRICT: Assert encrypted content is NOT the original
        $this->assertNotEquals($originalContent, $rawContent, 'Encrypted content must differ from original');

        // 3. STRICT: Assert decrypted content IS EXACTLY the original
        $decrypted = Crypt::decrypt($rawContent);
        $this->assertEquals($originalContent, $decrypted, 'Decrypted content must match original exactly');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_settings_service_invalidates_cache_on_update()
    {
        // Create required permission for admin settings endpoint
        \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'settings.edit_system',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        // Super-admin role must have the permission (re-sync after creating permission)
        $admin->givePermissionTo('settings.edit_system');

        Setting::create(['key' => 'test_cache_' . uniqid(), 'value' => 'old', 'type' => 'string']);
        $testKey = Setting::latest('id')->first()->key;

        // 1. Call helper, which caches 'old'
        $this->assertEquals('old', setting($testKey));

        // 2. Admin updates the setting via API - cache invalidation happens here
        $response = $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
            'settings' => [['key' => $testKey, 'value' => 'new']]
        ]);
        $response->assertStatus(200);

        // 3. Call helper again - cache should be naturally invalidated by controller
        $this->assertEquals('new', setting($testKey));
    }
}
