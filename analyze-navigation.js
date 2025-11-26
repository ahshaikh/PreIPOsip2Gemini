/**
 * Static Navigation Analysis Script
 * Analyzes all navigation links and compares against actual page files
 * Reports broken links, missing pages, and navigation issues
 */

const fs = require('fs');
const path = require('path');

// Results storage
const analysisResults = {
  public: {
    expectedRoutes: [],
    existingPages: [],
    missingPages: [],
    workingRoutes: []
  },
  user: {
    expectedRoutes: [],
    existingPages: [],
    missingPages: [],
    workingRoutes: []
  },
  admin: {
    expectedRoutes: [],
    existingPages: [],
    missingPages: [],
    workingRoutes: []
  },
  api: {
    expectedEndpoints: [],
    definedEndpoints: [],
    missingControllers: [],
    workingEndpoints: []
  }
};

// Expected routes based on navigation components
const EXPECTED_ROUTES = {
  public: [
    // From Navbar - About section
    '/about',
    '/about/story',
    '/how-it-works',
    '/about/trust',
    '/about/team',

    // From Navbar - Pre-IPO Listings
    '/products',
    '/plans',

    // From Navbar - Insights
    '/insights/market',
    '/insights/reports',
    '/insights/news',
    '/insights/tutorials',

    // From Navbar - Support
    '/faq',
    '/contact',
    '/help-center', // Help Center (FIXED)
    '/help-center/ticket', // Raise a ticket (FIXED)

    // From exploration
    '/blog',
    '/login',
    '/signup',
    '/verify',
    '/calculator',
    '/help-center',
    '/help-center/ticket',
    '/', // Home
  ],

  user: [
    // From DashboardNav
    '/dashboard',
    '/kyc',
    '/subscription',
    '/portfolio',
    '/bonuses',
    '/referrals',
    '/wallet',
    '/lucky-draws',
    '/profit-sharing',
    '/support',
    '/profile',

    // Additional from page files
    '/Profile', // Note: capital P
    '/offers',
    '/settings',
    '/subscribe',
    '/notifications',
    '/materials',
    '/reports',
    '/transactions',
    '/compliance',
    '/promote'
  ],

  admin: [
    // From AdminNav - Main
    '/admin/dashboard',
    '/admin/users',
    '/admin/payments',
    '/admin/kyc-queue',
    '/admin/withdrawal-queue',
    '/admin/reports',
    '/admin/lucky-draws',
    '/admin/profit-sharing',
    '/admin/support',

    // From AdminNav - Notifications
    '/admin/notifications/push',

    // From AdminNav - Settings
    '/admin/settings/system',
    '/admin/settings/plans',
    '/admin/settings/products',
    '/admin/settings/bonuses',
    '/admin/settings/referral-campaigns',
    '/admin/settings/roles',
    '/admin/settings/ip-whitelist',
    '/admin/settings/captcha',
    '/admin/settings/compliance',
    '/admin/settings/cms',
    '/admin/settings/menus',
    '/admin/settings/banners',
    '/admin/settings/theme-seo',
    '/admin/settings/blog',
    '/admin/settings/faq',
    '/admin/settings/notifications',
    '/admin/settings/system-health',
    '/admin/settings/activity',
    '/admin/settings/backups',

    // Additional from page files
    '/admin/settings/payment-gateways',
    '/admin/settings/email-templates',
    '/admin/settings/redirects',
    '/admin/settings/knowledge-base',
    '/admin/settings/knowledge-base/articles',
    '/admin/settings/promotional-materials',
    '/admin/settings/canned-responses',
    '/admin/system/audit-logs',
    '/admin/support/chat-transcript'
  ]
};

