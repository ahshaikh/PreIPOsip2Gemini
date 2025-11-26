# Performance and Monitoring Features

This document describes the performance monitoring, caching, queue management, and webhook retry features implemented in the Pre-IPO SIP application.

## Table of Contents

1. [Social Login Referral Integration](#social-login-referral-integration)
2. [Redis Cache and Queue Configuration](#redis-cache-and-queue-configuration)
3. [Database Query Performance Monitoring](#database-query-performance-monitoring)
4. [Webhook Retry System](#webhook-retry-system)
5. [Load Testing Suite](#load-testing-suite)
6. [Performance Monitoring Dashboard](#performance-monitoring-dashboard)

---

## Social Login Referral Integration

### Overview
Referral codes are now fully integrated into the Google OAuth social login flow, allowing users to earn referral bonuses even when signing up via Google.

### Implementation

**Files Modified:**
- `app/Http/Controllers/Api/SocialLoginController.php`

**How It Works:**
1. When redirecting to Google OAuth, the referral code is encoded in the OAuth `state` parameter
2. Upon callback, the state is decoded and the referral code is extracted
3. If a new user is created, the referral code is validated and processed
4. A `Referral` record is created with `pending` status
5. The referral will be completed when the referred user makes their first payment

**Usage:**

Frontend implementation:
```javascript
// When user clicks "Sign in with Google" from a referral link
const referralCode = new URLSearchParams(window.location.search).get('ref');

// Include referral code in the OAuth initiation
const response = await fetch(`/api/v1/auth/google?referral_code=${referralCode}`);
const { redirect_url } = await response.json();

// Redirect user to Google OAuth
window.location.href = redirect_url;
```

**Security Features:**
- Self-referral prevention
- Invalid referral code graceful handling
- Comprehensive logging for debugging

---

## Redis Cache and Queue Configuration

### Overview
Redis is now configured for high-performance caching and queue management, replacing file-based cache and database queues.

### Configuration Files

**Environment Variables (.env):**
```env
CACHE_STORE=redis
CACHE_PREFIX=preiposip_cache
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
```

**Database Configuration:**
- `config/database.php` - Separate Redis databases for default, cache, and queue connections

**Queue Configuration:**
- `config/queue.php` - Redis queue driver with exponential backoff

### Queue Structure

Three main queues are configured:

1. **default** - Standard jobs (emails, notifications)
2. **high** - High-priority jobs (payment processing)
3. **webhooks** - Webhook retry processing with 5 attempts

### Supervisor Configuration

**Files:**
- `supervisord.conf` - Development configuration
- `deploy/supervisor-queue-worker.conf` - Production queue workers
- `deploy/supervisor-webhook-worker.conf` - Production webhook workers
- `deploy/README-DEPLOYMENT.md` - Deployment documentation

**Setup:**
```bash
# Copy supervisor configs
sudo cp deploy/supervisor-queue-worker.conf /etc/supervisor/conf.d/
sudo cp deploy/supervisor-webhook-worker.conf /etc/supervisor/conf.d/

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# Check status
sudo supervisorctl status
```

**Managing Workers:**
```bash
# Restart workers after code deploy
sudo supervisorctl restart all

# View logs
tail -f storage/logs/queue-worker.log
tail -f storage/logs/webhook-worker.log
```

---

## Database Query Performance Monitoring

### Overview
Comprehensive database query monitoring to detect slow queries and N+1 query issues.

### Implementation

**Files:**
- `app/Providers/DatabaseQueryMonitoringServiceProvider.php`
- `app/Http/Middleware/TrackDatabaseQueries.php`
- `config/database.php` - Added `query_monitoring` configuration
- `config/logging.php` - Added `performance` log channel

**Environment Variables:**
```env
DB_QUERY_MONITORING_ENABLED=false  # Set to true to enable
DB_SLOW_QUERY_THRESHOLD=1000       # milliseconds
DB_N_PLUS_ONE_THRESHOLD=10         # number of similar queries
```

### Features

1. **Slow Query Detection**
   - Logs queries exceeding the threshold
   - Stores metrics in Redis for dashboard
   - Includes stack trace for debugging

2. **N+1 Query Detection**
   - Detects when the same query pattern is executed multiple times
   - Alerts when threshold is exceeded
   - Helps identify missing eager loading

3. **Request Performance Tracking**
   - Total query count per request
   - Total query time per request
   - Memory usage tracking
   - Debug headers in development mode

### Usage

**Enable in Development:**
```env
DB_QUERY_MONITORING_ENABLED=true
APP_DEBUG=true
```

**View Performance Logs:**
```bash
tail -f storage/logs/performance.log
```

**Debug Headers (in development):**
Response includes:
- `X-Database-Queries`: Total number of queries
- `X-Database-Time`: Total query time
- `X-Request-Time`: Total request processing time

---

## Webhook Retry System

### Overview
Comprehensive webhook processing with automatic retry, exponential backoff, and detailed logging.

### Implementation

**Files:**
- `database/migrations/2025_11_26_000001_create_webhook_logs_table.php`
- `app/Models/WebhookLog.php`
- `app/Jobs/ProcessWebhookRetryJob.php`
- `app/Console/Commands/RetryFailedWebhooks.php`
- `app/Console/Commands/ProcessPendingWebhooks.php`
- `app/Http/Controllers/Api/WebhookController.php` (updated)

### Features

1. **Automatic Logging**
   - All incoming webhooks are logged to `webhook_logs` table
   - Stores payload, headers, and processing status

2. **Exponential Backoff**
   - Retry 0: 1 minute
   - Retry 1: 2 minutes
   - Retry 2: 4 minutes
   - Retry 3: 8 minutes
   - Retry 4: 16 minutes
   - Maximum: 60 minutes

3. **Status Tracking**
   - `pending`: Waiting for retry
   - `processing`: Currently being processed
   - `success`: Successfully processed
   - `failed`: Failed but retriable
   - `max_retries_reached`: Exhausted all retries

4. **Automatic Cleanup**
   - Webhook logs older than 30 days are automatically pruned
   - Scheduled weekly via Laravel scheduler

### Commands

**Process Pending Webhooks:**
```bash
php artisan webhooks:process-pending
```

**Retry Failed Webhooks:**
```bash
# Retry all failed webhooks
php artisan webhooks:retry --all

# Retry specific webhook
php artisan webhooks:retry --id=123

# Retry webhooks failed in last 24 hours
php artisan webhooks:retry --hours=24
```

### Scheduled Tasks

Added to `app/Console/Kernel.php`:
```php
// Process pending webhook retries every 5 minutes
$schedule->command(ProcessPendingWebhooks::class)->everyFiveMinutes();

// Prune old webhook logs weekly
$schedule->command('model:prune', ['--model' => 'App\\Models\\WebhookLog'])->weekly();
```

---

## Load Testing Suite

### Overview
Comprehensive load testing tools and configurations using k6 and Artillery.

### Files
- `tests/LoadTesting/k6-load-test.js` - k6 load test script
- `tests/LoadTesting/artillery-config.yml` - Artillery configuration
- `tests/LoadTesting/README.md` - Complete documentation

### Test Scenarios

1. **User Authentication Flow** (30% weight)
   - Login
   - Profile retrieval

2. **Browse Plans** (40% weight)
   - List plans
   - List products

3. **Dashboard Load** (20% weight)
   - User dashboard
   - Investments list

4. **Referral System** (10% weight)
   - Referral dashboard
   - Referral statistics

### Running Tests

**k6 (Recommended):**
```bash
# Basic load test
k6 run tests/LoadTesting/k6-load-test.js

# Custom configuration
k6 run --env BASE_URL=https://your-domain.com tests/LoadTesting/k6-load-test.js

# With results output
k6 run --out json=results.json tests/LoadTesting/k6-load-test.js
```

**Artillery:**
```bash
# Run test
artillery run tests/LoadTesting/artillery-config.yml

# Generate HTML report
artillery run --output report.json tests/LoadTesting/artillery-config.yml
artillery report report.json --output report.html
```

### Performance Thresholds

- **Response Time:**
  - p95 < 2000ms
  - p99 < 5000ms

- **Error Rate:**
  - < 1% HTTP errors
  - < 5% business logic errors

- **Throughput:**
  - Minimum 50 req/s normal load
  - Minimum 100 req/s peak load

---

## Performance Monitoring Dashboard

### Overview
Real-time performance monitoring dashboard with metrics visualization.

### Access

**URL:** `http://your-domain.com/admin/performance`

**Note:** Add authentication middleware in production.

### API Endpoints

**Overview:**
```
GET /api/v1/admin/performance/overview?period=24h
```

**Database Metrics:**
```
GET /api/v1/admin/performance/database?period=24h
```

**Real-time Metrics:**
```
GET /api/v1/admin/performance/realtime
```

### Metrics Displayed

1. **Database**
   - Active connections
   - Slow query count
   - Average query time
   - Table sizes
   - Connection stats

2. **Cache (Redis)**
   - Connection status
   - Hit rate percentage
   - Memory usage
   - Commands processed

3. **Queue**
   - Queue sizes (default, high, webhooks)
   - Failed jobs count
   - Delayed jobs

4. **Webhooks**
   - Total processed
   - Success rate
   - Pending retries
   - Recent failures

5. **System**
   - PHP version
   - Memory usage
   - Peak memory
   - Server uptime

### Features

- **Auto-refresh:** Updates every 30 seconds
- **Period Selection:** 1h, 24h, 7d, 30d
- **Real-time Data:** Live connection and queue metrics
- **Detailed Tables:** Slow queries and webhook failures
- **Visual Design:** Dark theme optimized for monitoring

---

## Testing

### Feature Tests

**Social Login Referral:**
```bash
php artisan test --filter SocialLoginReferralTest
```

**Webhook Retry System:**
```bash
php artisan test --filter WebhookRetrySystemTest
```

### All Tests
```bash
php artisan test
```

---

## Deployment Checklist

### 1. Redis Setup
```bash
# Install Redis
sudo apt-get install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### 2. Update Environment
```bash
# Copy .env.example and update values
cp .env.example .env

# Set Redis as cache and queue driver
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Setup Supervisor
```bash
# Copy configs
sudo cp deploy/supervisor-queue-worker.conf /etc/supervisor/conf.d/
sudo cp deploy/supervisor-webhook-worker.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### 5. Configure Scheduler
```bash
# Add to crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Enable Monitoring (Optional)
```env
DB_QUERY_MONITORING_ENABLED=true
DB_SLOW_QUERY_THRESHOLD=1000
```

### 7. Test Load
```bash
# Run load tests against staging
k6 run --env BASE_URL=https://staging.your-domain.com tests/LoadTesting/k6-load-test.js
```

---

## Monitoring and Maintenance

### Daily Tasks
- Check performance dashboard
- Review slow query logs
- Monitor queue sizes
- Check webhook failure rate

### Weekly Tasks
- Review load test results
- Analyze performance trends
- Check Redis memory usage
- Review failed webhooks

### Monthly Tasks
- Performance optimization based on metrics
- Database index optimization
- Cache strategy review
- Load test against production (off-peak)

---

## Troubleshooting

### High Queue Backlog
```bash
# Check queue size
php artisan queue:monitor redis:default,redis:high,redis:webhooks

# Add more workers via supervisor
sudo nano /etc/supervisor/conf.d/preiposip-queue-worker.conf
# Increase numprocs
sudo supervisorctl reread && sudo supervisorctl update
```

### Redis Connection Issues
```bash
# Test connection
redis-cli ping

# Check Laravel connection
php artisan tinker
>>> Redis::connection()->ping();
```

### Slow Queries
```bash
# View performance log
tail -f storage/logs/performance.log

# Enable query monitoring
DB_QUERY_MONITORING_ENABLED=true
```

### Failed Webhooks
```bash
# List recent failures
php artisan tinker
>>> App\Models\WebhookLog::failed()->latest()->take(10)->get();

# Retry failed webhooks
php artisan webhooks:retry --hours=24
```

---

## Performance Best Practices

1. **Always cache expensive queries**
2. **Use eager loading to prevent N+1 queries**
3. **Monitor slow query log regularly**
4. **Keep queue workers running**
5. **Set up proper Redis memory limits**
6. **Run load tests after major changes**
7. **Enable query monitoring in staging**
8. **Review webhook failures daily**
9. **Keep supervisor configs updated**
10. **Document performance improvements**

---

## Support

For issues or questions:
- Check application logs: `storage/logs/`
- Review performance dashboard
- Check this documentation
- Contact DevOps team
