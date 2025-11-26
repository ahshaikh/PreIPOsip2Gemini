# Load Testing Suite

This directory contains load testing configurations and scripts for the Pre-IPO SIP application.

## Tools

We support multiple load testing tools:

1. **k6** - Modern load testing tool (recommended)
2. **Artillery** - Modern, powerful load testing toolkit
3. **Apache Bench (ab)** - Simple HTTP benchmarking

## Prerequisites

### Install k6
```bash
# macOS
brew install k6

# Ubuntu/Debian
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6

# Docker
docker pull grafana/k6
```

### Install Artillery
```bash
npm install -g artillery
```

### Apache Bench
```bash
# Ubuntu/Debian
sudo apt-get install apache2-utils

# macOS (pre-installed)
```

## Running Load Tests

### 1. k6 (Recommended)

**Basic Load Test:**
```bash
k6 run tests/LoadTesting/k6-load-test.js
```

**Custom Configuration:**
```bash
# Set base URL
k6 run --env BASE_URL=https://your-domain.com tests/LoadTesting/k6-load-test.js

# Increase virtual users
k6 run --vus 100 --duration 30s tests/LoadTesting/k6-load-test.js

# Output results to InfluxDB
k6 run --out influxdb=http://localhost:8086/k6 tests/LoadTesting/k6-load-test.js
```

**Cloud Testing with k6 Cloud:**
```bash
k6 login cloud --token YOUR_K6_CLOUD_TOKEN
k6 cloud tests/LoadTesting/k6-load-test.js
```

### 2. Artillery

**Run Artillery Load Test:**
```bash
artillery run tests/LoadTesting/artillery-config.yml
```

**Custom Configuration:**
```bash
# Set target URL
artillery run --target https://your-domain.com tests/LoadTesting/artillery-config.yml

# Generate HTML report
artillery run --output report.json tests/LoadTesting/artillery-config.yml
artillery report report.json --output report.html
```

**Quick Test:**
```bash
artillery quick --count 10 --num 100 http://localhost:8000/api/v1/plans
```

### 3. Apache Bench

**Simple Endpoint Test:**
```bash
# 1000 requests, 100 concurrent
ab -n 1000 -c 100 http://localhost:8000/api/v1/plans

# With authentication
ab -n 1000 -c 100 -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/v1/user/profile

# POST request with JSON
ab -n 100 -c 10 -p post-data.json -T application/json http://localhost:8000/api/v1/auth/login
```

## Test Scenarios

### Scenario 1: Authentication Flow
- User login
- Profile retrieval
- Token validation

### Scenario 2: Browse Content
- List plans
- List products
- Public endpoints

### Scenario 3: Dashboard Load
- User dashboard
- Investments list
- Portfolio data

### Scenario 4: Referral System
- Referral dashboard
- Referral statistics
- Referral history

## Performance Thresholds

Our load tests enforce the following thresholds:

- **Response Time:**
  - p95 < 2000ms (95th percentile)
  - p99 < 5000ms (99th percentile)

- **Error Rate:**
  - < 1% HTTP errors
  - < 5% business logic errors

- **Throughput:**
  - Minimum 50 requests/second under normal load
  - Minimum 100 requests/second under peak load

## Interpreting Results

### k6 Output

```
✓ login status is 200
✓ profile status is 200

checks.........................: 100.00% ✓ 2000 ✗ 0
data_received..................: 1.2 MB  40 kB/s
data_sent......................: 240 kB  8 kB/s
http_req_blocked...............: avg=1.2ms   p(95)=3.5ms
http_req_connecting............: avg=1ms     p(95)=2.8ms
http_req_duration..............: avg=145ms   p(95)=285ms p(99)=456ms
http_req_failed................: 0.00%   ✓ 0    ✗ 2000
http_req_receiving.............: avg=0.5ms   p(95)=1.2ms
http_req_sending...............: avg=0.3ms   p(95)=0.8ms
http_req_tls_handshaking.......: avg=0ms     p(95)=0ms
http_req_waiting...............: avg=144ms   p(95)=284ms
http_reqs......................: 2000    66.666667/s
iteration_duration.............: avg=8.5s    p(95)=9.2s
iterations.....................: 100     3.333333/s
```

**Key Metrics:**
- `http_req_duration`: Time from request sent to response received
- `http_req_failed`: Percentage of failed requests
- `http_reqs`: Total requests and rate
- `checks`: Assertion pass/fail rate

### Artillery Output

```
Summary report @ 14:30:45(+0000)
--------------------------------
Scenarios launched:  1000
Scenarios completed: 950
Requests completed:  4750
RPS sent: 79.17
Request latency:
  min: 45.2
  max: 2345.6
  median: 156.8
  p95: 456.2
  p99: 1234.5
Scenario counts:
  User Authentication Flow: 300 (30%)
  Browse Plans: 400 (40%)
  Dashboard Load: 200 (20%)
  Referral Dashboard: 100 (10%)
```

## Monitoring During Load Tests

### Enable Query Monitoring

Enable database query monitoring during load tests:

```bash
# In .env
DB_QUERY_MONITORING_ENABLED=true
```

### Monitor Logs

```bash
# Performance logs
tail -f storage/logs/performance.log

# General logs
tail -f storage/logs/laravel.log

# Queue workers
tail -f storage/logs/queue-worker.log
```

### System Metrics

```bash
# CPU and Memory
htop

# Redis
redis-cli monitor

# MySQL
mysql -u root -p -e "SHOW PROCESSLIST;"
```

## Best Practices

1. **Start Small**: Begin with low load and gradually increase
2. **Warm Up**: Always include a warm-up phase
3. **Monitor**: Watch server metrics during tests
4. **Realistic Data**: Use production-like test data
5. **Network**: Test from external network when possible
6. **Baseline**: Establish baseline metrics before optimization
7. **Incremental**: Test after each optimization
8. **Document**: Record results and configuration

## Common Issues

### High Error Rate
- Check application logs
- Verify database connections
- Check Redis availability
- Increase worker processes

### Slow Response Times
- Enable query monitoring
- Check for N+1 queries
- Review slow query log
- Optimize database indexes
- Increase cache usage

### Database Connection Errors
```env
# Increase connection pool
DB_MAX_CONNECTIONS=150
```

### Queue Backlog
```bash
# Add more workers
sudo supervisorctl restart all
```

## Continuous Load Testing

Add to CI/CD pipeline:

```yaml
# .github/workflows/load-test.yml
name: Load Test

on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: grafana/setup-k6-action@v1
      - run: k6 run tests/LoadTesting/k6-load-test.js
      - uses: actions/upload-artifact@v2
        with:
          name: load-test-results
          path: load-test-results.json
```

## Results Storage

Test results are stored in:
- `load-test-results.json` - k6 results
- `report.json` - Artillery results
- `storage/logs/performance.log` - Application performance logs

## Support

For questions or issues with load testing:
1. Check application logs
2. Review performance monitoring dashboard
3. Consult with DevOps team
4. Open issue in GitHub repository
