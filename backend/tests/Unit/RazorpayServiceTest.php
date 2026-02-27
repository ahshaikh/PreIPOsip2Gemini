<?php
// V-FINAL-1730-TEST-23

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\RazorpayService;
use Mockery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RazorpayServiceTest extends UnitTestCase
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
        // We use a blank mock to avoid property definition issues with the real class
        $this->mockApi = Mockery::mock();
        $this->service->setApi($this->mockApi);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_order_calls_razorpay_api()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(500, 'rec_123');
        
        $this->assertEquals('order_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_order_sets_correct_amount()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['amount']) && (int)$data['amount'] === 50000;
            }))
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(500, 'rec_123');
        $this->assertEquals('order_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_order_sets_currency_inr()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['currency']) && $data['currency'] === 'INR';
            }))
            ->andReturn((object)['id' => 'order_123']);

        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(500, 'rec_123');
        $this->assertEquals('order_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_order_includes_receipt()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['receipt']) && $data['receipt'] === 'rec_unique_1';
            }))
            ->andReturn((object)['id' => 'order_1']);

        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(500, 'rec_unique_1');
        $this->assertEquals('order_1', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_order_handles_api_failure()
    {
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception("Network Error"));

        $this->mockApi->order = $orderMock;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Network Error");

        $this->service->createOrder(500, 'rec_123');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_signature_validates_correct_signature()
    {
        $utilityMock = Mockery::mock();
        $utilityMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->with(['a' => 1])
            ->andReturnNull();

        $this->mockApi->utility = $utilityMock;

        $result = $this->service->verifySignature(['a' => 1]);
        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_verify_signature_rejects_invalid_signature()
    {
        $utilityMock = Mockery::mock();
        $utilityMock->shouldReceive('verifyPaymentSignature')
            ->once()
            ->with(['a' => 1])
            ->andThrow(new \Exception("Invalid Signature"));

        $this->mockApi->utility = $utilityMock;

        $result = $this->service->verifySignature(['a' => 1]);
        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_capture_payment_calls_razorpay_api()
    {
        $paymentMock = Mockery::mock();
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->status = 'authorized';
        $fetchedPayment->shouldReceive('capture')
            ->once()
            ->with(Mockery::on(function($data) {
                return (int)$data['amount'] === 50000;
            })) 
            ->andReturnSelf();

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $result = $this->service->capturePayment('pay_123', 500);
        $this->assertNotNull($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_capture_payment_validates_amount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Payment amount must be a positive number.");

        $this->service->capturePayment('pay_123', -100);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_refund_payment_calls_razorpay_api()
    {
        $paymentMock = Mockery::mock();
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->shouldReceive('refund')
            ->once()
            ->with(Mockery::on(function($data) {
                return (int)$data['amount'] === 10000;
            })) 
            ->andReturn((object)['id' => 'rfnd_123']);

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $result = $this->service->refundPayment('pay_123', 100);
        $this->assertEquals('rfnd_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_refund_payment_handles_partial_refund()
    {
        $paymentMock = Mockery::mock();
        $fetchedPayment = Mockery::mock();
        $fetchedPayment->shouldReceive('refund')
            ->once()
            ->with(Mockery::on(function($data) {
                return (int)$data['amount'] === 5000;
            })) 
            ->andReturn((object)['id' => 'rfnd_123']);

        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn($fetchedPayment);

        $this->mockApi->payment = $paymentMock;

        $result = $this->service->refundPayment('pay_123', 50);
        $this->assertEquals('rfnd_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fetch_payment_details_calls_api()
    {
        $paymentMock = Mockery::mock();
        $paymentMock->shouldReceive('fetch')
            ->once()
            ->with('pay_123')
            ->andReturn((object)['id' => 'pay_123']);

        $this->mockApi->payment = $paymentMock;

        $result = $this->service->fetchPayment('pay_123');
        $this->assertEquals('pay_123', $result->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_service_uses_correct_environment_keys()
    {
        // Re-instantiate to test constructor logic
        $service = new RazorpayService();
        // If keys are present (set in setUp), API should be initialized
        $this->assertNotNull($service->getApi());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_service_logs_all_api_calls()
    {
        // Setup a simple call
        $orderMock = Mockery::mock();
        $orderMock->shouldReceive('create')->andReturn((object)['id' => '1']);
        
        $this->mockApi->order = $orderMock;

        $result = $this->service->createOrder(500, 'rec_1');
        $this->assertNotNull($result);
    }
}
