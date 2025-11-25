import { chromium, Browser, Page, BrowserContext } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';
import { parse } from 'json2csv';

// ==================== TYPES ====================

interface RouteTest {
  url: string;
  status: number | string;
  errorType: string | null;
  responseTime: number;
  redirectChain: string[];
  missingComponents: string[];
  clickedLinks: number;
  timestamp: string;
  mode: 'PUBLIC' | 'USER' | 'ADMIN';
  screenshotPath?: string;
}

interface TestConfig {
  frontendUrl: string;
  backendUrl: string;
  userEmail: string;
  userPassword: string;
  adminEmail: string;
  adminPassword: string;
  maxRedirects: number;
  timeout: number;
  headless: boolean;
  takeScreenshots: boolean;
}

interface RouteMap {
  public: RouteCategory[];
  user: RouteCategory[];
  admin: RouteCategory[];
}

interface RouteCategory {
  category: string;
  routes: Route[];
}

interface Route {
  path?: string;
  method?: string;
  type?: string;
  dynamic?: boolean;
  queryParams?: string[];
}

// ==================== CONFIGURATION ====================

const config: TestConfig = {
  frontendUrl: process.env.FRONTEND_URL || 'http://localhost:3000',
  backendUrl: process.env.BACKEND_URL || 'http://localhost:8000',
  userEmail: process.env.USER_EMAIL || '',
  userPassword: process.env.USER_PASSWORD || '',
  adminEmail: process.env.ADMIN_EMAIL || '',
  adminPassword: process.env.ADMIN_PASSWORD || '',
  maxRedirects: 5,
  timeout: 30000,
  headless: process.env.HEADLESS !== 'false',
  takeScreenshots: process.env.SCREENSHOTS === 'true',
};

// ==================== DYNAMIC ROUTE HANDLERS ====================

const dynamicRouteResolvers: Record<string, () => string> = {
  '[slug]': () => 'test-slug-' + Date.now(),
  '[id]': () => '1',
  '[userId]': () => '1',
  '[ticketId]': () => '1',
  '{provider}': () => 'google',
  '{slug}': () => 'test-product',
  '{id}': () => '1',
  '{user}': () => '1',
  '{payment}': () => '1',
  '{withdrawal}': () => '1',
  '{supportTicket}': () => '1',
  '{plan}': () => '1',
  '{product}': () => '1',
  '{bulkPurchase}': () => '1',
  '{page}': () => '1',
  '{emailTemplate}': () => '1',
  '{faq}': () => '1',
  '{blogPost}': () => '1',
  '{referralCampaign}': () => '1',
  '{kbCategory}': () => '1',
  '{kbArticle}': () => '1',
  '{menu}': () => '1',
  '{banner}': () => '1',
  '{redirect}': () => '1',
  '{ipWhitelist}': () => '1',
  '{luckyDraw}': () => '1',
  '{profitShare}': () => '1',
  '{role}': () => '1',
};

function resolveDynamicRoute(route: string): string {
  let resolved = route;

  // Handle Next.js dynamic routes [param]
  Object.entries(dynamicRouteResolvers).forEach(([placeholder, resolver]) => {
    if (resolved.includes(placeholder)) {
      resolved = resolved.replace(placeholder, resolver());
    }
  });

  return resolved;
}

// ==================== AUTHENTICATION ====================

async function loginUser(page: Page, email: string, password: string): Promise<boolean> {
  try {
    console.log(`🔐 Logging in as: ${email}`);

    await page.goto(`${config.frontendUrl}/login`, { waitUntil: 'networkidle' });

    // Wait for login form
    await page.waitForSelector('input[type="email"], input[name="email"]', { timeout: 5000 });

    // Fill login form
    await page.fill('input[type="email"], input[name="email"]', email);
    await page.fill('input[type="password"], input[name="password"]', password);

    // Click login button
    await page.click('button[type="submit"]');

    // Wait for navigation or dashboard
    await page.waitForTimeout(2000);

    // Check if login successful by checking for auth token or dashboard
    const url = page.url();
    const hasAuthToken = await page.evaluate(() => {
      return localStorage.getItem('auth_token') !== null;
    });

    if (hasAuthToken || url.includes('dashboard')) {
      console.log('✅ Login successful');
      return true;
    }

    console.log('❌ Login failed');
    return false;
  } catch (error) {
    console.error('❌ Login error:', error);
    return false;
  }
}

