// V-PERFORMANCE-BUNDLE - Next.js Configuration with Bundle Optimization
import type { NextConfig } from "next";
import bundleAnalyzer from "@next/bundle-analyzer";
import { withSentryConfig } from "@sentry/nextjs";

const withBundleAnalyzer = bundleAnalyzer({
  enabled: process.env.ANALYZE === "true",
});

const nextConfig: NextConfig = {
  // Enable React strict mode for better development experience
  reactStrictMode: true,

  // Optimize images
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "**",
      },
    ],
    formats: ["image/avif", "image/webp"],
    minimumCacheTTL: 60 * 60 * 24, // 24 hours
  },

  // Enable experimental features for better performance
  experimental: {
    // Optimize package imports for faster builds
    optimizePackageImports: [
      "lucide-react",
      "@radix-ui/react-icons",
      "date-fns",
      "@tanstack/react-query",
      "recharts",
    ],
  },

  // Turbopack configuration (required for Next.js 16 when webpack config exists)
  turbopack: {},

  // Compiler optimizations
  compiler: {
    // Remove console logs in production
    removeConsole: process.env.NODE_ENV === "production" ? {
      exclude: ["error", "warn"],
    } : false,
  },

  // Webpack customization for bundle optimization
  webpack: (config, { dev, isServer }) => {
    // Production optimizations only
    if (!dev && !isServer) {
      // Enable tree shaking for lodash
      config.resolve.alias = {
        ...config.resolve.alias,
        lodash: "lodash-es",
      };

      // Optimize moment.js (if used) - remove unused locales
      config.plugins.push(
        new (require("webpack").ContextReplacementPlugin)(
          /moment[/\\]locale$/,
          /en|hi/
        )
      );

      // Split chunks optimization
      config.optimization = {
        ...config.optimization,
        moduleIds: "deterministic",
        splitChunks: {
          chunks: "all",
          minSize: 20000,
          maxSize: 244000,
          minRemainingSize: 0,
          minChunks: 1,
          maxAsyncRequests: 30,
          maxInitialRequests: 30,
          cacheGroups: {
            // Vendor chunks
            vendor: {
              test: /[\\/]node_modules[\\/]/,
              name(module: any) {
                const packageName = module.context.match(
                  /[\\/]node_modules[\\/](.*?)([\\/]|$)/
                )?.[1];
                return `vendor.${packageName?.replace("@", "")}`;
              },
              priority: 10,
              reuseExistingChunk: true,
            },
            // Common chunks
            common: {
              minChunks: 2,
              priority: -10,
              reuseExistingChunk: true,
            },
            // UI library chunks
            ui: {
              test: /[\\/]node_modules[\\/](@radix-ui|@headlessui|lucide-react)[\\/]/,
              name: "ui-libs",
              priority: 20,
              reuseExistingChunk: true,
            },
            // Charts library
            charts: {
              test: /[\\/]node_modules[\\/](recharts|d3-.*)[\\/]/,
              name: "charts",
              priority: 15,
              reuseExistingChunk: true,
            },
          },
        },
      };
    }

    return config;
  },

  // Headers for security and caching
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          // Security headers
          {
            key: "X-DNS-Prefetch-Control",
            value: "on",
          },
          {
            key: "Strict-Transport-Security",
            value: "max-age=63072000; includeSubDomains; preload",
          },
          {
            key: "X-Content-Type-Options",
            value: "nosniff",
          },
          {
            key: "X-Frame-Options",
            value: "SAMEORIGIN",
          },
          {
            key: "X-XSS-Protection",
            value: "1; mode=block",
          },
          {
            key: "Referrer-Policy",
            value: "strict-origin-when-cross-origin",
          },
          {
            key: "Permissions-Policy",
            value: "camera=(), microphone=(), geolocation=()",
          },
        ],
      },
      // Cache static assets
      {
        source: "/static/(.*)",
        headers: [
          {
            key: "Cache-Control",
            value: "public, max-age=31536000, immutable",
          },
        ],
      },
      // Cache images
      {
        source: "/_next/image(.*)",
        headers: [
          {
            key: "Cache-Control",
            value: "public, max-age=86400, stale-while-revalidate=604800",
          },
        ],
      },
    ];
  },

  // Redirects for legacy URLs
  async redirects() {
    return [
      // Add any legacy URL redirects here
      // {
      //   source: '/old-path',
      //   destination: '/new-path',
      //   permanent: true,
      // },
    ];
  },

  // Output configuration
  output: "standalone",

  // Powered by header disabled for security
  poweredByHeader: false,

  // Compress responses
  compress: true,

  // Generate ETags for caching
  generateEtags: true,

  // Trailing slash configuration
  trailingSlash: false,
};

// Sentry configuration options
const sentryWebpackPluginOptions = {
  // For all available options, see:
  // https://github.com/getsentry/sentry-webpack-plugin#options

  // Suppresses source map uploading logs during build
  silent: true,
  org: process.env.SENTRY_ORG,
  project: process.env.SENTRY_PROJECT,

  // Upload source maps only in production
  disableServerWebpackPlugin: process.env.NODE_ENV !== 'production',
  disableClientWebpackPlugin: process.env.NODE_ENV !== 'production',

  // Uncomment to route browser requests to Sentry through a Next.js rewrite to circumvent ad-blockers
  // tunnelRoute: "/monitoring",

  // Hides source maps from generated client bundles
  hideSourceMaps: true,

  // Automatically tree-shake Sentry logger statements to reduce bundle size
  disableLogger: true,

  // Enables automatic instrumentation of Vercel Cron Monitors
  automaticVercelMonitors: true,
};

// Wrap with Sentry if DSN is configured
const configWithAnalyzer = withBundleAnalyzer(nextConfig);

export default process.env.NEXT_PUBLIC_SENTRY_DSN
  ? withSentryConfig(configWithAnalyzer, sentryWebpackPluginOptions)
  : configWithAnalyzer;
