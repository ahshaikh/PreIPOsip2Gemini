/**
 * Storage URL Helper
 *
 * Transforms backend storage URLs to use the Next.js storage proxy.
 * This ensures images are served through Next.js for proper caching,
 * optimization, and CORS handling.
 *
 * Backend returns: http://localhost:8000/storage/company-logos/file.jpg
 * Frontend needs: /api/storage/company-logos/file.jpg
 */

/**
 * Transform a backend storage URL to use the Next.js storage proxy
 *
 * @param url - The storage URL from the backend (can be absolute or relative)
 * @returns Proxied URL path for Next.js
 */
export function transformStorageUrl(url: string | undefined | null): string | undefined {
  if (!url) return undefined;

  // If it's already a proxied URL, return as-is
  if (url.startsWith('/api/storage/')) {
    return url;
  }

  // Extract the storage path from absolute URLs
  // Handles: http://localhost:8000/storage/... or https://api.example.com/storage/...
  const storageMatch = url.match(/\/storage\/(.+)$/);
  if (storageMatch) {
    return `/api/storage/${storageMatch[1]}`;
  }

  // If it's already a relative path starting with /storage/
  if (url.startsWith('/storage/')) {
    return `/api${url}`;
  }

  // If none of the patterns match, return the original URL
  // (might be an external URL or data URI)
  return url;
}

/**
 * Batch transform multiple storage URLs
 *
 * @param urls - Array of storage URLs
 * @returns Array of proxied URLs
 */
export function transformStorageUrls(urls: (string | undefined | null)[]): (string | undefined)[] {
  return urls.map(transformStorageUrl);
}

/**
 * Transform storage URLs in an object (useful for company data)
 *
 * @param obj - Object containing logo_url or similar fields
 * @returns Object with transformed URLs
 */
export function transformObjectStorageUrls<T extends Record<string, any>>(obj: T): T {
  if (!obj) return obj;

  const transformed = { ...obj };

  // Transform common image field names
  const imageFields = ['logo_url', 'logo', 'image_url', 'image', 'avatar_url', 'avatar'];

  for (const field of imageFields) {
    if (field in transformed && typeof transformed[field] === 'string') {
      transformed[field] = transformStorageUrl(transformed[field]) || transformed[field];
    }
  }

  return transformed;
}