// API endpoints to verify
const API_ENDPOINTS = {
  public: [
    'GET /plans',
    'GET /plans/{slug}',
    'GET /page/{slug}',
    'GET /public/faqs',
    'GET /public/blog',
    'GET /public/blog/{slug}',
    'GET /global-settings',
    'GET /products/{slug}/history'
  ],
  user: [
    'GET /user/profile',
    'PUT /user/profile',
    'POST /user/profile/avatar',
    'GET /user/kyc',
    'POST /user/kyc',
    'GET /user/subscription',
    'POST /user/subscription',
    'POST /user/subscription/change-plan',
    'POST /user/subscription/pause',
    'POST /user/subscription/resume',
    'POST /user/subscription/cancel',
    'GET /user/portfolio',
    'GET /user/bonuses',
    'GET /user/referrals',
    'GET /user/wallet',
    'POST /user/wallet/deposit/initiate',
    'POST /user/wallet/withdraw',
    'GET /user/withdrawals',
    'GET /user/activity',
    'GET /user/support-tickets',
    'POST /user/support-tickets',
    'POST /user/support-tickets/{id}/reply',
    'POST /user/support-tickets/{id}/close',
    'POST /user/support-tickets/{id}/rate',
    'GET /user/lucky-draws',
    'GET /user/profit-sharing',
    'GET /user/notifications',
    'POST /user/notifications/{id}/read',
    'POST /user/notifications/mark-all-read',
    'DELETE /user/notifications/{id}',
    'POST /user/security/password',
    'GET /user/2fa/status',
    'POST /user/2fa/enable',
    'POST /user/2fa/confirm',
    'POST /user/2fa/disable'
  ],
  admin: [
    'GET /admin/dashboard',
    'GET /admin/users',
    'POST /admin/users',
    'PUT /admin/users/{id}',
    'GET /admin/kyc-queue',
    'POST /admin/kyc-queue/{id}/approve',
    'POST /admin/kyc-queue/{id}/reject',
    'GET /admin/plans',
    'POST /admin/plans',
    'PUT /admin/plans/{id}',
    'DELETE /admin/plans/{id}',
    'GET /admin/products',
    'POST /admin/products',
    'PUT /admin/products/{id}',
    'DELETE /admin/products/{id}',
    'GET /admin/payments',
    'GET /admin/withdrawal-queue',
    'POST /admin/withdrawal-queue/{id}/approve',
    'POST /admin/withdrawal-queue/{id}/complete',
    'POST /admin/withdrawal-queue/{id}/reject',
    'GET /admin/settings',
    'PUT /admin/settings',
    'GET /admin/lucky-draws',
    'GET /admin/profit-sharing',
    'GET /admin/support-tickets',
    'GET /admin/reports/financial-summary',
    'GET /admin/system/health',
    'GET /admin/system/activity-logs',
    'GET /admin/roles',
    'POST /admin/roles',
    'GET /admin/ip-whitelist',
    'POST /admin/ip-whitelist'
  ]
};

/**
 * Convert a Next.js file path to a route
 * e.g., frontend/app/(public)/about/page.tsx -> /about
 */
function filePathToRoute(filePath) {
  // Remove frontend/app/ prefix
  let route = filePath.replace(/^frontend\/app/, '');

  // Remove /page.tsx, /page.jsx, etc.
  route = route.replace(/\/page\.(tsx|jsx|ts|js)$/, '');

  // Remove route groups (public), (user)
  route = route.replace(/\/\([^)]+\)/g, '');

  // Handle root
  if (route === '') route = '/';

  // Ensure leading slash
  if (!route.startsWith('/')) route = '/' + route;

  return route;
}

/**
 * Find all page.tsx files in frontend/app
 */
function findAllPages() {
  const pages = {
    all: [],
    public: [],
    user: [],
    admin: []
  };

  const appDir = path.join(__dirname, 'frontend', 'app');

  function scanDir(dir) {
    if (!fs.existsSync(dir)) return;

    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);

      if (entry.isDirectory()) {
        scanDir(fullPath);
      } else if (entry.name.match(/^page\.(tsx|jsx|ts|js)$/)) {
        const relativePath = path.relative(__dirname, fullPath);
        const route = filePathToRoute(relativePath);

        pages.all.push({ file: relativePath, route });

        // Categorize
        if (route.startsWith('/admin')) {
          pages.admin.push({ file: relativePath, route });
        } else if (relativePath.includes('/(user)')) {
          pages.user.push({ file: relativePath, route });
        } else if (relativePath.includes('/(public)') || route === '/') {
          pages.public.push({ file: relativePath, route });
        } else {
          // Uncategorized pages (dashboard, profile, etc. without route group)
          if (route.startsWith('/dashboard') || route.startsWith('/profile') ||
              route.startsWith('/portfolio') || route.startsWith('/kyc') ||
              route.startsWith('/subscription') || route.startsWith('/bonuses') ||
              route.startsWith('/referrals') || route.startsWith('/wallet') ||
              route.startsWith('/support') || route.startsWith('/settings') ||
              route.startsWith('/offers') || route.startsWith('/lucky-draws') ||
              route.startsWith('/profit-sharing') || route.startsWith('/notifications') ||
              route.startsWith('/materials') || route.startsWith('/reports') ||
              route.startsWith('/transactions') || route.startsWith('/compliance') ||
              route.startsWith('/promote')) {
            pages.user.push({ file: relativePath, route });
          } else {
            pages.public.push({ file: relativePath, route });
          }
        }
      }
    }
  }

  scanDir(appDir);
  return pages;
}

