// V-ENHANCED-ERROR-HANDLING - API Utilities

import { AxiosError } from 'axios';

/**
 * Extract error message from API error response
 * Useful for components that want custom error handling
 */
export function getErrorMessage(error: unknown): string {
  if (!error) return 'An unexpected error occurred';

  const axiosError = error as AxiosError<any>;
  const data = axiosError.response?.data;

  // Try different message locations
  if (data?.message) return data.message;
  if (data?.error) return data.error;
  if (typeof data === 'string') return data;
  if (axiosError.message) return axiosError.message;

  return 'An unexpected error occurred';
}

/**
 * Extract validation errors from 422 response
 * Returns an object with field names as keys and error messages as values
 */
export function getValidationErrors(error: unknown): Record<string, string> {
  const axiosError = error as AxiosError<any>;
  const data = axiosError.response?.data;

  if (axiosError.response?.status !== 422 || !data?.errors) {
    return {};
  }

  const errors: Record<string, string> = {};

  Object.keys(data.errors).forEach((field) => {
    const fieldErrors = data.errors[field];
    // Take the first error message for each field
    errors[field] = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;
  });

  return errors;
}

/**
 * Check if error is a specific status code
 */
export function isErrorStatus(error: unknown, status: number): boolean {
  const axiosError = error as AxiosError;
  return axiosError.response?.status === status;
}

/**
 * Check if error is a network error (no response from server)
 */
export function isNetworkError(error: unknown): boolean {
  const axiosError = error as AxiosError;
  return !axiosError.response && Boolean(axiosError.request);
}

/**
 * Config option to suppress global error toast
 * Use this in API calls when you want to handle errors manually
 *
 * Example:
 * try {
 *   await api.post('/endpoint', data, suppressErrorToast())
 * } catch (error) {
 *   // Handle error manually
 * }
 */
export function suppressErrorToast() {
  return {
    validateStatus: () => true, // Don't throw on any status
  };
}

/**
 * Format API error for logging/debugging
 */
export function formatErrorForLogging(error: unknown) {
  const axiosError = error as AxiosError<any>;

  return {
    message: getErrorMessage(error),
    status: axiosError.response?.status,
    statusText: axiosError.response?.statusText,
    url: axiosError.config?.url,
    method: axiosError.config?.method,
    data: axiosError.response?.data,
    timestamp: new Date().toISOString(),
  };
}