// ==================== ERROR DETECTION ====================

async function detectErrors(page: Page, url: string, redirectCount: number): Promise<{
  status: number | string;
  errorType: string | null;
  missingComponents: string[];
}> {
  const errors: string[] = [];
  const missingComponents: string[] = [];

  // Check for infinite redirects
  if (redirectCount >= config.maxRedirects) {
    return {
      status: 'REDIRECT_LOOP',
      errorType: 'INFINITE_REDIRECT',
      missingComponents,
    };
  }

  // Check page content
  const content = await page.content();
  const title = await page.title();

  // Detect 404
  if (
    content.includes('404') ||
    content.includes('Page Not Found') ||
    content.includes('not found') ||
    title.toLowerCase().includes('404') ||
    title.toLowerCase().includes('not found')
  ) {
    return {
      status: 404,
      errorType: '404_NOT_FOUND',
      missingComponents,
    };
  }

  // Detect 500 errors
  if (
    content.includes('500') ||
    content.includes('Internal Server Error') ||
    content.includes('server error') ||
    title.toLowerCase().includes('error')
  ) {
    return {
      status: 500,
      errorType: '500_SERVER_ERROR',
      missingComponents,
    };
  }

  // Check for React/Next.js errors
  const hasReactError = await page.evaluate(() => {
    const body = document.body.innerText;
    return (
      body.includes('Application error') ||
      body.includes('Unhandled Runtime Error') ||
      body.includes('Error: ') ||
      document.querySelector('[data-nextjs-dialog]') !== null
    );
  });

  if (hasReactError) {
    return {
      status: 'ERROR',
      errorType: 'REACT_ERROR',
      missingComponents,
    };
  }

  // Check for missing components (empty page, no content)
  const hasContent = await page.evaluate(() => {
    const body = document.body;
    const text = body.innerText.trim();
    const hasElements = body.querySelectorAll('div, section, main').length > 0;
    return text.length > 100 && hasElements;
  });

  if (!hasContent) {
    missingComponents.push('EMPTY_PAGE');
  }

  // Check for console errors
  const consoleErrors = await page.evaluate(() => {
    const errors: string[] = [];
    // This would need to be set up with page.on('console') earlier
    return errors;
  });

  if (consoleErrors.length > 0) {
    missingComponents.push(...consoleErrors);
  }

  return {
    status: 200,
    errorType: missingComponents.length > 0 ? 'MISSING_COMPONENTS' : null,
    missingComponents,
  };
}

// ==================== LINK CLICKING ====================

async function clickAllLinks(page: Page, baseUrl: string): Promise<number> {
  try {
    const links = await page.$$eval('a[href]', (anchors) =>
      anchors
        .map((a) => (a as HTMLAnchorElement).href)
        .filter((href) => href && !href.startsWith('javascript:') && !href.startsWith('mailto:'))
    );

    console.log(`   Found ${links.length} links to test`);

    let clickedCount = 0;

    for (const link of links.slice(0, 20)) { // Limit to first 20 links to avoid excessive testing
      try {
        // Only test internal links
        if (link.startsWith(baseUrl) || link.startsWith('/')) {
          const linkUrl = link.startsWith('/') ? `${baseUrl}${link}` : link;

          // Open in new page to avoid losing state
          const context = page.context();
          const newPage = await context.newPage();

          await newPage.goto(linkUrl, { waitUntil: 'domcontentloaded', timeout: 10000 });
          await newPage.waitForTimeout(500);

          clickedCount++;

          await newPage.close();
        }
      } catch (error) {
        // Link click failed, continue
      }
    }

    return clickedCount;
  } catch (error) {
    console.error('   Error clicking links:', error);
    return 0;
  }
}

