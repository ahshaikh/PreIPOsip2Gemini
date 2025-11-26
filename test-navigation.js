/**
 * Role-Based Navigation Testing Script
 * Tests navigation for Public, User, and Admin roles
 * Reports all broken links and access issues
 */

const http = require('http');
const https = require('https');

// Configuration
const BACKEND_URL = 'http://localhost:8000';
const FRONTEND_URL = 'http://localhost:3000';
const API_BASE = `${BACKEND_URL}/api/v1`;

// Test results storage
const testResults = {
  public: {
    passed: [],
    failed: [],
    brokenLinks: []
  },
  user: {
    passed: [],
    failed: [],
    brokenLinks: []
  },
  admin: {
    passed: [],
    failed: [],
    brokenLinks: []
  },
  summary: {
    totalTests: 0,
    totalPassed: 0,
    totalFailed: 0,
    totalBrokenLinks: 0
  }
};

// Test credentials (you may need to update these)
const TEST_CREDENTIALS = {
  user: {
    email: 'testuser@example.com',
    password: 'Test123456!'
  },
  admin: {
    email: 'admin@example.com',
    password: 'Admin123456!'
  }
};

// All routes to test
const ROUTES = {
  public: [
    '/',
    '/about',
    '/about/story',
    '/about/team',
    '/about/trust',
    '/products',
    '/plans',
    '/faq',
    '/help-center',
    '/help-center/ticket',
    '/contact',
    '/blog',
    '/insights/news',
    '/insights/tutorials',
    '/insights/reports',
    '/insights/market',
    '/login',
    '/signup',
    '/verify',
    '/how-it-works',
    '/calculator',
    '/home-2',
    '/home-3',
    '/home-4',
    '/home-5',
    '/home-6',
    '/home-7'
  ],
  user: [
    '/dashboard',
    '/Profile',
    '/portfolio',
    '/offers',
    '/lucky-draws',
    '/settings',
    '/bonuses',
    '/subscribe',
    '/support',
    '/subscription',
    '/notifications',
    '/wallet',
    '/kyc',
    '/materials',
    '/profit-sharing',
    '/reports',
    '/transactions',
    '/compliance',
    '/promote',
    '/referrals'
  ],
  admin: [
    '/admin/dashboard',
    '/admin/users',
    '/admin/payments',
    '/admin/kyc-queue',
    '/admin/withdrawal-queue',
    '/admin/reports',
    '/admin/lucky-draws',
    '/admin/profit-sharing',
    '/admin/support',
    '/admin/system/audit-logs',
    '/admin/notifications/push',
    '/admin/settings/system',
    '/admin/settings/plans',
    '/admin/settings/products',
    '/admin/settings/bonuses',
    '/admin/settings/referral-campaigns',
    '/admin/settings/roles',
    '/admin/settings/ip-whitelist',
    '/admin/settings/captcha',
    '/admin/settings/canned-responses',
    '/admin/settings/notifications',
    '/admin/settings/redirects',
    '/admin/settings/knowledge-base',
    '/admin/settings/knowledge-base/articles',
    '/admin/settings/activity',
    '/admin/settings/banners',
    '/admin/settings/blog',
    '/admin/settings/menus',
    '/admin/settings/promotional-materials',
    '/admin/settings/cms',
    '/admin/settings/compliance',
    '/admin/settings/theme-seo',
    '/admin/settings/payment-gateways',
    '/admin/settings/system-health',
    '/admin/settings/backups',
    '/admin/settings/faq',
    '/admin/settings/email-templates'
  ]
};

// API endpoints to test
const API_ROUTES = {
  public: [
    '/plans',
    '/global-settings',
    '/public/faqs',
    '/public/blog'
  ],
  user: [
    '/user/profile',
    '/user/portfolio',
    '/user/subscription',
    '/user/wallet',
    '/user/bonuses',
    '/user/referrals',
    '/user/notifications',
    '/user/activity',
    '/user/support-tickets',
    '/user/lucky-draws',
    '/user/profit-sharing'
  ],
  admin: [
    '/admin/dashboard',
    '/admin/users',
    '/admin/payments',
    '/admin/kyc-queue',
    '/admin/withdrawal-queue',
    '/admin/reports/financial-summary',
    '/admin/settings',
    '/admin/system/health',
    '/admin/system/activity-logs',
    '/admin/lucky-draws',
    '/admin/profit-sharing',
    '/admin/support-tickets'
  ]
};

