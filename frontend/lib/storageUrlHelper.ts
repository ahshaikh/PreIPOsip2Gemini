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
export function transformStorageUrl(
  url: string | undefined | null
): string | undefined {
  if (!url) return undefined;

  // Already proxied
  if (url.startsWith('/api/storage/')) {
    return url;
  }

  // Absolute backend URL → extract /storage/ path
  const storageMatch = url.match(/\/storage\/(.+)$/);
  if (storageMatch) {
    return `/api/storage/${storageMatch[1]}`;
  }

  // Relative /storage/ path
  if (url.startsWith('/storage/')) {
    return `/api${url}`;
  }

  // External or unknown format
  return url;
}

/**
 * Batch transform multiple storage URLs
 */
export function transformStorageUrls(
  urls: (string | undefined | null)[]
): (string | undefined)[] {
  return urls.map(transformStorageUrl);
}

/**
 * Known image-related fields that may contain storage URLs
 */
type ImageField =
  | 'logo_url'
  | 'logo'
  | 'image_url'
  | 'image'
  | 'avatar_url'
  | 'avatar';

/**
 * Transform storage URLs in an object (e.g., company/user objects)
 *
 * - Does NOT mutate the original object
 * - Preserves original type shape
 * - Only transforms known image string fields
 */
export function transformObjectStorageUrls<
  T extends Record<string, unknown>
>(obj: T): T {
  if (!obj) return obj;

  const imageFields: readonly ImageField[] = [
    'logo_url',
    'logo',
    'image_url',
    'image',
    'avatar_url',
    'avatar',
  ];

  const entries = Object.entries(obj).map(([key, value]) => {
    if (
      imageFields.includes(key as ImageField) &&
      typeof value === 'string'
    ) {
      return [key, transformStorageUrl(value) ?? value] as const;
    }

    return [key, value] as const;
  });

  return Object.fromEntries(entries) as T;
}