/**
 * Check if a controller exists for an API endpoint
 */
function checkControllerExists(endpoint) {
  const backendDir = path.join(__dirname, 'backend', 'app', 'Http', 'Controllers', 'Api');

  // Extract path from endpoint (e.g., "GET /admin/users" -> "admin/users")
  const [method, routePath] = endpoint.split(' ');
  const parts = routePath.replace(/^\//, '').split('/');

  // Determine controller path
  let controllerPath = '';
  if (parts[0] === 'admin') {
    controllerPath = path.join(backendDir, 'Admin');
  } else if (parts[0] === 'user') {
    controllerPath = path.join(backendDir, 'User');
  } else {
    controllerPath = path.join(backendDir, 'Public');
  }

  // Check if directory exists
  return fs.existsSync(controllerPath);
}

/**
 * Compare expected routes with existing pages
 */
function analyzeRoutes() {
  console.log('\n' + '='.repeat(80));
  console.log('STATIC NAVIGATION ANALYSIS');
  console.log('Analyzing all routes and navigation links');
  console.log('='.repeat(80) + '\n');

  // Find all existing pages
  console.log('Scanning all page files...');
  const existingPages = findAllPages();

  console.log(`Found ${existingPages.all.length} total pages:`);
  console.log(`  - Public: ${existingPages.public.length}`);
  console.log(`  - User: ${existingPages.user.length}`);
  console.log(`  - Admin: ${existingPages.admin.length}\n`);

  // Analyze each role
  for (const role of ['public', 'user', 'admin']) {
    console.log('='.repeat(80));
    console.log(`Analyzing ${role.toUpperCase()} routes`);
    console.log('='.repeat(80));

    const expectedRoutes = EXPECTED_ROUTES[role];
    const existingRoutes = existingPages[role].map(p => p.route);

    analysisResults[role].expectedRoutes = expectedRoutes;
    analysisResults[role].existingPages = existingPages[role];

    // Find missing pages
    for (const route of expectedRoutes) {
      // Normalize route for comparison
      const normalizedRoute = route.toLowerCase();
      const found = existingRoutes.some(er => {
        // Handle query parameters
        const cleanRoute = route.split('?')[0];
        return er.toLowerCase() === normalizedRoute || er.toLowerCase() === cleanRoute.toLowerCase();
      });

      if (found) {
        analysisResults[role].workingRoutes.push(route);
        console.log(`  ✓ ${route}`);
      } else {
        // Check if it's a dynamic route
        const isDynamic = route.includes('[') || route.includes('{');
        const hasQuery = route.includes('?');

        if (hasQuery) {
          // Routes with query params - check base route
          const baseRoute = route.split('?')[0];
          const baseExists = existingRoutes.some(er => er.toLowerCase() === baseRoute.toLowerCase());
          if (baseExists) {
            analysisResults[role].workingRoutes.push(route);
            console.log(`  ✓ ${route} (query param variant)`);
          } else {
            analysisResults[role].missingPages.push(route);
            console.log(`  ✗ ${route} - MISSING PAGE`);
          }
        } else if (isDynamic) {
          // Dynamic routes - check if dynamic version exists
          const basePath = route.split('[')[0].split('{')[0];
          const hasDynamic = existingRoutes.some(er => er.startsWith(basePath) && (er.includes('[') || er.includes('{')));
          if (hasDynamic) {
            analysisResults[role].workingRoutes.push(route);
            console.log(`  ✓ ${route} (dynamic route)`);
          } else {
            analysisResults[role].missingPages.push(route);
            console.log(`  ✗ ${route} - MISSING DYNAMIC ROUTE`);
          }
        } else {
          analysisResults[role].missingPages.push(route);
          console.log(`  ✗ ${route} - MISSING PAGE`);
        }
      }
    }

    // Find unexpected pages (pages that exist but aren't in navigation)
    const unexpectedPages = existingPages[role].filter(p => {
      const route = p.route.toLowerCase();
      return !expectedRoutes.some(er => {
        const expectedLower = er.toLowerCase().split('?')[0];
        return route === expectedLower || route.startsWith(expectedLower + '/');
      });
    });

    if (unexpectedPages.length > 0) {
      console.log(`\n  Orphaned pages (not in navigation):`);
      unexpectedPages.forEach(p => {
        console.log(`    ℹ ${p.route} - exists but not linked in navigation`);
      });
    }

    console.log('');
  }

  // Analyze API endpoints
  console.log('='.repeat(80));
  console.log('API ENDPOINT ANALYSIS');
  console.log('='.repeat(80));

  const apiRoutesFile = path.join(__dirname, 'backend', 'routes', 'api.php');
  const apiRoutesExist = fs.existsSync(apiRoutesFile);

  if (apiRoutesExist) {
    console.log('✓ API routes file exists\n');
    const apiContent = fs.readFileSync(apiRoutesFile, 'utf8');

    for (const role of ['public', 'user', 'admin']) {
      const endpoints = API_ENDPOINTS[role];
      console.log(`\nChecking ${role.toUpperCase()} API endpoints:`);

      for (const endpoint of endpoints) {
        const [method, route] = endpoint.split(' ');
        const routePattern = route.replace(/\{[^}]+\}/g, '[^/]+');
        const regex = new RegExp(`Route::${method.toLowerCase()}\\(['"]${routePattern}['"]`, 'i');

        if (regex.test(apiContent) || apiContent.includes(route.replace(/\{|\}/g, ''))) {
          analysisResults.api.workingEndpoints.push(endpoint);
          console.log(`  ✓ ${endpoint}`);
        } else {
          analysisResults.api.missingControllers.push(endpoint);
          console.log(`  ✗ ${endpoint} - NOT FOUND IN ROUTES`);
        }
      }
    }
  } else {
    console.log('✗ API routes file not found!');
  }
}

