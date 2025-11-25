# GitHub Actions Workflows

## 🔗 Broken Link Check Workflow

Automated workflow that tests all routes in the application for broken links, errors, and performance issues.

### 📋 Features

- ✅ **Automatic Triggers**: Runs on every push and pull request
- ✅ **Full Stack Testing**: Tests both Laravel backend and Next.js frontend
- ✅ **3 Test Modes**: PUBLIC, USER (authenticated), and ADMIN routes
- ✅ **Comprehensive Reporting**: JSON, CSV, and screenshots
- ✅ **PR Comments**: Automatic summary posted on pull requests
- ✅ **Artifact Upload**: Reports saved for 14 days
- ✅ **CI/CD Integration**: Fails build on critical errors

### 🎯 What It Tests

| Mode | Routes Tested |
|------|---------------|
| **PUBLIC** | Homepage, login, signup, blog, products, FAQs, etc. |
| **USER** | Dashboard, profile, wallet, portfolio, support, etc. |
| **ADMIN** | Admin dashboard, user management, KYC queue, settings, etc. |

### 🚨 Error Detection

The workflow detects and reports:

- 🔍 **404 Not Found**: Missing pages
- 🔥 **500 Server Errors**: Backend crashes
- 🔄 **Redirect Loops**: Infinite redirects
- 🔐 **Auth Errors**: Session expiration
- ⏱️ **Timeouts**: Slow pages
- ⚛️ **React Errors**: Frontend crashes
- 📊 **Missing Components**: Empty pages, missing navigation

### ⚙️ Configuration

#### Required Secrets

Add these secrets in your GitHub repository settings:

```
Settings → Secrets and variables → Actions → New repository secret
```

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `TEST_USER_EMAIL` | Test user email for USER mode | `testuser@example.com` |
| `TEST_USER_PASSWORD` | Test user password | `password123` |
| `TEST_ADMIN_EMAIL` | Admin email for ADMIN mode | `admin@example.com` |
| `TEST_ADMIN_PASSWORD` | Admin password | `admin123` |

**Note:** If secrets are not set, the workflow uses default credentials (not recommended for production).

#### Environment Variables

You can customize the workflow by modifying these in `.github/workflows/broken-link-check.yml`:

```yaml
env:
  NODE_VERSION: '18.x'          # Node.js version
  PHP_VERSION: '8.2'            # PHP version
  FRONTEND_PORT: 3000           # Next.js port
  BACKEND_PORT: 8000            # Laravel port
```

#### Crawler Configuration

Adjust crawler behavior in the "Run Broken Link Crawler" step:

```yaml
env:
  HEADLESS: true                # Run browser in headless mode
  SCREENSHOTS: true             # Take screenshots on errors
  MAX_RETRIES: 2                # Retry failed tests 2 times
  RETRY_DELAY: 1000             # Wait 1s between retries
  RATE_LIMIT_DELAY: 300         # Wait 300ms between route tests
  FAIL_FAST: false              # Test all routes (don't stop on first error)
  TIMEOUT: 15000                # 15s timeout per route
```

### 📊 Reports

#### Workflow Output

The workflow generates:

1. **Console Log**: Real-time progress in GitHub Actions UI
2. **PR Comment**: Automated summary with metrics and errors
3. **JSON Report**: Complete test results with all details
4. **CSV Report**: Spreadsheet-friendly format
5. **Summary Report**: High-level statistics
6. **Screenshots**: Error page captures (when enabled)

#### Downloading Reports

1. Go to the workflow run in GitHub Actions
2. Scroll to "Artifacts" section at the bottom
3. Download `crawler-reports.zip`
4. Extract and open `crawler-report-*.json` or `*.csv`

#### PR Comment Example

```markdown
## ✅ Broken Link Check Results

**Status:** All Links Valid
**Success Rate:** 98.5% (95/96 passed)

### 📊 Summary

| Metric | Value |
|--------|-------|
| Total Tests | 96 |
| ✅ Successful | 95 |
| ❌ Failed | 1 |
| 🔄 Retried | 2 |
| 🚨 Critical Errors | 0 |

### 🔍 Error Breakdown

| Error Type | Count |
|------------|-------|
| 🔍 404 Not Found | 0 |
| 🔥 500 Server Error | 0 |
| 🔄 Redirect Loops | 0 |
| 🔐 Auth Errors | 0 |
| ⏱️ Timeouts | 1 |
| ⚛️ React Errors | 0 |

### ⚡ Performance

| Metric | Value |
|--------|-------|
| Avg Response Time | 1250ms |
| Min Response Time | 450ms |
| Max Response Time | 3200ms |
| Total Duration | 125s |
```

### 🔧 Workflow Steps

The workflow performs the following steps:

#### 1. Setup (5-7 minutes)
- ✅ Checkout repository
- ✅ Setup Node.js 18.x
- ✅ Setup PHP 8.2 with extensions
- ✅ Install Composer dependencies
- ✅ Install npm dependencies
- ✅ Install Playwright browsers
- ✅ Setup MySQL and Redis services

