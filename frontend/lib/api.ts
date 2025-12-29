// V-PHASE4-1730-098 | V-ENHANCED-ERROR-HANDLING | V-FIX-LOGIN-REDIRECT (Simplified to plain localStorage)
//
// CENTRAL API CLIENT
// ------------------
// This file is responsible for:
// 1. Attaching authentication headers
// 2. Centralized error normalization
// 3. Preventing silent error shape corruption
// 4. Ensuring logs are deterministic (never `{}`)
// 5. Providing audit-grade diagnostics without speculation

import axios, { AxiosError, AxiosResponse } from 'axios';
import { toast } from 'sonner';

/**
 * Axios instance configuration
 * ----------------------------
 * - baseURL resolved from env
 * - credentials enabled for sanctum / cookies if required
 * - Accept header enforced
 */
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1/',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
  },
});

/**
 * REQUEST INTERCEPTOR
 * -------------------
 * Purpose:
 * - Attach Bearer token from localStorage
 * - Log request execution deterministically
 *
 * Important invariants:
 * - localStorage is accessed ONLY in browser
 * - headers object is always initialized
 */
api.interceptors.request.use(
  (config) => {
    console.log(
      '[API INTERCEPTOR] Running for:',
      config.method?.toUpperCase(),
      config.url
    );

    // Only access localStorage on the client
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token');

      console.log(
        '[API INTERCEPTOR] Token from localStorage:',
        token ? `${token.substring(0, 20)}...` : 'NONE'
      );

      if (token) {
        // CRITICAL: Axios does not guarantee headers existence
        if (!config.headers) {
          config.headers = {} as any;
        }

        config.headers.Authorization = `Bearer ${token}`;

        console.log(
          '[API INTERCEPTOR] Authorization header set:',
          config.headers.Authorization
        );
      } else {
        console.warn('[API INTERCEPTOR] No token found in localStorage!');
      }
    }

    return config;
  },
  (error) => {
    // Interceptor failures must always propagate real Error objects
    console.error('[API INTERCEPTOR] Request interceptor error:', error);
    return Promise.reject(error);
  }
);

/**
 * RESPONSE INTERCEPTOR
 * -------------------
 * Purpose:
 * - Normalize ALL errors into safe, inspectable shapes
 * - Prevent throwing raw objects / primitives
 * - Handle auth redirects and user feedback
 * - Log server errors without polluting UI
 */
api.interceptors.response.use(
  (response: AxiosResponse) => {
    // Success path: return response untouched
    return response;
  },
  (rawError: unknown) => {
    /**
     * ERROR NORMALIZATION (CRITICAL)
     * ------------------------------
     * Axios / React Query / fetch chains may throw:
     * - AxiosError
     * - Error
     * - string
     * - object
     * - null / undefined (bad upstream code)
     *
     * From this point onward:
     * - We ALWAYS work with a real Error instance
     * - We NEVER throw raw data
     */
    let error: Error;
    let axiosError: AxiosError | null = null;

    if (rawError instanceof AxiosError) {
      axiosError = rawError;
      error = rawError;
    } else if (rawError instanceof Error) {
      error = rawError;
    } else {
      // Absolute fallback: stringify unknown throwables
      error = new Error(
        typeof rawError === 'string'
          ? rawError
          : JSON.stringify(rawError)
      );
    }

    // Client-side only handling
    if (typeof window === 'undefined') {
      return Promise.reject(error);
    }

    const status = axiosError?.response?.status;
    const data = axiosError?.response?.data as any;

    /**
     * MESSAGE RESOLUTION
     * ------------------
     * Extract a user-facing message WITHOUT speculation.
     * Priority order is deterministic.
     */
    let message = 'An unexpected error occurred';

    if (data?.message) {
      message = data.message;
    } else if (data?.error) {
      message = data.error;
    } else if (typeof data === 'string') {
      message = data;
    } else if (error.message) {
      message = error.message;
    }

    /**
     * STATUS-BASED HANDLING
     * --------------------
     * All branches are explicit.
     */
    switch (status) {
      case 401: {
        // Unauthorized - token invalid or expired
        const publicPaths = [
          '/',
          '/login',
          '/signup',
          '/about',
          '/contact',
          '/products',
          '/companies',
          '/plans',
          '/blog',
          '/faq',
          '/help-center',
        ];

        const pathname = window.location.pathname;
        const isPublicPage = publicPaths.some(
          (path) => pathname === path || pathname.startsWith(path + '/')
        );
        const isLoginPage = pathname.includes('/login');

        if (!isLoginPage && !isPublicPage) {
          toast.error('Session Expired', {
            description: 'Please log in again to continue',
          });

          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        } else if (isPublicPage) {
          // Silent cleanup on public pages
          localStorage.removeItem('auth_token');
        }
        break;
      }

      case 403:
        toast.error('Access Denied', {
          description: message,
        });
        break;

      case 404:
        // Intentionally silent
        break;

      case 422:
        if (data?.errors) {
          const firstError = Object.values(data.errors)[0];
          const errorMessage = Array.isArray(firstError)
            ? firstError[0]
            : firstError;

          toast.error('Validation Error', {
            description: String(errorMessage),
          });
        } else {
          toast.error('Validation Error', { description: message });
        }
        break;

      case 429:
        toast.error('Too Many Requests', {
          description: message,
        });
        break;

      case 500:
      case 502:
      case 503:
      case 504: {
        /**
         * SERVER ERROR LOGGING (NON-INTRUSIVE)
         * -----------------------------------
         * This log is guaranteed NOT to be `{}`.
         * All fields have explicit fallbacks.
         */
        
        const errorPayload = {
          url: axiosError?.config?.url ?? null,
          method: axiosError?.config?.method?.toUpperCase() ?? null,
          status: status ?? null,
          message,
          errorName: error.name,
          errorMessage: error.message,
          stack: error.stack?.split('\n').slice(0, 3) ?? [],
          responseData: data ?? null,
        };

        console.error(
          '[API] Server Error:\n' +
          JSON.stringify(errorPayload, null, 2)
        );

        const isCriticalEndpoint =
          axiosError?.config?.url?.includes('/login') ||
          axiosError?.config?.url?.includes('/logout');

        if (isCriticalEndpoint) {
          toast.error('Server Error', {
            description:
              'Something went wrong on our end. Please try again later.',
          });
        }
        break;
      }

      default:
        if (status && status >= 400) {
          toast.error('Error', { description: message });
        }
        break;
    }

    /**
     * PRODUCTION ERROR REPORTING
     * --------------------------
     * Only server-side failures are sent to Sentry.
     */
    if (
      process.env.NODE_ENV === 'production' &&
      status &&
      status >= 500
    ) {
      if (process.env.NEXT_PUBLIC_SENTRY_DSN) {
        import('@sentry/nextjs').then((Sentry) => {
          Sentry.captureException(error, {
            contexts: {
              api: {
                url: axiosError?.config?.url,
                method: axiosError?.config?.method,
                status,
              },
            },
            extra: {
              message,
              data,
            },
          });
        });
      }
    }

    /**
     * NETWORK FAILURE (NO RESPONSE)
     * -----------------------------
     * Happens on DNS failure, CORS, offline mode.
     */
    if (!axiosError?.response) {
      toast.error('Network Error', {
        description:
          'Unable to connect to server. Please check your internet connection.',
      });
    }

    /**
     * IMPORTANT:
     * ----------
     * Always reject with a real Error object.
     * Never throw raw data.
     */
    return Promise.reject(error);
  }
);

export default api;
