import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Get the base URL for Laravel storage files
 * Removes /api/v1 suffix from API_URL to get storage base
 */
export function getStorageUrl(path: string): string {
  // In Next.js, env vars might be undefined if not set at build time
  // Use a more robust approach to get the API base URL
  const apiUrl = typeof process !== 'undefined' && process.env.NEXT_PUBLIC_API_URL
    ? process.env.NEXT_PUBLIC_API_URL
    : 'http://localhost:8000/api/v1/';

  // Remove /api/v1/ suffix to get base URL
  const baseUrl = apiUrl.replace(/\/api\/v1\/?$/, '');

  // Ensure path doesn't start with /
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;

  return `${baseUrl}/storage/${cleanPath}`;
}