#### 2. Database (1-2 minutes)
- ✅ Configure Laravel .env
- ✅ Run database migrations
- ✅ Seed test data (optional)

#### 3. Start Services (1-2 minutes)
- ✅ Start Laravel backend on port 8000
- ✅ Build Next.js frontend
- ✅ Start Next.js on port 3000
- ✅ Wait for services to be ready

#### 4. Run Tests (5-15 minutes)
- ✅ Run Playwright crawler
- ✅ Test PUBLIC routes
- ✅ Test USER routes (authenticated)
- ✅ Test ADMIN routes (authenticated)
- ✅ Generate reports

#### 5. Report & Cleanup (1-2 minutes)
- ✅ Parse test results
- ✅ Upload artifacts
- ✅ Post PR comment
- ✅ Fail if critical errors found
- ✅ Stop services

**Total Duration:** ~15-30 minutes depending on number of routes

### 🚀 Triggering the Workflow

#### Automatic Triggers

The workflow runs automatically on:

- **Push** to `main`, `develop`, or `staging` branches
- **Pull Request** to `main`, `develop`, or `staging` branches

#### Manual Trigger

You can also trigger the workflow manually:

1. Go to **Actions** tab in GitHub
2. Select **Broken Link Check** workflow
3. Click **Run workflow** button
4. Select branch and click **Run workflow**

### 🛑 Workflow Failure Criteria

The workflow **fails** if:

- ✅ Any **404 Not Found** errors
- ✅ Any **500 Server Errors**
- ✅ Any **Redirect Loops**
- ✅ Any **Auth Errors** (session expiration)
- ❌ Timeouts (non-critical, just reported)
- ❌ React Errors (non-critical, just reported)
- ❌ Missing components (non-critical, just reported)

**Critical Error Threshold:** > 0 critical errors

### 🐛 Troubleshooting

#### Workflow Fails at "Setup" Stage

**Issue:** Dependencies not installing

**Solutions:**
```yaml
# Check Node version compatibility
- Update NODE_VERSION in workflow

# Check PHP extensions
- Verify all required extensions are listed

# Clear cache
- Delete .github cache in repository settings
```

#### Services Not Starting

**Issue:** Backend/Frontend timeout errors

**Solutions:**
```yaml
# Increase wait time in workflow:
for i in {1..60}; do  # Increased from 30

# Check service logs:
- Download service-logs artifact
- Review /tmp/backend.log and /tmp/frontend.log
```

#### Database Connection Errors

**Issue:** Laravel can't connect to MySQL

**Solutions:**
```yaml
# Verify MySQL service configuration:
services:
  mysql:
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s

# Check .env configuration in workflow
```

#### Authentication Fails

**Issue:** USER or ADMIN tests skipped

**Solutions:**
```yaml
# Verify secrets are set:
- Go to Settings → Secrets → Actions
- Add TEST_USER_EMAIL, TEST_USER_PASSWORD
- Add TEST_ADMIN_EMAIL, TEST_ADMIN_PASSWORD

# Or update crawler to use seeded users:
USER_EMAIL: 'admin@example.com'  # From seed data
```

#### Crawler Times Out

**Issue:** Individual route tests timeout

**Solutions:**
```yaml
# Increase timeout in workflow:
env:
  TIMEOUT: 30000  # Increased from 15000

# Or reduce routes tested:
- Modify crawler to skip certain routes
- Use FAIL_FAST=true for quick feedback
```

### 📈 Performance Optimization

#### Reduce Workflow Time

```yaml
# 1. Skip screenshots (saves ~30%)
SCREENSHOTS: false

# 2. Reduce retries (saves ~20%)
MAX_RETRIES: 1

# 3. Fail fast (saves time on errors)
FAIL_FAST: true

# 4. Test fewer routes
- Modify crawler to skip non-critical routes

# 5. Use matrix strategy for parallel testing
strategy:
  matrix:
    mode: [PUBLIC, USER, ADMIN]
```

#### Reduce Costs

```yaml
# 1. Run only on specific branches
on:
  push:
    branches: [main]  # Only main branch

# 2. Run on schedule instead of every push
on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2am

# 3. Skip on draft PRs
if: github.event.pull_request.draft == false
```

### 🔐 Security Best Practices

1. **Use Secrets**: Never hardcode credentials in workflow
2. **Limit Permissions**: Use minimal GitHub token permissions
3. **Rotate Credentials**: Change test user passwords regularly
4. **Separate Environment**: Use test database, not production
5. **Review Logs**: Check for exposed secrets in logs

### 📚 Additional Resources

- [Playwright Documentation](https://playwright.dev/docs/intro)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Next.js Testing](https://nextjs.org/docs/testing)

### 🤝 Contributing

To improve the workflow:

1. Test locally first using the crawler
2. Update workflow YAML
3. Create a pull request
4. Verify workflow runs successfully
5. Update this README if needed

### 📝 Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-25 | Initial release with full features |

---

**Questions or Issues?** Open an issue in the repository.
