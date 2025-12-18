import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

/**
 * Next.js Auth Middleware
 * * Intercepts requests to dashboard, admin, and user profile routes
 * to verify authentication server-side using the auth_token cookie.
 */
export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  
  // 1. Get token from cookies (HttpOnly cookies are accessible to Middleware)
  const token = request.cookies.get('auth_token')?.value;

  // 2. Define protected route patterns
  const isProtectedRoute = 
    pathname.startsWith('/dashboard') || 
    pathname.startsWith('/portfolio') || 
    pathname.startsWith('/wallet') || 
    pathname.startsWith('/kyc') ||
    pathname.startsWith('/admin') ||
    pathname.startsWith('/Profile');

  const isAuthRoute = 
    pathname.startsWith('/login') || 
    pathname.startsWith('/signup');

  // 3. Logic: Redirect unauthenticated users to login
  if (isProtectedRoute && !token) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('callbackUrl', pathname);
    return NextResponse.redirect(loginUrl);
  }

  // 4. Logic: Redirect logged-in users away from Login/Signup
  if (isAuthRoute && token) {
    return NextResponse.redirect(new URL('/dashboard', request.url));
  }

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