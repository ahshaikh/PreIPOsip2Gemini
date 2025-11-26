# Circuit Breaker Pattern Implementation Guide

## Overview

The circuit breaker pattern protects the application from cascading failures when external services (payment gateways, SMS providers, email services) are down or slow. It acts like an electrical circuit breaker - automatically "opening" when too many failures occur, preventing further damage.

## Architecture

### Circuit States

1. **CLOSED** (Normal Operation)
   - All requests pass through to the external service
   - Failures are counted
   - Transitions to OPEN when failure threshold is reached

2. **OPEN** (Service Down)
   - Requests fail immediately without calling the external service
   - Returns fallback value or throws exception
   - Automatically transitions to HALF_OPEN after timeout period

3. **HALF_OPEN** (Testing Recovery)
   - Limited requests are allowed to test if service recovered
   - Success → transitions to CLOSED
   - Failure → transitions back to OPEN

### State Transitions

```
CLOSED --[5 failures]--> OPEN --[60s timeout]--> HALF_OPEN --[2 successes]--> CLOSED
   ↑                                                  |
   └────────────────────[failure]────────────────────┘
```

## Implementation

### Core Service

**Location:** `backend/app/Services/CircuitBreakerService.php`

```php
$breaker = new CircuitBreakerService(
    serviceName: 'razorpay-orders',
    failureThreshold: 5,    // Failures before opening circuit
    successThreshold: 2,    // Successes to close circuit
    timeout: 60,            // Seconds before retry
    halfOpenAttempts: 3     // Max attempts in half-open state
);

$result = $breaker->call(
    callback: fn() => $externalApi->call(),
    fallback: $defaultValue
);
```

### Trait for Easy Integration

**Location:** `backend/app/Services/Traits/HasCircuitBreaker.php`

```php
use App\Services\Traits\HasCircuitBreaker;

class MyService
{
    use HasCircuitBreaker;

    public function callExternalApi()
    {
        return $this->withCircuitBreaker(
            'my-service',
            fn() => Http::post('https://api.example.com'),
            fallback: null,
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 60,
            ]
        );
    }
}
```

## Resilient Service Wrappers

### 1. Razorpay (Payment Gateway)

**Location:** `backend/app/Services/ResilientRazorpayService.php`

**Configuration:**
- Failure Threshold: 5 failures
- Success Threshold: 2 successes
- Timeout: 120 seconds (payments can be slow)
- Half-Open Attempts: 3

**Usage:**
```php
use App\Services\ResilientRazorpayService;

$razorpay = app(ResilientRazorpayService::class);
$order = $razorpay->createOrder($amount, $receipt);

if ($order === null) {
    // Circuit is open - Razorpay is unavailable
    // Show maintenance message or queue for later
}
```

**Operations Protected:**
- `createOrder()` - Returns `null` if circuit open
- `createOrUpdateRazorpayPlan()` - Returns `null`
- `createRazorpaySubscription()` - Returns `null`
- `verifySignature()` - Returns `false` (fail-safe)
- `refundPayment()` - Throws exception (critical operation)
- `fetchPayment()` - Returns `null`

### 2. SMS Gateway (MSG91, Twilio)

**Location:** `backend/app/Services/ResilientSmsService.php`

**Configuration:**
- Failure Threshold: 3 failures (SMS gateways can be flaky)
- Success Threshold: 2 successes
- Timeout: 300 seconds (5 minutes - non-critical)
- Half-Open Attempts: 2

**Usage:**
```php
use App\Services\ResilientSmsService;

$sms = app(ResilientSmsService::class);
$log = $sms->send($user, $message, $templateSlug);

if ($log === null) {
    // Circuit is open or user opted out
    // SMS is non-critical, continue without it
}
```

### 3. Email Service (SMTP, SES, Mailgun)

**Location:** `backend/app/Services/ResilientEmailService.php`

**Configuration:**
- Failure Threshold: 5 failures
- Success Threshold: 2 successes
- Timeout: 180 seconds (3 minutes)
- Half-Open Attempts: 3