// ==================== ROUTE TESTING ====================

async function testRoute(
  page: Page,
  url: string,
  mode: 'PUBLIC' | 'USER' | 'ADMIN',
  reportsDir: string
): Promise<RouteTest> {
  const startTime = Date.now();
  const redirectChain: string[] = [];
  let redirectCount = 0;

  console.log(`\n📍 Testing: ${url} [${mode}]`);

  const result: RouteTest = {
    url,
    status: 'PENDING',
    errorType: null,
    responseTime: 0,
    redirectChain,
    missingComponents: [],
    clickedLinks: 0,
    timestamp: new Date().toISOString(),
    mode,
  };

  try {
    // Track redirects
    page.on('response', (response) => {
      const status = response.status();
      if (status >= 300 && status < 400) {
        redirectCount++;
        redirectChain.push(response.url());
      }
    });

    // Navigate to URL
    const response = await page.goto(url, {
      waitUntil: 'domcontentloaded',
      timeout: config.timeout,
    });

    await page.waitForTimeout(1000); // Wait for JS to load

    result.status = response?.status() || 'UNKNOWN';
    result.redirectChain = redirectChain;

    // Detect errors
    const errorCheck = await detectErrors(page, url, redirectCount);
    result.status = errorCheck.status;
    result.errorType = errorCheck.errorType;
    result.missingComponents = errorCheck.missingComponents;

    // Click links if page loaded successfully
    if (result.status === 200 && !result.errorType) {
      result.clickedLinks = await clickAllLinks(page, config.frontendUrl);
    }

    // Take screenshot if enabled and there's an error
    if (config.takeScreenshots && result.errorType) {
      const screenshotName = `${mode}_${url.replace(/[^a-z0-9]/gi, '_')}_${Date.now()}.png`;
      const screenshotPath = path.join(reportsDir, 'screenshots', screenshotName);
      await page.screenshot({ path: screenshotPath, fullPage: true });
      result.screenshotPath = screenshotPath;
    }

    result.responseTime = Date.now() - startTime;

    console.log(`   ✓ Status: ${result.status} | Time: ${result.responseTime}ms | Links: ${result.clickedLinks}`);
    if (result.errorType) {
      console.log(`   ⚠️  Error: ${result.errorType}`);
    }

  } catch (error: any) {
    result.status = 'ERROR';
    result.errorType = error.message || 'UNKNOWN_ERROR';
    result.responseTime = Date.now() - startTime;

    console.log(`   ❌ Error: ${result.errorType}`);

    // Take screenshot on error
    if (config.takeScreenshots) {
      try {
        const screenshotName = `${mode}_ERROR_${url.replace(/[^a-z0-9]/gi, '_')}_${Date.now()}.png`;
        const screenshotPath = path.join(reportsDir, 'screenshots', screenshotName);
        await page.screenshot({ path: screenshotPath, fullPage: true });
        result.screenshotPath = screenshotPath;
      } catch (screenshotError) {
        // Ignore screenshot errors
      }
    }
  }

  return result;
}

// ==================== MODE TESTING ====================

async function testPublicMode(
  browser: Browser,
  routes: RouteCategory[],
  reportsDir: string
): Promise<RouteTest[]> {
  console.log('\n\n🌐 ========== TESTING PUBLIC ROUTES ==========\n');

  const context = await browser.newContext();
  const page = await context.newPage();
  const results: RouteTest[] = [];

  // Listen for console errors
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      console.log('   Console Error:', msg.text());
    }
  });

  // Extract all frontend public routes
  const frontendRoutes = routes
    .flatMap((category) => category.routes)
    .filter((route) => route.type === 'page' && route.path)
    .map((route) => route.path!);

  // Test each route
  for (const route of frontendRoutes) {
    const resolvedRoute = resolveDynamicRoute(route);
    const url = `${config.frontendUrl}${resolvedRoute}`;
    const result = await testRoute(page, url, 'PUBLIC', reportsDir);
    results.push(result);
  }

  await context.close();
  return results;
}