// Helper function to make HTTP requests
function makeRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const protocol = url.startsWith('https') ? https : http;
    const req = protocol.get(url, options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => resolve({ statusCode: res.statusCode, data, headers: res.headers }));
    });
    req.on('error', reject);
    req.setTimeout(10000, () => {
      req.destroy();
      reject(new Error('Request timeout'));
    });
  });
}

// Helper function to make API requests with authentication
function makeApiRequest(endpoint, token = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(endpoint, API_BASE);
    const options = {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    if (token) {
      options.headers['Authorization'] = `Bearer ${token}`;
    }

    http.get(url.toString(), options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        try {
          const jsonData = JSON.parse(data);
          resolve({ statusCode: res.statusCode, data: jsonData });
        } catch (e) {
          resolve({ statusCode: res.statusCode, data: data });
        }
      });
    }).on('error', reject);
  });
}

// Login function
async function login(email, password) {
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify({ email, password });
    const options = {
      hostname: 'localhost',
      port: 8000,
      path: '/api/v1/login',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData),
        'Accept': 'application/json'
      }
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        try {
          const response = JSON.parse(data);
          if (res.statusCode === 200 && response.token) {
            resolve(response.token);
          } else {
            reject(new Error(`Login failed: ${res.statusCode} - ${data}`));
          }
        } catch (e) {
          reject(new Error(`Failed to parse login response: ${e.message}`));
        }
      });
    });

    req.on('error', reject);
    req.write(postData);
    req.end();
  });
}

// Check if service is running
async function checkService(url, name) {
  try {
    await makeRequest(url);
    console.log(`✓ ${name} is running at ${url}`);
    return true;
  } catch (error) {
    console.error(`✗ ${name} is NOT running at ${url}`);
    console.error(`  Error: ${error.message}`);
    return false;
  }
}

// Test a frontend route
async function testFrontendRoute(route, token = null, role = 'public') {
  const url = `${FRONTEND_URL}${route}`;
  const testName = `[${role.toUpperCase()}] ${route}`;

  try {
    const options = {};
    if (token) {
      options.headers = { 'Cookie': `auth_token=${token}` };
    }

    const response = await makeRequest(url, options);

    if (response.statusCode === 200) {
      testResults[role].passed.push({
        route,
        url,
        status: response.statusCode,
        message: 'Success'
      });
      console.log(`  ✓ ${testName} - ${response.statusCode}`);
      return true;
    } else if (response.statusCode === 404) {
      testResults[role].brokenLinks.push({
        route,
        url,
        status: response.statusCode,
        message: 'Page not found (404)'
      });
      console.log(`  ✗ ${testName} - 404 NOT FOUND`);
      return false;
    } else if (response.statusCode === 302 || response.statusCode === 301) {
      const redirectTo = response.headers.location || 'unknown';
      testResults[role].failed.push({
        route,
        url,
        status: response.statusCode,
        message: `Redirect to ${redirectTo}`
      });
      console.log(`  → ${testName} - REDIRECT to ${redirectTo}`);
      return false;
    } else {
      testResults[role].failed.push({
        route,
        url,
        status: response.statusCode,
        message: `Unexpected status code: ${response.statusCode}`
      });
      console.log(`  ✗ ${testName} - ${response.statusCode}`);
      return false;
    }
  } catch (error) {
    testResults[role].brokenLinks.push({
      route,
      url,
      status: 'ERROR',
      message: error.message
    });
    console.log(`  ✗ ${testName} - ERROR: ${error.message}`);
    return false;
  }
}

