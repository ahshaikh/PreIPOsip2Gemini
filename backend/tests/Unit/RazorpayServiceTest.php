<?php
// V-FINAL-1730-TEST-23

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RazorpayService;
use Mockery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RazorpayServiceTest extends TestCase
{
    protected $service;
    protected $mockApi;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Config
        Config::set('services.razorpay.key', 'test_key');
        Config::set('services.razorpay.secret', 'test_secret');

        $this->service = new RazorpayService();
        
        // Create a Mock for the Razorpay\Api\Api class
        $this->mockApi = Mockery::mock('Razorpay\Api\Api');
        $this->service->setApi($this->mockApi);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_create_order_calls_razorpay_api()
    {
        // Mock the 'order' property and its 'create' method
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['receipt'] === 'rec_123';
            }))
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(100, 'rec_123');
        
        $this->assertEquals('order_123', $result->id);
    }

    /** @test */
    public function test_create_order_sets_correct_amount()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                // Should be converted to paise (100 * 100 = 10000)
                return $data['amount'] === 10000;
            }))
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $this->service->createOrder(100, 'rec_123');
    }

    /** @test */
    public function test_create_order_sets_currency_inr()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::subset(['currency' => 'INR']))
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $this->service->createOrder(100, 'rec_123');
    }

    /** @test */
    public function test_create_order_includes_receipt()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::subset(['receipt' => 'rec_unique_1']))
            ->andReturn((object)['id' => 'order_1']);

        $this->mockApi->order = $orderMock;

        $this->service->createOrder(100, 'rec_unique_1');
    }

    /** @test */
    public function test_create_order_handles_api_failure()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception("Network Error"));

        $this->mockApi->order = $orderMock;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Network Error");

        $this->service->createOrder(100, 'rec_123');
    }

    /** @test */
    public function test_verify_signature_validates_correct_signature()
    {
        $utilityMock = Mockery::mock();
        $utilityMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->with(['a' => 1])
            ->andReturnNull(); // Returns null on success, throws on fail

        $this->mockApi->utility = $utilityMock;

        $result = $this->service->verifySignature(['a' => 1]);
        $this->assertTrue($result);
    }

    /** @test */
    public function test_verify_signature_rejects_invalid_signature()
    {
        $utilityMock = Mockery::mock();
        $utilityMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->andThrow(new \Exception("Invalid Signature"));

        $this->mockApi->utility = $utilityMock;

        $result = $this->service->verifySignature(['a' => 1]);
        $this->assertFalse($result);
    }

    /** @test */
    public function test_capture_payment_calls_razorpay_api()
    {
        $paymentMock = Mockery::mock();
        
        // First fetch
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->status = 'authorized';
        $fetchedPayment->shouldReceive('capture')
            ->once()
            ->with(['amount' => 50000]) // 500 * 100
            ->andReturnSelf();

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $this->service->capturePayment('pay_123', 500);
    }

    /** @test */
    public function test_capture_payment_validates_amount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Amount must be positive");

        $this->service->capturePayment('pay_123', -100);
    }

    /** @test */
    public function test_refund_payment_calls_razorpay_api()
    {
        $paymentMock = Mockery::mock();
        
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->shouldReceive('refund')
            ->once()
            ->with(['amount' => 10000]) // 100 * 100
            ->andReturn((object)['id' => 'rfnd_123']);

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $this->service->refundPayment('pay_123', 100);
    }

    /** @test */
    public function test_refund_payment_handles_partial_refund()
    {
        $paymentMock = Mockery::mock();
        
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->shouldReceive('refund')
            ->once()
            ->with(['amount' => 5000]) // 50 * 100
            ->andReturn((object)['id' => 'rfnd_123']);

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $this->service->refundPayment('pay_123', 50);
    }

    /** @test */
    public function test_fetch_payment_details_calls_api()
    {
        $paymentMock = Mockery::mock();
        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn(['id' => 'pay_123']);

        $this->mockApi->payment = $paymentMock;

        $this->service->fetchPayment('pay_123');
    }

    /** @test */
    public function test_webhook_signature_validates_correctly()
    {
        $utilityMock = Mockery::mock();
        $utilityMock->shouldReceive('verifyWebhookSignature')
            ->once()
            ->with('payload', 'sig', 'secret')
            ->andReturnNull();

        $this->mockApi->utility = $utilityMock;

        $result = $this->service->verifyWebhookSignature('payload', 'sig', 'secret');
        $this->assertTrue($result);
    }

    /** @test */
    public function test_service_uses_correct_environment_keys()
    {
        // Re-instantiate to test constructor logic
        $service = new RazorpayService();
        // We can't easily access protected properties, but we can check if API is set
        // If keys are present (set in setUp), API should be initialized
        $this->assertNotNull($service->getApi());
    }

    /** @test */
    public function test_service_logs_all_api_calls()
    {
        Log::shouldReceive('info')
            ->atLeast()->once()
            ->withArgs(function($msg) {
                return str_contains($msg, '[RazorpayService]');
            });

        // Setup a simple call
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')->andReturn((object)['id' => '1']);
        $this->mockApi->order = $orderMock;

        $this->service->createOrder(100, 'rec_1');
    }
}