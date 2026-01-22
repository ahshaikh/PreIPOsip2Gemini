/** * V-AUDIT-REFACTOR-2025 | V-ASSET-OPTIMIZATION | V-LCP-REDUCTION
 * Configuration verified for Module 19 performance standards:
 */
/** @type {import('next').NextConfig} */
const nextConfig = {
  // [AUDIT FIX]: Enables Gzip/Brotli compression for all text-based assets (HTML/JS/CSS).
  // Significantly reduces bandwidth usage and improves load times over 3G/4G.
  compress: true,
  
  // [AUDIT FIX]: Modern Image Optimization.
  // Serves AVIF/WebP formats automatically based on browser support.
  // Device sizes ensure mobile users don't download 4K desktop-sized images.
  // FIX: Added remotePatterns to allow images from backend storage
  images: {
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200],
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/api/v1/storage/**',
      },
      {
        protocol: 'https',
        hostname: 'preiposip.com',
        pathname: '/storage/**',
      },
      {
        protocol: 'https',
        hostname: 'placehold.co',
        pathname: '/**',
      },
    ],
  },

  // [AUDIT FIX]: Security & Performance.
  // Disabling source maps in production prevents leaking code structure 
  // and reduces the memory footprint of the build.
  productionBrowserSourceMaps: false,
};

module.exports = nextConfig;