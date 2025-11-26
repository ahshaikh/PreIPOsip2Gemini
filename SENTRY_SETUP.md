# Sentry Error Tracking Setup Guide

This document provides instructions for integrating Sentry error tracking into the PreIPO SIP application.

## Overview

Sentry is a real-time error tracking system that helps monitor and fix crashes in production. This guide covers setup for both frontend (Next.js) and backend (Laravel) components.

## Frontend Setup (Next.js)

### 1. Install Sentry SDK

```bash
cd frontend
npm install --save @sentry/nextjs
```

### 2. Initialize Sentry

Run the Sentry wizard:
```bash
npx @sentry/wizard@latest -i nextjs
```

This will:
- Create `sentry.client.config.ts`
- Create `sentry.server.config.ts`
- Create `sentry.edge.config.ts`
- Update `next.config.js`
- Create `.sentryclirc` (add to .gitignore!)

### 3. Environment Variables

Add to `frontend/.env.local`:
```env
NEXT_PUBLIC_SENTRY_DSN=your_sentry_dsn_here
SENTRY_ORG=your-org
SENTRY_PROJECT=preipo-sip-frontend
SENTRY_AUTH_TOKEN=your_auth_token

# Optional: Environment
NEXT_PUBLIC_APP_ENV=production
```

### 4. Update Error Boundaries

Update `frontend/app/error.tsx`:
```typescript
'use client'

import { useEffect } from 'react'
import * as Sentry from '@sentry/nextjs'

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Log error to Sentry
    Sentry.captureException(error)
  }, [error])

  return (
    <div className="flex flex-col items-center justify-center min-h-screen">
      <h2>Something went wrong!</h2>
      <button onClick={() => reset()}>Try again</button>
    </div>
  )
}
```

Update `frontend/app/global-error.tsx`:
```typescript
'use client'

import * as Sentry from '@sentry/nextjs'
import { useEffect } from 'react'

export default function GlobalError({
  error,
}: {
  error: Error & { digest?: string }
}) {
  useEffect(() => {
    Sentry.captureException(error)
  }, [error])

  return (
    <html>
      <body>
        <h2>Something went wrong!</h2>
      </body>
    </html>
  )
}
```

### 5. Update API Error Handling

Update `frontend/lib/api.ts` to log production errors:
```typescript
// Add at the top
import * as Sentry from '@sentry/nextjs'

// In error handling
if (process.env.NODE_ENV === 'production') {
  Sentry.captureException(error, {
    tags: {
      api_endpoint: config.url,
      method: config.method,
    },
    extra: {
      response: error.response?.data,
      status: error.response?.status,
    },
  })
}
```

### 6. Source Maps

Ensure source maps are uploaded to Sentry by adding to `next.config.js`:
```javascript
const { withSentryConfig } = require('@sentry/nextjs')

module.exports = withSentryConfig(
  {
    // Your existing Next.js config
  },
  {
    // Sentry webpack plugin options
    silent: true,
    org: process.env.SENTRY_ORG,
    project: process.env.SENTRY_PROJECT,
  },
  {
    // Sentry SDK options
    widenClientFileUpload: true,
    tunnelRoute: '/monitoring',
    hideSourceMaps: true,
    disableLogger: true,
  }
)
```

## Backend Setup (Laravel)

### 1. Install Sentry SDK

```bash
cd backend
composer require sentry/sentry-laravel
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Sentry\\Laravel\\ServiceProvider"
```

### 3. Environment Variables

Add to `backend/.env`:
```env
SENTRY_LARAVEL_DSN=your_sentry_dsn_here
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_ENVIRONMENT=production
```

### 4. Update Exception Handler

The Sentry Laravel package automatically integrates with Laravel's exception handler. No additional code needed!

### 5. Add User Context

Update `backend/app/Http/Middleware/Authenticate.php` or create a new middleware:
```php
use Sentry\State\Scope;
use function Sentry\configureScope;

configureScope(function (Scope $scope) use ($user): void {
    $scope->setUser([
        'id' => $user->id,
        'email' => $user->email,
        'username' => $user->username,
    ]);
});
```

### 6. Performance Monitoring (Optional)

Add to routes you want to monitor:
```php
use Sentry\Tracing\TransactionContext;

$transactionContext = new TransactionContext();
$transactionContext->setName('GET /api/v1/products');
$transactionContext->setOp('http.server');

$transaction = \Sentry\startTransaction($transactionContext);

// Your route logic

$transaction->finish();
```

## Best Practices

### 1. Error Filtering

Configure to ignore common errors in `sentry.client.config.ts`:
```typescript
Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  ignoreErrors: [
    'ResizeObserver loop limit exceeded',
    'Non-Error promise rejection captured',
    'Network request failed',
  ],
  beforeSend(event, hint) {
    // Filter out 404s and other non-critical errors
    if (event.exception) {
      const error = hint.originalException
      if (error && error.response?.status === 404) {
        return null
      }
    }
    return event
  },
})
```

### 2. Release Tracking

Set release version in both frontend and backend:

Frontend `sentry.client.config.ts`:
```typescript
Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  release: process.env.NEXT_PUBLIC_SENTRY_RELEASE || 'development',
})
```

Backend `.env`:
```env
SENTRY_RELEASE=1.0.0
```

### 3. Environment Tagging

Always set environment to differentiate between staging and production:
```env
# Production
SENTRY_ENVIRONMENT=production

# Staging
SENTRY_ENVIRONMENT=staging

# Development
SENTRY_ENVIRONMENT=development
```

### 4. User Privacy

Never log sensitive data:
```typescript
Sentry.init({
  beforeSend(event) {
    // Remove sensitive data
    if (event.request) {
      delete event.request.cookies
      delete event.request.headers?.Authorization
    }
    return event
  },
})
```

## Testing

### Frontend Test
```javascript
// Test error in browser console
throw new Error('Test Sentry Error from Frontend')
```

### Backend Test
```bash
php artisan sentry:test
```

## Monitoring Checklist

- [ ] Install Sentry SDK (frontend & backend)
- [ ] Configure environment variables
- [ ] Update error boundaries
- [ ] Test error reporting
- [ ] Configure release tracking
- [ ] Set up alerts in Sentry dashboard
- [ ] Add team members to Sentry project
- [ ] Configure error filtering rules
- [ ] Enable source maps upload (frontend)
- [ ] Test in staging environment

## Resources

- [Sentry Next.js Documentation](https://docs.sentry.io/platforms/javascript/guides/nextjs/)
- [Sentry Laravel Documentation](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Sentry Best Practices](https://docs.sentry.io/product/sentry-basics/integrate-frontend/best-practices/)

## Notes

- The TODO comments in `error.tsx`, `global-error.tsx`, and `api.ts` should be removed after Sentry is implemented
- Sentry free tier includes 5k errors/month and 10k performance units
- Consider upgrading to Team plan ($26/month) for production use
- Set up alerts for high error rates in Sentry dashboard