/**
 * Generate comprehensive report
 */
function generateReport() {
  console.log('\n' + '='.repeat(80));
  console.log('COMPREHENSIVE NAVIGATION TESTING REPORT');
  console.log('='.repeat(80));
  console.log(`Generated: ${new Date().toLocaleString()}`);
  console.log('='.repeat(80));

  // Executive Summary
  let totalExpected = 0;
  let totalWorking = 0;
  let totalMissing = 0;

  for (const role of ['public', 'user', 'admin']) {
    totalExpected += analysisResults[role].expectedRoutes.length;
    totalWorking += analysisResults[role].workingRoutes.length;
    totalMissing += analysisResults[role].missingPages.length;
  }

  console.log('\n## EXECUTIVE SUMMARY\n');
  console.log(`Total Routes Tested: ${totalExpected}`);
  console.log(`✓ Working Routes: ${totalWorking} (${((totalWorking/totalExpected)*100).toFixed(1)}%)`);
  console.log(`✗ Broken Links: ${totalMissing} (${((totalMissing/totalExpected)*100).toFixed(1)}%)`);

  // Detailed report by role
  for (const role of ['public', 'user', 'admin']) {
    const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);

    console.log('\n' + '-'.repeat(80));
    console.log(`## ${roleTitle.toUpperCase()} USER NAVIGATION`);
    console.log('-'.repeat(80));

    console.log(`\nTotal Expected Routes: ${analysisResults[role].expectedRoutes.length}`);
    console.log(`✓ Working: ${analysisResults[role].workingRoutes.length}`);
    console.log(`✗ Missing: ${analysisResults[role].missingPages.length}`);

    if (analysisResults[role].missingPages.length > 0) {
      console.log(`\n### 🔴 BROKEN LINKS (${analysisResults[role].missingPages.length}):\n`);
      analysisResults[role].missingPages.forEach((route, index) => {
        console.log(`${index + 1}. ${route}`);
        console.log(`   Issue: Page file does not exist`);
        console.log(`   Action Required: Create page file at frontend/app${route}/page.tsx\n`);
      });
    }

    if (analysisResults[role].workingRoutes.length > 0) {
      console.log(`\n### ✅ WORKING ROUTES (${analysisResults[role].workingRoutes.length}):\n`);
      analysisResults[role].workingRoutes.forEach((route, index) => {
        console.log(`  ${index + 1}. ${route}`);
      });
    }
  }

  // API Summary
  const totalApiEndpoints = API_ENDPOINTS.public.length + API_ENDPOINTS.user.length + API_ENDPOINTS.admin.length;

  console.log('\n' + '-'.repeat(80));
  console.log('## API ENDPOINTS ANALYSIS');
  console.log('-'.repeat(80));
  console.log(`\nTotal API Endpoints: ${totalApiEndpoints}`);
  console.log(`✓ Defined: ${analysisResults.api.workingEndpoints.length}`);
  console.log(`✗ Missing: ${analysisResults.api.missingControllers.length}`);

  if (analysisResults.api.missingControllers.length > 0) {
    console.log(`\n### 🔴 MISSING API ENDPOINTS (${analysisResults.api.missingControllers.length}):\n`);
    analysisResults.api.missingControllers.forEach((endpoint, index) => {
      console.log(`${index + 1}. ${endpoint}`);
      console.log(`   Issue: Endpoint not found in api.php`);
      console.log(`   Action Required: Add route definition and controller method\n`);
    });
  }

  console.log('\n' + '='.repeat(80));
  console.log('END OF REPORT');
  console.log('='.repeat(80) + '\n');

  return analysisResults;
}