async function testUserMode(
  browser: Browser,
  routes: RouteCategory[],
  reportsDir: string
): Promise<RouteTest[]> {
  console.log('\n\n👤 ========== TESTING USER ROUTES ==========\n');

  if (!config.userEmail || !config.userPassword) {
    console.log('⚠️  USER credentials not provided. Skipping USER tests.');
    return [];
  }

  const context = await browser.newContext();
  const page = await context.newPage();
  const results: RouteTest[] = [];

  // Listen for console errors
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      console.log('   Console Error:', msg.text());
    }
  });

  // Login first
  const loginSuccess = await loginUser(page, config.userEmail, config.userPassword);

  if (!loginSuccess) {
    console.log('❌ Failed to login as USER. Skipping USER tests.');
    await context.close();
    return [];
  }

  // Extract all frontend user routes
  const frontendRoutes = routes
    .flatMap((category) => category.routes)
    .filter((route) => route.type === 'page' && route.path)
    .map((route) => route.path!);

  // Test each route
  for (const route of frontendRoutes) {
    const resolvedRoute = resolveDynamicRoute(route);
    const url = `${config.frontendUrl}${resolvedRoute}`;
    const result = await testRoute(page, url, 'USER', reportsDir);
    results.push(result);
  }

  await context.close();
  return results;
}

async function testAdminMode(
  browser: Browser,
  routes: RouteCategory[],
  reportsDir: string
): Promise<RouteTest[]> {
  console.log('\n\n👑 ========== TESTING ADMIN ROUTES ==========\n');

  if (!config.adminEmail || !config.adminPassword) {
    console.log('⚠️  ADMIN credentials not provided. Skipping ADMIN tests.');
    return [];
  }

  const context = await browser.newContext();
  const page = await context.newPage();
  const results: RouteTest[] = [];

  // Listen for console errors
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      console.log('   Console Error:', msg.text());
    }
  });

  // Login first
  const loginSuccess = await loginUser(page, config.adminEmail, config.adminPassword);

  if (!loginSuccess) {
    console.log('❌ Failed to login as ADMIN. Skipping ADMIN tests.');
    await context.close();
    return [];
  }

  // Extract all frontend admin routes
  const frontendRoutes = routes
    .flatMap((category) => category.routes)
    .filter((route) => route.type === 'page' && route.path)
    .map((route) => route.path!);

  // Test each route
  for (const route of frontendRoutes) {
    const resolvedRoute = resolveDynamicRoute(route);
    const url = `${config.frontendUrl}${resolvedRoute}`;
    const result = await testRoute(page, url, 'ADMIN', reportsDir);
    results.push(result);
  }

  await context.close();
  return results;
}

// ==================== REPORT GENERATION ====================