**Usage:**
```php
use App\Services\ResilientEmailService;

$email = app(ResilientEmailService::class);
$log = $email->send($user, $templateSlug, $variables);

if ($log === null) {
    // Circuit is open or user opted out
    // Emails are queued, can retry later
}
```

## Monitoring Circuit Breakers

### Get Status

```php
// Razorpay status
$razorpay = app(ResilientRazorpayService::class);
$status = $razorpay->getCircuitStatus();

// Returns:
[
    'orders' => [
        'service' => 'razorpay-orders',
        'state' => 'closed',
        'failure_count' => 0,
        'success_count' => 0,
        'half_open_attempts' => 0,
        'thresholds' => [
            'failure' => 5,
            'success' => 2,
            'timeout' => 120,
        ],
    ],
    'plans' => [...],
    'subscriptions' => [...],
    // ...
]

// SMS status
$sms = app(ResilientSmsService::class);
$smsStatus = $sms->getCircuitStatus();

// Email status
$email = app(ResilientEmailService::class);
$emailStatus = $email->getCircuitStatus();
```

### Reset Circuit Breakers (Admin Only)

```php
// Reset all Razorpay circuits
$razorpay->resetAllCircuits();

// Reset SMS circuit
$sms->resetCircuit();

// Reset email circuit
$email->resetCircuit();
```

### Admin Dashboard Endpoint

**Endpoint:** `GET /api/admin/circuit-breakers`

**Response:**
```json
{
    "razorpay": {
        "orders": {
            "service": "razorpay-orders",
            "state": "closed",
            "failure_count": 0,
            "success_count": 0
        }
    },
    "sms": {
        "service": "sms-gateway",
        "state": "open",
        "failure_count": 5,
        "success_count": 0
    },
    "email": {
        "service": "email-service",
        "state": "closed",
        "failure_count": 0,
        "success_count": 0
    }
}
```

## Best Practices

### 1. Choose Appropriate Thresholds

| Service Type | Failure Threshold | Timeout | Rationale |
|--------------|------------------|---------|-----------|
| Payment Gateway | 5 | 120s | Critical but can be slow |
| SMS Gateway | 3 | 300s | Non-critical, often flaky |
| Email Service | 5 | 180s | Non-critical, generally reliable |
| External APIs | 5 | 60s | Standard configuration |

### 2. Provide Meaningful Fallbacks

**Good Fallbacks:**
```php
// For non-critical operations
$fallback = null; // Logged and handled gracefully

// For user-facing features
$fallback = function() {
    return [
        'status' => 'unavailable',
        'message' => 'Service temporarily unavailable',
    ];
};
```

**Bad Fallbacks:**
```php
// Don't silently fail critical operations
$order = $razorpay->createOrder($amount, $receipt);
// If null, MUST handle it - don't assume success

// Don't use fallback for refunds
$razorpay->refundPayment($paymentId); // Should throw exception
```

### 3. Log Circuit State Changes

All state transitions are automatically logged:

```
[info] Circuit breaker OPENED for razorpay-orders after 5 failures
[info] Circuit breaker transitioned to HALF_OPEN for razorpay-orders
[info] Circuit breaker CLOSED for razorpay-orders after 2 successful attempts
```

### 4. Monitor Circuit Breaker Metrics

**Recommended Alerts:**
- Circuit opened → Send alert to ops team
- Circuit stays open > 15 minutes → Escalate
- Multiple circuits open → System-wide issue

**Metrics to Track:**
- Circuit state (closed/open/half-open)
- Failure count
- Time in open state
- Total requests blocked

## Common Scenarios

### Scenario 1: Razorpay is Down

```php
// Circuit opens after 5 failed order creations
$order = $razorpay->createOrder(1000, 'ORDER123');
// Returns: null

// Application response
if ($order === null) {
    return response()->json([
        'error' => 'Payment service temporarily unavailable. Please try again later.',
    ], 503);
}
```

