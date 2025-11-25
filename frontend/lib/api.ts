// V-PHASE4-1730-098 | V-ENHANCED-ERROR-HANDLING
import axios, { AxiosError, AxiosResponse } from 'axios';
import { toast } from 'sonner';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1/',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
});

// Request interceptor - Add auth token
api.interceptors.request.use(
  (config) => {
    // Only access localStorage on the client side
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token');
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Centralized error handling
api.interceptors.response.use(
  (response: AxiosResponse) => {
    // Success response - just return it
    return response;
  },
  (error: AxiosError<any>) => {
    // Only handle errors on client side
    if (typeof window === 'undefined') {
      return Promise.reject(error);
    }

    const status = error.response?.status;
    const data = error.response?.data;

    // Extract error message with fallbacks
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

    // Handle specific status codes
    switch (status) {
      case 401:
        // Unauthorized - clear token and redirect to login
        if (!window.location.pathname.includes('/login')) {
          toast.error('Session Expired', {
            description: 'Please log in again to continue'
          });
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        break;

      case 403:
        // Forbidden - user doesn't have permission
        toast.error('Access Denied', {
          description: message || 'You do not have permission to perform this action'
        });
        break;

      case 404:
        // Not found - usually don't show toast for this
        // Components can handle it individually if needed
        break;

      case 422:
        // Validation errors - show first validation message
        if (data?.errors) {
          const firstError = Object.values(data.errors)[0];
          const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
          toast.error('Validation Error', {
            description: errorMessage as string
          });
        } else {
          toast.error('Validation Error', { description: message });
        }
        break;

      case 429:
        // Rate limit exceeded
        toast.error('Too Many Requests', {
          description: message || 'Please slow down and try again later'
        });
        break;

      case 500:
      case 502:
      case 503:
      case 504:
        // Server errors
        toast.error('Server Error', {
          description: 'Something went wrong on our end. Please try again later.'
        });
        break;

      default:
        // Generic error for other cases
        if (status && status >= 400) {
          toast.error('Error', { description: message });
        }
        break;
    }

    // Log errors in production (can integrate Sentry, LogRocket, etc.)
    if (process.env.NODE_ENV === 'production' && status && status >= 500) {
      // Example: Sentry.captureException(error);
      console.error('API Error:', {
        url: error.config?.url,
        method: error.config?.method,
        status,
        message,
        data,
      });
    }

    // Network errors (no response from server)
    if (!error.response) {
      toast.error('Network Error', {
        description: 'Unable to connect to server. Please check your internet connection.'
      });
    }

    return Promise.reject(error);
  }
);

export default api;