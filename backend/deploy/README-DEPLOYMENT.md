# Deployment Configuration

## Redis Queue Workers

This application uses Redis for caching and queue management. Supervisor is used to manage queue worker processes.

### Prerequisites

1. **Install Redis**:
   ```bash
   sudo apt-get update
   sudo apt-get install redis-server
   sudo systemctl enable redis-server
   sudo systemctl start redis-server
   ```

2. **Install Supervisor**:
   ```bash
   sudo apt-get install supervisor
   sudo systemctl enable supervisor
   sudo systemctl start supervisor
   ```

3. **Configure Redis** (optional tuning):
   ```bash
   sudo nano /etc/redis/redis.conf
   # Set maxmemory and eviction policy
   maxmemory 256mb
   maxmemory-policy allkeys-lru
   ```

### Setup Instructions

1. **Copy Supervisor Configuration**:
   ```bash
   sudo cp deploy/supervisor-queue-worker.conf /etc/supervisor/conf.d/
   sudo cp deploy/supervisor-webhook-worker.conf /etc/supervisor/conf.d/
   ```

2. **Update Configuration Paths**:
   - Edit the configuration files to match your deployment path
   - Default path is `/var/www/preiposip/backend`
   - Update `user` if not using `www-data`

3. **Reload Supervisor**:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start all
   ```

4. **Check Worker Status**:
   ```bash
   sudo supervisorctl status
   ```

### Queue Configuration

The application uses the following queue structure:

- **high**: High-priority jobs (payment processing, webhooks)
- **default**: Standard jobs (emails, notifications)
- **webhooks**: Webhook retry processing (5 attempts with exponential backoff)

### Monitoring

**View Worker Logs**:
```bash
# Queue workers
tail -f /var/www/preiposip/backend/storage/logs/queue-worker.log

# Webhook workers
tail -f /var/www/preiposip/backend/storage/logs/webhook-worker.log

# Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log
```

**Manage Workers**:
```bash
# Restart all workers
sudo supervisorctl restart all

# Restart specific worker
sudo supervisorctl restart preiposip-queue-worker:*

# Stop workers
sudo supervisorctl stop all

# Start workers
sudo supervisorctl start all
```

### Performance Tuning

**Adjust Worker Count**:
- Edit `numprocs` in supervisor config
- Recommended: 2-4 workers per CPU core
- High traffic: Increase webhook workers

**Memory Limits**:
- Default: 512MB per worker
- Adjust `--memory` flag based on job complexity
- Monitor with: `ps aux | grep queue:work`

**Timeout Settings**:
- `--max-time=3600`: Workers restart after 1 hour
- `--tries=3`: Default retry attempts
- `--tries=5`: Webhook retry attempts
- `--backoff=10,30,60`: Exponential backoff (10s, 30s, 60s)

### Troubleshooting

**Workers Not Starting**:
```bash
# Check supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check configuration syntax
sudo supervisorctl reread

# Force reload
sudo supervisorctl reload
```

**Redis Connection Issues**:
```bash
# Test Redis connection
redis-cli ping

# Check Laravel Redis connection
php artisan tinker
>>> Redis::connection()->ping();

# View Redis info
redis-cli info
```

**Failed Jobs**:
```bash
# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>

# Clear failed jobs
php artisan queue:flush
```

### Health Checks

Add to your monitoring/cron:

```bash
# Check queue health
php artisan queue:monitor redis:default,redis:high,redis:webhooks --max=100

# Clear old failed jobs (weekly)
php artisan queue:prune-failed --hours=168
```

### Environment Variables

Ensure these are set in `.env`:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
```

### Scaling

**Horizontal Scaling**:
- Run workers on multiple servers
- All connect to same Redis instance
- Use Redis Sentinel or Cluster for HA

**Vertical Scaling**:
- Increase `numprocs` in supervisor config
- Add more memory to Redis
- Use dedicated Redis server

### Security

1. **Firewall Redis**:
   ```bash
   sudo ufw allow from <app-server-ip> to any port 6379
   ```

2. **Set Redis Password**:
   ```bash
   # In redis.conf
   requirepass your-strong-password

   # In .env
   REDIS_PASSWORD=your-strong-password
   ```

3. **Disable Dangerous Commands**:
   ```bash
   # In redis.conf
   rename-command FLUSHDB ""
   rename-command FLUSHALL ""
   rename-command CONFIG ""
   ```