// Test an API route
async function testApiRoute(endpoint, token = null, role = 'public') {
  const testName = `[${role.toUpperCase()} API] ${endpoint}`;

  try {
    const response = await makeApiRequest(endpoint, token);

    if (response.statusCode === 200) {
      testResults[role].passed.push({
        route: endpoint,
        url: `${API_BASE}${endpoint}`,
        status: response.statusCode,
        message: 'API Success'
      });
      console.log(`  ✓ ${testName} - ${response.statusCode}`);
      return true;
    } else if (response.statusCode === 401) {
      testResults[role].failed.push({
        route: endpoint,
        url: `${API_BASE}${endpoint}`,
        status: response.statusCode,
        message: 'Unauthorized - Authentication required'
      });
      console.log(`  ✗ ${testName} - 401 UNAUTHORIZED`);
      return false;
    } else if (response.statusCode === 403) {
      testResults[role].failed.push({
        route: endpoint,
        url: `${API_BASE}${endpoint}`,
        status: response.statusCode,
        message: 'Forbidden - Insufficient permissions'
      });
      console.log(`  ✗ ${testName} - 403 FORBIDDEN`);
      return false;
    } else if (response.statusCode === 404) {
      testResults[role].brokenLinks.push({
        route: endpoint,
        url: `${API_BASE}${endpoint}`,
        status: response.statusCode,
        message: 'API endpoint not found'
      });
      console.log(`  ✗ ${testName} - 404 NOT FOUND`);
      return false;
    } else {
      testResults[role].failed.push({
        route: endpoint,
        url: `${API_BASE}${endpoint}`,
        status: response.statusCode,
        message: `Unexpected status code: ${response.statusCode}`
      });
      console.log(`  ✗ ${testName} - ${response.statusCode}`);
      return false;
    }
  } catch (error) {
    testResults[role].brokenLinks.push({
      route: endpoint,
      url: `${API_BASE}${endpoint}`,
      status: 'ERROR',
      message: error.message
    });
    console.log(`  ✗ ${testName} - ERROR: ${error.message}`);
    return false;
  }
}

// Generate report
function generateReport() {
  console.log('\n' + '='.repeat(80));
  console.log('ROLE-BASED NAVIGATION TESTING - COMPREHENSIVE REPORT');
  console.log('='.repeat(80));
  console.log(`Generated: ${new Date().toLocaleString()}`);
  console.log('='.repeat(80));

  // Calculate summary
  for (const role of ['public', 'user', 'admin']) {
    testResults.summary.totalPassed += testResults[role].passed.length;
    testResults.summary.totalFailed += testResults[role].failed.length;
    testResults.summary.totalBrokenLinks += testResults[role].brokenLinks.length;
  }
  testResults.summary.totalTests = testResults.summary.totalPassed +
                                    testResults.summary.totalFailed +
                                    testResults.summary.totalBrokenLinks;

  // Print summary
  console.log('\n## EXECUTIVE SUMMARY\n');
  console.log(`Total Tests Run: ${testResults.summary.totalTests}`);
  console.log(`✓ Passed: ${testResults.summary.totalPassed} (${((testResults.summary.totalPassed/testResults.summary.totalTests)*100).toFixed(1)}%)`);
  console.log(`✗ Failed: ${testResults.summary.totalFailed} (${((testResults.summary.totalFailed/testResults.summary.totalTests)*100).toFixed(1)}%)`);
  console.log(`⚠ Broken Links: ${testResults.summary.totalBrokenLinks} (${((testResults.summary.totalBrokenLinks/testResults.summary.totalTests)*100).toFixed(1)}%)`);

  // Detailed results by role
  for (const role of ['public', 'user', 'admin']) {
    const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);
    const total = testResults[role].passed.length +
                  testResults[role].failed.length +
                  testResults[role].brokenLinks.length;

    console.log('\n' + '-'.repeat(80));
    console.log(`## ${roleTitle.toUpperCase()} USER TESTING RESULTS`);
    console.log('-'.repeat(80));
    console.log(`Total Tests: ${total}`);
    console.log(`✓ Passed: ${testResults[role].passed.length}`);
    console.log(`✗ Failed: ${testResults[role].failed.length}`);
    console.log(`⚠ Broken Links: ${testResults[role].brokenLinks.length}`);

    // Broken Links
    if (testResults[role].brokenLinks.length > 0) {
      console.log(`\n### BROKEN LINKS (${testResults[role].brokenLinks.length}):\n`);
      testResults[role].brokenLinks.forEach((item, index) => {
        console.log(`${index + 1}. ${item.route}`);
        console.log(`   URL: ${item.url}`);
        console.log(`   Status: ${item.status}`);
        console.log(`   Issue: ${item.message}`);
        console.log('');
      });
    }

    // Failed Tests
    if (testResults[role].failed.length > 0) {
      console.log(`\n### FAILED TESTS (${testResults[role].failed.length}):\n`);
      testResults[role].failed.forEach((item, index) => {
        console.log(`${index + 1}. ${item.route}`);
        console.log(`   URL: ${item.url}`);
        console.log(`   Status: ${item.status}`);
        console.log(`   Issue: ${item.message}`);
        console.log('');
      });
    }

    // Passed Tests Summary
    if (testResults[role].passed.length > 0) {
      console.log(`\n### PASSED TESTS (${testResults[role].passed.length}):\n`);
      console.log('All working routes:');
      testResults[role].passed.forEach((item, index) => {
        console.log(`  ${index + 1}. ${item.route} (${item.status})`);
      });
    }
  }

  console.log('\n' + '='.repeat(80));
  console.log('END OF REPORT');
  console.log('='.repeat(80) + '\n');

  return testResults;
}

