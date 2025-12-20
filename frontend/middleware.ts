import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

/**
 * Next.js Auth Middleware
 *
 * DISABLED: This middleware was designed for cookie-based authentication,
 * but the app currently uses localStorage-based token auth.
 *
 * Since middleware runs on the server and can't access localStorage,
 * authentication is now handled client-side in the layout components.
 *
 * If you want to re-enable server-side auth protection, you need to:
 * 1. Use the HttpOnly cookie that the backend already sets
 * 2. Remove localStorage token storage
 * 3. Update all API calls to rely on cookie credentials
 */
export function middleware(request: NextRequest) {
  // Authentication is handled client-side in layout components
  // This middleware is currently a passthrough
  return NextResponse.next();
}

/**
 * Limit middleware to specific paths for performance
 */
export const config = {
  matcher: [
    '/dashboard/:path*',
    '/portfolio/:path*',
    '/wallet/:path*',
    '/kyc/:path*',
    '/admin/:path*',
    '/Profile/:path*',
    '/login',
    '/signup',
  ],
};