### Scenario 2: SMS Gateway is Slow

```php
// Circuit opens after 3 timeouts
$log = $sms->send($user, $message, 'otp.login');
// Returns: null

// Application response
if ($log === null) {
    // SMS is non-critical - continue without it
    Log::warning("OTP SMS not sent to {$user->mobile} - circuit open");
    // Could use alternative method (email OTP, etc.)
}
```

### Scenario 3: Recovery After Downtime

```
T+0s: Razorpay goes down
T+30s: Circuit opens after 5 failures
T+90s: Timeout expires (120s from last failure)
T+91s: Circuit transitions to HALF_OPEN
T+92s: First request succeeds
T+93s: Second request succeeds
T+94s: Circuit closes, normal operation resumes
```

## Testing Circuit Breakers

### Unit Tests

```php
use App\Services\CircuitBreakerService;

public function test_circuit_opens_after_threshold_failures()
{
    $breaker = new CircuitBreakerService('test-service', 3, 2, 60);

    // Trigger 3 failures
    for ($i = 0; $i < 3; $i++) {
        try {
            $breaker->call(fn() => throw new \Exception('Test'));
        } catch (\Exception $e) {}
    }

    $status = $breaker->getStatus();
    $this->assertEquals('open', $status['state']);
}
```

### Integration Tests

```php
public function test_razorpay_circuit_breaker_protects_order_creation()
{
    // Mock Razorpay SDK to fail
    $this->mockRazorpayToFail();

    $razorpay = app(ResilientRazorpayService::class);

    // Should open circuit after 5 failures
    for ($i = 0; $i < 5; $i++) {
        $order = $razorpay->createOrder(1000, "TEST{$i}");
    }

    // 6th call should fail fast without calling Razorpay
    $order = $razorpay->createOrder(1000, 'TEST6');
    $this->assertNull($order);
}
```

## Troubleshooting

### Circuit Won't Close

**Symptoms:** Circuit stays in HALF_OPEN or reopens immediately

**Possible Causes:**
1. External service still returning errors
2. Success threshold too high
3. Timeout too short (service needs more time to recover)

**Solutions:**
- Manually reset circuit: `$service->resetCircuit()`
- Increase timeout in configuration
- Check external service status

### False Positives

**Symptoms:** Circuit opens when service is actually available

**Possible Causes:**
1. Failure threshold too low
2. Network connectivity issues
3. Temporary spikes in latency

**Solutions:**
- Increase failure threshold
- Add retry logic before circuit breaker
- Monitor network quality

### Circuit Breaker Overhead

**Impact:** Minimal - Redis cache lookups add ~1-2ms per request

**Optimization:**
- Use in-memory cache for high-frequency operations
- Batch circuit breaker checks where possible

## Migration Guide

### Replacing Existing Service Calls

**Before:**
```php
use App\Services\RazorpayService;

$razorpay = app(RazorpayService::class);
$order = $razorpay->createOrder($amount, $receipt);
```

**After:**
```php
use App\Services\ResilientRazorpayService;

$razorpay = app(ResilientRazorpayService::class);
$order = $razorpay->createOrder($amount, $receipt);

if ($order === null) {
    // Handle circuit open scenario
}
```

### Service Container Binding

**In `app/Providers/AppServiceProvider.php`:**
```php
public function register()
{
    // Bind resilient services
    $this->app->singleton(ResilientRazorpayService::class);
    $this->app->singleton(ResilientSmsService::class);
    $this->app->singleton(ResilientEmailService::class);
}
```

## Further Reading

- [Circuit Breaker Pattern - Martin Fowler](https://martinfowler.com/bliki/CircuitBreaker.html)
- [Release It! - Michael Nygard](https://pragprog.com/titles/mnee2/release-it-second-edition/)
- [Hystrix (Netflix) Circuit Breaker](https://github.com/Netflix/Hystrix/wiki/How-it-Works)