function generateReports(allResults: RouteTest[], reportsDir: string): void {
  console.log('\n\n📊 ========== GENERATING REPORTS ==========\n');

  // Ensure reports directory exists
  if (!fs.existsSync(reportsDir)) {
    fs.mkdirSync(reportsDir, { recursive: true });
  }

  const screenshotsDir = path.join(reportsDir, 'screenshots');
  if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir, { recursive: true });
  }

  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');

  // Generate JSON report
  const jsonPath = path.join(reportsDir, `crawler-report-${timestamp}.json`);
  fs.writeFileSync(jsonPath, JSON.stringify(allResults, null, 2));
  console.log(`✅ JSON report: ${jsonPath}`);

  // Generate CSV report
  const csvData = allResults.map((result) => ({
    URL: result.url,
    STATUS: result.status,
    ERROR_TYPE: result.errorType || 'NONE',
    RESPONSE_TIME_MS: result.responseTime,
    REDIRECT_COUNT: result.redirectChain.length,
    MISSING_COMPONENTS: result.missingComponents.join(', '),
    CLICKED_LINKS: result.clickedLinks,
    MODE: result.mode,
    TIMESTAMP: result.timestamp,
    SCREENSHOT: result.screenshotPath || '',
  }));

  const csv = parse(csvData);
  const csvPath = path.join(reportsDir, `crawler-report-${timestamp}.csv`);
  fs.writeFileSync(csvPath, csv);
  console.log(`✅ CSV report: ${csvPath}`);

  // Generate summary
  const summary = {
    totalTests: allResults.length,
    successful: allResults.filter((r) => r.status === 200 && !r.errorType).length,
    errors: allResults.filter((r) => r.errorType !== null).length,
    notFound: allResults.filter((r) => r.status === 404).length,
    serverErrors: allResults.filter((r) => r.status === 500).length,
    redirectLoops: allResults.filter((r) => r.errorType === 'INFINITE_REDIRECT').length,
    avgResponseTime: Math.round(
      allResults.reduce((sum, r) => sum + r.responseTime, 0) / allResults.length
    ),
    byMode: {
      PUBLIC: allResults.filter((r) => r.mode === 'PUBLIC').length,
      USER: allResults.filter((r) => r.mode === 'USER').length,
      ADMIN: allResults.filter((r) => r.mode === 'ADMIN').length,
    },
  };

  const summaryPath = path.join(reportsDir, `crawler-summary-${timestamp}.json`);
  fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));
  console.log(`✅ Summary report: ${summaryPath}`);

  // Console summary
  console.log('\n📈 SUMMARY:');
  console.log(`   Total Tests: ${summary.totalTests}`);
  console.log(`   ✅ Successful: ${summary.successful}`);
  console.log(`   ❌ Errors: ${summary.errors}`);
  console.log(`   🔍 Not Found (404): ${summary.notFound}`);
  console.log(`   🔥 Server Errors (500): ${summary.serverErrors}`);
  console.log(`   🔄 Redirect Loops: ${summary.redirectLoops}`);
  console.log(`   ⏱️  Avg Response Time: ${summary.avgResponseTime}ms`);
  console.log(`\n   By Mode:`);
  console.log(`     🌐 PUBLIC: ${summary.byMode.PUBLIC}`);
  console.log(`     👤 USER: ${summary.byMode.USER}`);
  console.log(`     👑 ADMIN: ${summary.byMode.ADMIN}`);
}

// ==================== MAIN ====================

async function main() {
  console.log('🚀 Starting Playwright Crawler\n');
  console.log('Configuration:');
  console.log(`   Frontend: ${config.frontendUrl}`);
  console.log(`   Backend: ${config.backendUrl}`);
  console.log(`   Headless: ${config.headless}`);
  console.log(`   Screenshots: ${config.takeScreenshots}`);
  console.log(`   User Email: ${config.userEmail ? '✓' : '✗'}`);
  console.log(`   Admin Email: ${config.adminEmail ? '✓' : '✗'}`);

  const reportsDir = path.join(__dirname, 'reports');

  // Load route map
  const routeMapPath = path.join(__dirname, 'route-map.json');
  if (!fs.existsSync(routeMapPath)) {
    console.error('❌ route-map.json not found!');
    process.exit(1);
  }

  const routeMap: RouteMap = JSON.parse(fs.readFileSync(routeMapPath, 'utf-8'));

  // Launch browser
  const browser = await chromium.launch({ headless: config.headless });

  const allResults: RouteTest[] = [];

  try {
    // Test PUBLIC routes
    const publicResults = await testPublicMode(browser, routeMap.public, reportsDir);
    allResults.push(...publicResults);

    // Test USER routes
    const userResults = await testUserMode(browser, routeMap.user, reportsDir);
    allResults.push(...userResults);

    // Test ADMIN routes
    const adminResults = await testAdminMode(browser, routeMap.admin, reportsDir);
    allResults.push(...adminResults);

    // Generate reports
    generateReports(allResults, reportsDir);

  } catch (error) {
    console.error('❌ Fatal error:', error);
  } finally {
    await browser.close();
  }

  console.log('\n✅ Crawler completed!\n');
}

// Run the crawler
main().catch(console.error);
