<?php
// V-FINAL-1730-TEST-79 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
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

class ServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    private function seedDatabase()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    /** @test */
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
            'gateway_order_id' => 'order_123'
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

    /** @test */
    public function test_email_service_queues_for_async_delivery()
    {
        Queue::fake();
        EmailTemplate::factory()->create(['slug' => 'test.email']);
        $user = User::factory()->create();
        
        $service = $this->app->make(\App\Services\EmailService::class);
        $service->send($user, 'test.email', []);

        Queue::assertPushed(\App\Jobs\ProcessEmailJob::class);
    }

    /** @test */
    public function test_subscription_service_calculates_prorated_amounts()
    {
        $user = User::factory()->create();
        $planA = Plan::factory()->create(['monthly_amount' => 1000]);
        $planB = Plan::factory()->create(['monthly_amount' => 4000]);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $planA->id,
            'status' => 'active',
            'next_payment_date' => now()->addDays(15) // Halfway
        ]);
        
        $service = $this.app->make(\App\Services\SubscriptionService::class);
        
        // (4000-1000) / 30 * 15 = 1500
        $amount = $service->upgradePlan($sub, $planB);

        $this.assertEquals(1500, $amount);
        $this.assertDatabaseHas('payments', ['amount' => 1500, 'gateway' => 'upgrade_charge']);
    }
    
    /** @test */
    public function test_file_upload_service_validates_file_types()
    {
        $service = $this.app->make(FileUploadService::class);
        $fakeFile = UploadedFile::fake()->create('document.zip', 100);

        $this.expectException(\Exception::class);
        $this.expectExceptionMessage("File validation failed: The file field must be a file of type: jpg, jpeg, png, pdf.");

        $service->upload($fakeFile); // Default mimes
    }

    /** @test */
    public function test_file_upload_service_scans_for_viruses()
    {
        $service = $this.app->make(FileUploadService::class);
        // We use the "eicar" standard test file name to trigger the mock scanner
        $virusFile = UploadedFile::fake()->create('eicar.com', 100);

        $this.expectException(\Exception::class);
        $this.expectExceptionMessage("Malware detected in file.");

        $service->upload($virusFile);
    }

    /** @test */
    public function test_file_upload_service_encrypts_sensitive_files()
    {
        Storage::fake('public');
        $service = $this.app->make(FileUploadService::class);
        $file = UploadedFile::fake()->create('secret.txt', 1, 'text/plain');
        
        $path = $service->upload($file, [
            'path' => 'secure',
            'encrypt' => true
        ]);
        
        // 1. Get raw content from disk
        $rawContent = Storage::disk('public')->get($path);
        
        // 2. Assert it is NOT the original content
        $this.assertNotEquals('a', $rawContent);
        
        // 3. Assert we can decrypt it to get original content
        $this.assertEquals('a', Crypt::decrypt($rawContent));
    }

    /** @test */
    public function test_settings_service_invalidates_cache_on_update()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        
        Setting::create(['key' => 'test_cache', 'value' => 'old']);

        // 1. Call helper, which caches 'old'
        $this.assertEquals('old', setting('test_cache'));

        // 2. Admin updates the setting
        $this.actingAs($admin)->putJson('/api/v1/admin/settings', [
            'settings' => [ ['key' => 'test_cache', 'value' => 'new'] ]
        ]);

        // 3. Call helper again. Cache should be busted.
        $this.assertEquals('new', setting('test_cache'));
    }
}