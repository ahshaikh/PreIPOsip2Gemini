/**
 * Storage Proxy API Route
 *
 * Proxies image requests from Next.js to Laravel backend storage.
 * This maintains Next.js image optimization while fetching from external storage.
 *
 * SECURITY: This route validates the path to prevent directory traversal
 * and only allows specific file extensions for images.
 *
 * Usage: /api/storage/company-logos/filename.png
 *        /api/storage/team-photos/10/filename.jpg
 */

import { NextRequest, NextResponse } from 'next/server';

// Allowed image extensions for security
const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.avif'];

// Get backend URL from environment
const getBackendURL = (): string => {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
  return apiUrl.endsWith('/api/v1') ? apiUrl.slice(0, -7) : apiUrl.replace('/api/v1', '');
};

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ path: string[] }> }
) {
  try {
    const { path } = await params;

    // Reconstruct the storage path from segments
    const storagePath = path.join('/');

    // SECURITY: Validate path to prevent directory traversal
    if (storagePath.includes('..') || storagePath.includes('//')) {
      return NextResponse.json(
        { error: 'Invalid path' },
        { status: 400 }
      );
    }

    // SECURITY: Validate file extension
    const extension = storagePath.substring(storagePath.lastIndexOf('.')).toLowerCase();
    if (!ALLOWED_EXTENSIONS.includes(extension)) {
      return NextResponse.json(
        { error: 'File type not allowed' },
        { status: 400 }
      );
    }

    // Construct the full backend URL
    const backendUrl = `${getBackendURL()}/storage/${storagePath}`;

    // Fetch the image from Laravel backend
    const response = await fetch(backendUrl, {
      method: 'GET',
      // Include a timeout to prevent hanging requests
      signal: AbortSignal.timeout(10000),
    });

    if (!response.ok) {
      // Return appropriate error based on backend response
      if (response.status === 404) {
        return NextResponse.json(
          { error: 'Image not found' },
          { status: 404 }
        );
      }
      return NextResponse.json(
        { error: 'Failed to fetch image' },
        { status: response.status }
      );
    }

    // Get the image data as ArrayBuffer
    const imageData = await response.arrayBuffer();

    // Determine content type from response or extension
    let contentType = response.headers.get('content-type') || 'application/octet-stream';

    // Fallback content type based on extension if not provided
    if (contentType === 'application/octet-stream') {
      const contentTypes: Record<string, string> = {
        '.jpg': 'image/jpeg',
        '.jpeg': 'image/jpeg',
        '.png': 'image/png',
        '.gif': 'image/gif',
        '.webp': 'image/webp',
        '.svg': 'image/svg+xml',
        '.avif': 'image/avif',
      };
      contentType = contentTypes[extension] || contentType;
    }

    // Return the image with appropriate headers
    return new NextResponse(imageData, {
      status: 200,
      headers: {
        'Content-Type': contentType,
        // Cache for 1 hour in browser, 24 hours in CDN
        'Cache-Control': 'public, max-age=3600, s-maxage=86400',
        // Allow Next.js image optimization
        'Accept-Ranges': 'bytes',
      },
    });

  } catch (error) {
    // Handle timeout or network errors
    if (error instanceof Error && error.name === 'TimeoutError') {
      return NextResponse.json(
        { error: 'Request timeout' },
        { status: 504 }
      );
    }

    console.error('Storage proxy error:', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}