// Save report to file
function saveReportToFile(results) {
  const fs = require('fs');
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const filename = `navigation-test-report-${timestamp}.json`;

  fs.writeFileSync(filename, JSON.stringify(results, null, 2));
  console.log(`\n✓ Detailed report saved to: ${filename}`);

  // Also save a markdown version
  const mdFilename = `navigation-test-report-${timestamp}.md`;
  let markdown = generateMarkdownReport(results);
  fs.writeFileSync(mdFilename, markdown);
  console.log(`✓ Markdown report saved to: ${mdFilename}\n`);

  return { json: filename, markdown: mdFilename };
}

// Generate markdown report
function generateMarkdownReport(results) {
  let md = '# Role-Based Navigation Testing - Comprehensive Report\n\n';
  md += `**Generated:** ${new Date().toLocaleString()}\n\n`;
  md += '---\n\n';

  // Executive Summary
  md += '## Executive Summary\n\n';
  md += `- **Total Tests Run:** ${results.summary.totalTests}\n`;
  md += `- **✓ Passed:** ${results.summary.totalPassed} (${((results.summary.totalPassed/results.summary.totalTests)*100).toFixed(1)}%)\n`;
  md += `- **✗ Failed:** ${results.summary.totalFailed} (${((results.summary.totalFailed/results.summary.totalTests)*100).toFixed(1)}%)\n`;
  md += `- **⚠️ Broken Links:** ${results.summary.totalBrokenLinks} (${((results.summary.totalBrokenLinks/results.summary.totalTests)*100).toFixed(1)}%)\n\n`;

  // Results by role
  for (const role of ['public', 'user', 'admin']) {
    const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);
    const total = results[role].passed.length +
                  results[role].failed.length +
                  results[role].brokenLinks.length;

    md += `---\n\n## ${roleTitle} User Testing Results\n\n`;
    md += `- **Total Tests:** ${total}\n`;
    md += `- **✓ Passed:** ${results[role].passed.length}\n`;
    md += `- **✗ Failed:** ${results[role].failed.length}\n`;
    md += `- **⚠️ Broken Links:** ${results[role].brokenLinks.length}\n\n`;

    // Broken Links
    if (results[role].brokenLinks.length > 0) {
      md += `### 🔴 Broken Links (${results[role].brokenLinks.length})\n\n`;
      results[role].brokenLinks.forEach((item, index) => {
        md += `${index + 1}. **${item.route}**\n`;
        md += `   - URL: \`${item.url}\`\n`;
        md += `   - Status: \`${item.status}\`\n`;
        md += `   - Issue: ${item.message}\n\n`;
      });
    }

    // Failed Tests
    if (results[role].failed.length > 0) {
      md += `### ⚠️ Failed Tests (${results[role].failed.length})\n\n`;
      results[role].failed.forEach((item, index) => {
        md += `${index + 1}. **${item.route}**\n`;
        md += `   - URL: \`${item.url}\`\n`;
        md += `   - Status: \`${item.status}\`\n`;
        md += `   - Issue: ${item.message}\n\n`;
      });
    }

    // Passed Tests
    if (results[role].passed.length > 0) {
      md += `### ✅ Passed Tests (${results[role].passed.length})\n\n`;
      md += '| # | Route | Status |\n';
      md += '|---|-------|--------|\n';
      results[role].passed.forEach((item, index) => {
        md += `| ${index + 1} | ${item.route} | ${item.status} |\n`;
      });
      md += '\n';
    }
  }

  md += '---\n\n';
  md += '*End of Report*\n';

  return md;
}