/**
 * Save report to files
 */
function saveReports(results) {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);

  // Save JSON
  const jsonFile = `navigation-analysis-${timestamp}.json`;
  fs.writeFileSync(jsonFile, JSON.stringify(results, null, 2));
  console.log(`✓ JSON report saved: ${jsonFile}`);

  // Save Markdown
  const mdFile = `navigation-analysis-${timestamp}.md`;
  const markdown = generateMarkdownReport(results);
  fs.writeFileSync(mdFile, markdown);
  console.log(`✓ Markdown report saved: ${mdFile}\n`);

  return { json: jsonFile, markdown: mdFile };
}

/**
 * Generate markdown report
 */
function generateMarkdownReport(results) {
  let md = '# Role-Based Navigation Testing Report\n\n';
  md += `**Generated:** ${new Date().toLocaleString()}\n\n`;
  md += '---\n\n';

  // Executive Summary
  let totalExpected = 0;
  let totalWorking = 0;
  let totalMissing = 0;

  for (const role of ['public', 'user', 'admin']) {
    totalExpected += results[role].expectedRoutes.length;
    totalWorking += results[role].workingRoutes.length;
    totalMissing += results[role].missingPages.length;
  }

  md += '## Executive Summary\n\n';
  md += `- **Total Routes Tested:** ${totalExpected}\n`;
  md += `- **✅ Working Routes:** ${totalWorking} (${((totalWorking/totalExpected)*100).toFixed(1)}%)\n`;
  md += `- **🔴 Broken Links:** ${totalMissing} (${((totalMissing/totalExpected)*100).toFixed(1)}%)\n\n`;

  // Results by role
  for (const role of ['public', 'user', 'admin']) {
    const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);

    md += `---\n\n## ${roleTitle} User Navigation\n\n`;
    md += `- **Total Expected Routes:** ${results[role].expectedRoutes.length}\n`;
    md += `- **✅ Working:** ${results[role].workingRoutes.length}\n`;
    md += `- **🔴 Missing:** ${results[role].missingPages.length}\n\n`;

    if (results[role].missingPages.length > 0) {
      md += `### 🔴 Broken Links (${results[role].missingPages.length})\n\n`;
      results[role].missingPages.forEach((route, index) => {
        md += `${index + 1}. **${route}**\n`;
        md += `   - **Issue:** Page file does not exist\n`;
        md += `   - **Action Required:** Create page file at \`frontend/app${route}/page.tsx\`\n\n`;
      });
    }

    if (results[role].workingRoutes.length > 0) {
      md += `### ✅ Working Routes (${results[role].workingRoutes.length})\n\n`;
      md += '| # | Route |\n';
      md += '|---|-------|\n';
      results[role].workingRoutes.forEach((route, index) => {
        md += `| ${index + 1} | ${route} |\n`;
      });
      md += '\n';
    }
  }

  // API Endpoints
  const totalApiEndpoints = API_ENDPOINTS.public.length + API_ENDPOINTS.user.length + API_ENDPOINTS.admin.length;

  md += `---\n\n## API Endpoints Analysis\n\n`;
  md += `- **Total API Endpoints:** ${totalApiEndpoints}\n`;
  md += `- **✅ Defined:** ${results.api.workingEndpoints.length}\n`;
  md += `- **🔴 Missing:** ${results.api.missingControllers.length}\n\n`;

  if (results.api.missingControllers.length > 0) {
    md += `### 🔴 Missing API Endpoints (${results.api.missingControllers.length})\n\n`;
    results.api.missingControllers.forEach((endpoint, index) => {
      md += `${index + 1}. **${endpoint}**\n`;
      md += `   - **Issue:** Endpoint not found in api.php\n`;
      md += `   - **Action Required:** Add route definition and controller method\n\n`;
    });
  }

  md += '---\n\n*End of Report*\n';

  return md;
}

// Run analysis
try {
  analyzeRoutes();
  const results = generateReport();
  const files = saveReports(results);

  console.log('✅ Analysis complete!\n');

  // Exit with error code if broken links found
  const hasBrokenLinks = results.public.missingPages.length > 0 ||
                        results.user.missingPages.length > 0 ||
                        results.admin.missingPages.length > 0;

  process.exit(hasBrokenLinks ? 1 : 0);
} catch (error) {
  console.error('\n❌ Fatal error during analysis:', error);
  console.error(error.stack);
  process.exit(1);
}