// Main test runner
async function runTests() {
  console.log('\n' + '='.repeat(80));
  console.log('ROLE-BASED NAVIGATION TESTING');
  console.log('Testing navigation for Public, User, and Admin roles');
  console.log('='.repeat(80) + '\n');

  // Check if services are running
  console.log('Checking if services are running...\n');
  const backendRunning = await checkService(BACKEND_URL, 'Backend API');
  const frontendRunning = await checkService(FRONTEND_URL, 'Frontend');

  if (!backendRunning || !frontendRunning) {
    console.log('\n⚠️  WARNING: One or more services are not running!');
    console.log('   This test will continue but many tests will fail.');
    console.log('   Please ensure both backend and frontend are running.');
    console.log('\n   Start backend: cd backend && php artisan serve');
    console.log('   Start frontend: cd frontend && npm run dev\n');
  }

  // Test PUBLIC routes (unauthenticated)
  console.log('\n' + '='.repeat(80));
  console.log('TESTING PUBLIC USER ROUTES (Unauthenticated)');
  console.log('='.repeat(80) + '\n');

  console.log('Frontend Routes:');
  for (const route of ROUTES.public) {
    await testFrontendRoute(route, null, 'public');
  }

  console.log('\nAPI Routes:');
  for (const endpoint of API_ROUTES.public) {
    await testApiRoute(endpoint, null, 'public');
  }

  // Test USER routes (authenticated)
  console.log('\n' + '='.repeat(80));
  console.log('TESTING REGULAR USER ROUTES (Authenticated)');
  console.log('='.repeat(80) + '\n');

  let userToken = null;
  try {
    console.log('Attempting to login as regular user...');
    userToken = await login(TEST_CREDENTIALS.user.email, TEST_CREDENTIALS.user.password);
    console.log('✓ Successfully logged in as user\n');
  } catch (error) {
    console.error('✗ Failed to login as user:', error.message);
    console.log('⚠️  User tests will show authorization failures\n');
  }

  console.log('Frontend Routes:');
  for (const route of ROUTES.user) {
    await testFrontendRoute(route, userToken, 'user');
  }

  console.log('\nAPI Routes:');
  for (const endpoint of API_ROUTES.user) {
    await testApiRoute(endpoint, userToken, 'user');
  }

  // Test ADMIN routes (authenticated admin)
  console.log('\n' + '='.repeat(80));
  console.log('TESTING ADMIN USER ROUTES (Authenticated Admin)');
  console.log('='.repeat(80) + '\n');

  let adminToken = null;
  try {
    console.log('Attempting to login as admin...');
    adminToken = await login(TEST_CREDENTIALS.admin.email, TEST_CREDENTIALS.admin.password);
    console.log('✓ Successfully logged in as admin\n');
  } catch (error) {
    console.error('✗ Failed to login as admin:', error.message);
    console.log('⚠️  Admin tests will show authorization failures\n');
  }

  console.log('Frontend Routes:');
  for (const route of ROUTES.admin) {
    await testFrontendRoute(route, adminToken, 'admin');
  }

  console.log('\nAPI Routes:');
  for (const endpoint of API_ROUTES.admin) {
    await testApiRoute(endpoint, adminToken, 'admin');
  }

  // Generate and display report
  const results = generateReport();

  // Save report to file
  const files = saveReportToFile(results);

  console.log('Testing complete!');

  // Exit with appropriate code
  const hasFailures = results.summary.totalFailed > 0 || results.summary.totalBrokenLinks > 0;
  process.exit(hasFailures ? 1 : 0);
}

// Run tests
runTests().catch(error => {
  console.error('\n❌ Fatal error during testing:', error);
  process.exit(1);
});
