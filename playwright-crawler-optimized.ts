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
  retryCount: number;
  consoleErrors: string[];
  navigationMenus: string[];
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
  maxRetries: number;
  retryDelay: number;
  rateLimitDelay: number;
  parallelWorkers: number;
  failFast: boolean;
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

interface TestSummary {
  totalTests: number;
  successful: number;
  failed: number;
  retried: number;
  skipped: number;
  errors: ErrorSummary;
  performance: PerformanceSummary;
  byMode: Record<string, number>;
}

interface ErrorSummary {
  notFound: number;
  serverErrors: number;
  redirectLoops: number;
  authErrors: number;
  timeouts: number;
  reactErrors: number;
}

interface PerformanceSummary {
  avgResponseTime: number;
  minResponseTime: number;
  maxResponseTime: number;
  totalDuration: number;
}

// ==================== CONFIGURATION ====================

const config: TestConfig = {
  frontendUrl: process.env.FRONTEND_URL || 'http://localhost:3000',
  backendUrl: process.env.BACKEND_URL || 'http://localhost:8000',
  userEmail: process.env.USER_EMAIL || '',
  userPassword: process.env.USER_PASSWORD || '',
  adminEmail: process.env.ADMIN_EMAIL || '',
  adminPassword: process.env.ADMIN_PASSWORD || '',
  maxRedirects: parseInt(process.env.MAX_REDIRECTS || '5'),
  timeout: parseInt(process.env.TIMEOUT || '30000'),
  headless: process.env.HEADLESS !== 'false',
  takeScreenshots: process.env.SCREENSHOTS === 'true',
  maxRetries: parseInt(process.env.MAX_RETRIES || '3'),
  retryDelay: parseInt(process.env.RETRY_DELAY || '2000'),
  rateLimitDelay: parseInt(process.env.RATE_LIMIT_DELAY || '500'),
  parallelWorkers: parseInt(process.env.PARALLEL_WORKERS || '1'),
  failFast: process.env.FAIL_FAST === 'true',
};

// ==================== UTILITIES ====================

function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function retry<T>(
  fn: () => Promise<T>,
  maxRetries: number,
  delay: number,
  context: string
): Promise<T> {
  let lastError: Error | undefined;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (error: any) {
      lastError = error;
      console.log(`   ⚠️  Retry ${attempt}/${maxRetries} for ${context}: ${error.message}`);

      if (attempt < maxRetries) {
        await sleep(delay * attempt); // Exponential backoff
      }
    }
  }

  throw lastError || new Error(`Failed after ${maxRetries} retries`);
}

// ==================== DYNAMIC ROUTE HANDLERS ====================

const dynamicRouteResolvers: Record<string, () => string> = {
  '[slug]': () => 'test-slug',
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
  '{menu}': () => 'main',
  '{banner}': () => '1',
  '{redirect}': () => '1',
  '{ipWhitelist}': () => '1',
  '{luckyDraw}': () => '1',
  '{profitShare}': () => '1',
  '{role}': () => 'user',
};

function resolveDynamicRoute(route: string): string {
  let resolved = route;

  // Handle Next.js dynamic routes [param] and Laravel {param}
  Object.entries(dynamicRouteResolvers).forEach(([placeholder, resolver]) => {
    if (resolved.includes(placeholder)) {
      resolved = resolved.replace(placeholder, resolver());
    }
  });

  return resolved;
}

// ==================== AUTHENTICATION (IMPROVED) ====================

async function loginUser(
  page: Page,
  email: string,
  password: string,
  retries: number = config.maxRetries
): Promise<boolean> {
  return retry(
    async () => {
      console.log(`🔐 Logging in as: ${email}`);

      // Navigate to login page
      await page.goto(`${config.frontendUrl}/login`, {
        waitUntil: 'networkidle',
        timeout: config.timeout,
      });

      // Wait for login form to be fully loaded
      await page.waitForSelector('input[type="email"], input[name="email"]', {
        timeout: 10000,
        state: 'visible',
      });

      // Clear any existing values
      await page.fill('input[type="email"], input[name="email"]', '');
      await page.fill('input[type="password"], input[name="password"]', '');

      // Fill login form
      await page.fill('input[type="email"], input[name="email"]', email);
      await page.fill('input[type="password"], input[name="password"]', password);

      // Wait for submit button to be enabled
      const submitButton = await page.locator('button[type="submit"]').first();
      await submitButton.waitFor({ state: 'visible' });

      // Click login button and wait for navigation
      await Promise.all([
        page.waitForLoadState('networkidle'),
        submitButton.click(),
      ]);

      // Additional wait for client-side navigation
      await sleep(2000);

      // Multiple checks for successful login
      const url = page.url();
      const hasAuthToken = await page.evaluate(() => {
        return localStorage.getItem('auth_token') !== null;
      });

      // Check for error messages on login page
      const hasLoginError = await page.evaluate(() => {
        const text = document.body.innerText.toLowerCase();
        return (
          text.includes('invalid credentials') ||
          text.includes('login failed') ||
          text.includes('incorrect password') ||
          text.includes('user not found')
        );
      });

      if (hasLoginError) {
        throw new Error('Invalid credentials or login error');
      }

      // Check if still on login page (failed login)
      if (url.includes('/login') && !hasAuthToken) {
        throw new Error('Login failed - still on login page');
      }

      // Success criteria: has auth token OR redirected to dashboard/admin
      if (hasAuthToken || url.includes('dashboard') || url.includes('/admin')) {
        console.log(`✅ Login successful - redirected to: ${url}`);
        return true;
      }

      throw new Error('Login validation failed');
    },
    retries,
    config.retryDelay,
    `login for ${email}`
  );
}

// ==================== REDIRECT DETECTION (IMPROVED) ====================

interface RedirectInfo {
  count: number;
  chain: string[];
  isLoop: boolean;
}

async function trackRedirects(page: Page): Promise<RedirectInfo> {
  const redirects: string[] = [];
  let redirectCount = 0;

  // Set up redirect tracking BEFORE navigation
  const redirectHandler = (response: any) => {
    const status = response.status();
    const url = response.url();

    // Track redirect status codes
    if (status >= 300 && status < 400) {
      redirectCount++;
      redirects.push(`${url} (${status})`);

      // Check for redirect loop
      const urlCounts = redirects.filter((r) => r.includes(url)).length;
      if (urlCounts > 2) {
        console.log(`   ⚠️  Potential redirect loop detected at: ${url}`);
      }
    }
  };

  page.on('response', redirectHandler);

  return {
    count: redirectCount,
    chain: redirects,
    isLoop: redirectCount >= config.maxRedirects,
  };
}

// ==================== ERROR DETECTION (IMPROVED) ====================

async function detectErrors(
  page: Page,
  url: string,
  redirectInfo: RedirectInfo
): Promise<{
  status: number | string;
  errorType: string | null;
  missingComponents: string[];
}> {
  const missingComponents: string[] = [];

  // Check for infinite redirects
  if (redirectInfo.isLoop) {
    return {
      status: 'REDIRECT_LOOP',
      errorType: 'INFINITE_REDIRECT',
      missingComponents: [`Redirect chain: ${redirectInfo.chain.join(' -> ')}`],
    };
  }

  // Get page content and title
  const content = await page.content();
  const title = await page.title();
  const currentUrl = page.url();

  // Detect 404 - multiple patterns
  if (
    content.includes('404') ||
    content.includes('Page Not Found') ||
    content.includes('not found') ||
    content.includes('Page not found') ||
    title.toLowerCase().includes('404') ||
    title.toLowerCase().includes('not found')
  ) {
    return {
      status: 404,
      errorType: '404_NOT_FOUND',
      missingComponents: [`Current URL: ${currentUrl}`],
    };
  }

  // Detect 500 errors
  if (
    content.includes('500') ||
    content.includes('Internal Server Error') ||
    content.includes('server error') ||
    content.includes('Something went wrong') ||
    title.toLowerCase().includes('error')
  ) {
    return {
      status: 500,
      errorType: '500_SERVER_ERROR',
      missingComponents,
    };
  }

  // Check for authentication errors (redirected to login unexpectedly)
  if (currentUrl.includes('/login') && !url.includes('/login')) {
    return {
      status: 401,
      errorType: 'AUTH_REQUIRED',
      missingComponents: ['Redirected to login - session may have expired'],
    };
  }

  // Check for React/Next.js errors
  const hasReactError = await page.evaluate(() => {
    const body = document.body.innerText;
    return (
      body.includes('Application error') ||
      body.includes('Unhandled Runtime Error') ||
      body.includes('Hydration failed') ||
      body.includes('There was an error') ||
      document.querySelector('[data-nextjs-dialog]') !== null ||
      document.querySelector('#__next-build-watcher') !== null
    );
  });

  if (hasReactError) {
    return {
      status: 'ERROR',
      errorType: 'REACT_ERROR',
      missingComponents: ['React/Next.js runtime error detected'],
    };
  }

  // Check for missing components (empty page, no content)
  const pageAnalysis = await page.evaluate(() => {
    const body = document.body;
    const text = body.innerText.trim();
    const hasElements = body.querySelectorAll('div, section, main, article').length;
    const hasNavigation = body.querySelectorAll('nav, [role="navigation"]').length;
    const hasImages = body.querySelectorAll('img').length;
    const hasLinks = body.querySelectorAll('a').length;

    return {
      textLength: text.length,
      elementCount: hasElements,
      hasNavigation: hasNavigation > 0,
      hasImages: hasImages > 0,
      hasLinks: hasLinks > 0,
      isEmpty: text.length < 50 && hasElements < 5,
    };
  });

  if (pageAnalysis.isEmpty) {
    missingComponents.push('EMPTY_PAGE');
  }

  if (!pageAnalysis.hasNavigation && !url.includes('/login') && !url.includes('/signup')) {
    missingComponents.push('MISSING_NAVIGATION');
  }

  if (pageAnalysis.elementCount < 10) {
    missingComponents.push('LOW_ELEMENT_COUNT');
  }

  return {
    status: 200,
    errorType: missingComponents.length > 0 ? 'MISSING_COMPONENTS' : null,
    missingComponents,
  };
}

// ==================== MENU TRAVERSAL (IMPROVED) ====================

async function findNavigationMenus(page: Page): Promise<string[]> {
  return await page.evaluate(() => {
    const menus: string[] = [];

    // Find all navigation elements
    const navs = document.querySelectorAll('nav, [role="navigation"], .nav, .navbar, .menu');

    navs.forEach((nav, index) => {
      const links = nav.querySelectorAll('a');
      const linkCount = links.length;

      if (linkCount > 0) {
        menus.push(`Nav ${index + 1}: ${linkCount} links`);
      }
    });

    return menus;
  });
}

async function clickAllLinks(
  page: Page,
  baseUrl: string,
  maxLinks: number = 20
): Promise<number> {
  try {
    // Get all links from navigation menus first, then other links
    const links = await page.evaluate((base) => {
      const allLinks: Array<{ href: string; text: string; isNav: boolean }> = [];

      // Priority 1: Navigation links
      const navLinks = document.querySelectorAll('nav a, [role="navigation"] a');
      navLinks.forEach((a) => {
        const href = (a as HTMLAnchorElement).href;
        const text = a.textContent?.trim() || '';
        if (href && !href.startsWith('javascript:') && !href.startsWith('mailto:')) {
          allLinks.push({ href, text, isNav: true });
        }
      });

      // Priority 2: Other links
      const otherLinks = document.querySelectorAll('a:not(nav a):not([role="navigation"] a)');
      otherLinks.forEach((a) => {
        const href = (a as HTMLAnchorElement).href;
        const text = a.textContent?.trim() || '';
        if (href && !href.startsWith('javascript:') && !href.startsWith('mailto:')) {
          allLinks.push({ href, text, isNav: false });
        }
      });

      return allLinks;
    }, baseUrl);

    // Deduplicate links
    const uniqueLinks = Array.from(
      new Map(links.map((link) => [link.href, link])).values()
    );

    // Sort: navigation links first
    uniqueLinks.sort((a, b) => (b.isNav ? 1 : 0) - (a.isNav ? 1 : 0));

    console.log(
      `   Found ${uniqueLinks.length} unique links (${links.filter((l) => l.isNav).length} from navigation)`
    );

    let clickedCount = 0;

    for (const link of uniqueLinks.slice(0, maxLinks)) {
      try {
        // Only test internal links
        if (link.href.startsWith(baseUrl) || link.href.startsWith('/')) {
          const linkUrl = link.href.startsWith('/') ? `${baseUrl}${link.href}` : link.href;

          // Open in new page to avoid losing state
          const context = page.context();
          const newPage = await context.newPage();

          try {
            await newPage.goto(linkUrl, {
              waitUntil: 'domcontentloaded',
              timeout: 10000,
            });

            // Quick validation
            await newPage.waitForTimeout(500);

            clickedCount++;
          } catch (linkError) {
            console.log(`   ⚠️  Link failed: ${link.text || linkUrl}`);
          } finally {
            await newPage.close();
          }

          // Rate limiting between link clicks
          await sleep(config.rateLimitDelay);
        }
      } catch (error) {
        // Continue with other links
      }
    }

    return clickedCount;
  } catch (error) {
    console.error('   Error in link clicking:', error);
    return 0;
  }
}

// ==================== CONSOLE ERROR TRACKING ====================

function setupConsoleErrorTracking(page: Page): string[] {
  const consoleErrors: string[] = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      const text = msg.text();
      // Filter out common non-critical errors
      if (
        !text.includes('favicon') &&
        !text.includes('chrome-extension') &&
        !text.includes('ResizeObserver')
      ) {
        consoleErrors.push(text);
      }
    }
  });

  page.on('pageerror', (error) => {
    consoleErrors.push(`Page Error: ${error.message}`);
  });

  return consoleErrors;
}

// ==================== ROUTE TESTING (OPTIMIZED) ====================

async function testRoute(
  page: Page,
  url: string,
  mode: 'PUBLIC' | 'USER' | 'ADMIN',
  reportsDir: string
): Promise<RouteTest> {
  const startTime = Date.now();
  let retryCount = 0;

  console.log(`\n📍 Testing: ${url} [${mode}]`);

  const result: RouteTest = {
    url,
    status: 'PENDING',
    errorType: null,
    responseTime: 0,
    redirectChain: [],
    missingComponents: [],
    clickedLinks: 0,
    timestamp: new Date().toISOString(),
    mode,
    retryCount: 0,
    consoleErrors: [],
    navigationMenus: [],
  };

  // Set up console error tracking
  const consoleErrors = setupConsoleErrorTracking(page);

  const testLogic = async (): Promise<void> => {
    // Set up redirect tracking
    const redirects: string[] = [];
    let redirectCount = 0;

    const redirectHandler = (response: any) => {
      const status = response.status();
      if (status >= 300 && status < 400) {
        redirectCount++;
        redirects.push(`${response.url()} (${status})`);
      }
    };

    page.on('response', redirectHandler);

    try {
      // Navigate to URL
      const response = await page.goto(url, {
        waitUntil: 'domcontentloaded',
        timeout: config.timeout,
      });

      // Wait for page to stabilize
      await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});

      result.status = response?.status() || 'UNKNOWN';
      result.redirectChain = redirects;

      const redirectInfo: RedirectInfo = {
        count: redirectCount,
        chain: redirects,
        isLoop: redirectCount >= config.maxRedirects,
      };

      // Detect errors
      const errorCheck = await detectErrors(page, url, redirectInfo);
      result.status = errorCheck.status;
      result.errorType = errorCheck.errorType;
      result.missingComponents = errorCheck.missingComponents;
      result.consoleErrors = [...consoleErrors];

      // Find navigation menus
      result.navigationMenus = await findNavigationMenus(page);

      // Click links if page loaded successfully
      if (result.status === 200 && !result.errorType) {
        result.clickedLinks = await clickAllLinks(page, config.frontendUrl, 10); // Reduced for CI/CD
      }

      // Remove response listener
      page.off('response', redirectHandler);
    } catch (error: any) {
      page.off('response', redirectHandler);
      throw error;
    }
  };

  try {
    // Retry logic with exponential backoff
    await retry(testLogic, config.maxRetries, config.retryDelay, url);

    result.responseTime = Date.now() - startTime;

    console.log(
      `   ✓ Status: ${result.status} | Time: ${result.responseTime}ms | Links: ${result.clickedLinks} | Menus: ${result.navigationMenus.length}`
    );
    if (result.errorType) {
      console.log(`   ⚠️  Error: ${result.errorType}`);
    }
    if (result.consoleErrors.length > 0) {
      console.log(`   ⚠️  Console Errors: ${result.consoleErrors.length}`);
    }
  } catch (error: any) {
    result.status = 'ERROR';
    result.errorType = error.message || 'UNKNOWN_ERROR';
    result.responseTime = Date.now() - startTime;
    result.retryCount = config.maxRetries;

    console.log(`   ❌ Error after ${config.maxRetries} retries: ${result.errorType}`);
  }

  // Take screenshot on error
  if (config.takeScreenshots && result.errorType) {
    try {
      const screenshotName = `${mode}_${url.replace(/[^a-z0-9]/gi, '_')}_${Date.now()}.png`;
      const screenshotPath = path.join(reportsDir, 'screenshots', screenshotName);
      await page.screenshot({ path: screenshotPath, fullPage: true });
      result.screenshotPath = screenshotPath;
    } catch (screenshotError) {
      // Ignore screenshot errors
    }
  }

  // Rate limiting between route tests
  await sleep(config.rateLimitDelay);

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

  // Extract all frontend public routes
  const frontendRoutes = routes
    .flatMap((category) => category.routes)
    .filter((route) => route.type === 'page' && route.path)
    .map((route) => route.path!);

  console.log(`Found ${frontendRoutes.length} public routes to test`);

  // Test each route
  for (const route of frontendRoutes) {
    const resolvedRoute = resolveDynamicRoute(route);
    const url = `${config.frontendUrl}${resolvedRoute}`;

    const result = await testRoute(page, url, 'PUBLIC', reportsDir);
    results.push(result);

    // Fail fast if enabled
    if (config.failFast && result.errorType) {
      console.log('\n⚠️  Fail-fast enabled. Stopping on first error.');
      break;
    }
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

  try {
    // Login first with retry logic
    const loginSuccess = await loginUser(page, config.userEmail, config.userPassword);

    if (!loginSuccess) {
      console.log('❌ Failed to login as USER after retries. Skipping USER tests.');
      await context.close();
      return [];
    }

    // Extract all frontend user routes
    const frontendRoutes = routes
      .flatMap((category) => category.routes)
      .filter((route) => route.type === 'page' && route.path)
      .map((route) => route.path!);

    console.log(`Found ${frontendRoutes.length} user routes to test`);

    // Test each route
    for (const route of frontendRoutes) {
      const resolvedRoute = resolveDynamicRoute(route);
      const url = `${config.frontendUrl}${resolvedRoute}`;

      const result = await testRoute(page, url, 'USER', reportsDir);
      results.push(result);

      // Fail fast if enabled
      if (config.failFast && result.errorType) {
        console.log('\n⚠️  Fail-fast enabled. Stopping on first error.');
        break;
      }
    }
  } finally {
    await context.close();
  }

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

  try {
    // Login first with retry logic
    const loginSuccess = await loginUser(page, config.adminEmail, config.adminPassword);

    if (!loginSuccess) {
      console.log('❌ Failed to login as ADMIN after retries. Skipping ADMIN tests.');
      await context.close();
      return [];
    }

    // Extract all frontend admin routes
    const frontendRoutes = routes
      .flatMap((category) => category.routes)
      .filter((route) => route.type === 'page' && route.path)
      .map((route) => route.path!);

    console.log(`Found ${frontendRoutes.length} admin routes to test`);

    // Test each route
    for (const route of frontendRoutes) {
      const resolvedRoute = resolveDynamicRoute(route);
      const url = `${config.frontendUrl}${resolvedRoute}`;

      const result = await testRoute(page, url, 'ADMIN', reportsDir);
      results.push(result);

      // Fail fast if enabled
      if (config.failFast && result.errorType) {
        console.log('\n⚠️  Fail-fast enabled. Stopping on first error.');
        break;
      }
    }
  } finally {
    await context.close();
  }

  return results;
}

// ==================== REPORT GENERATION (ENHANCED) ====================

function generateReports(allResults: RouteTest[], reportsDir: string, duration: number): TestSummary {
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
    CONSOLE_ERRORS: result.consoleErrors.length,
    NAVIGATION_MENUS: result.navigationMenus.length,
    RETRY_COUNT: result.retryCount,
    MODE: result.mode,
    TIMESTAMP: result.timestamp,
    SCREENSHOT: result.screenshotPath || '',
  }));

  const csv = parse(csvData);
  const csvPath = path.join(reportsDir, `crawler-report-${timestamp}.csv`);
  fs.writeFileSync(csvPath, csv);
  console.log(`✅ CSV report: ${csvPath}`);

  // Calculate response times
  const responseTimes = allResults.map((r) => r.responseTime).filter((t) => t > 0);

  // Generate summary
  const summary: TestSummary = {
    totalTests: allResults.length,
    successful: allResults.filter((r) => r.status === 200 && !r.errorType).length,
    failed: allResults.filter((r) => r.errorType !== null).length,
    retried: allResults.filter((r) => r.retryCount > 0).length,
    skipped: 0,
    errors: {
      notFound: allResults.filter((r) => r.status === 404).length,
      serverErrors: allResults.filter((r) => r.status === 500).length,
      redirectLoops: allResults.filter((r) => r.errorType === 'INFINITE_REDIRECT').length,
      authErrors: allResults.filter((r) => r.errorType === 'AUTH_REQUIRED').length,
      timeouts: allResults.filter((r) => r.errorType === 'TIMEOUT').length,
      reactErrors: allResults.filter((r) => r.errorType === 'REACT_ERROR').length,
    },
    performance: {
      avgResponseTime: responseTimes.length > 0
        ? Math.round(responseTimes.reduce((a, b) => a + b, 0) / responseTimes.length)
        : 0,
      minResponseTime: responseTimes.length > 0 ? Math.min(...responseTimes) : 0,
      maxResponseTime: responseTimes.length > 0 ? Math.max(...responseTimes) : 0,
      totalDuration: duration,
    },
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
  console.log(`   ✅ Successful: ${summary.successful} (${Math.round((summary.successful / summary.totalTests) * 100)}%)`);
  console.log(`   ❌ Failed: ${summary.failed} (${Math.round((summary.failed / summary.totalTests) * 100)}%)`);
  console.log(`   🔄 Retried: ${summary.retried}`);
  console.log(`\n   Errors Breakdown:`);
  console.log(`     🔍 Not Found (404): ${summary.errors.notFound}`);
  console.log(`     🔥 Server Errors (500): ${summary.errors.serverErrors}`);
  console.log(`     🔄 Redirect Loops: ${summary.errors.redirectLoops}`);
  console.log(`     🔐 Auth Errors: ${summary.errors.authErrors}`);
  console.log(`     ⏱️  Timeouts: ${summary.errors.timeouts}`);
  console.log(`     ⚛️  React Errors: ${summary.errors.reactErrors}`);
  console.log(`\n   Performance:`);
  console.log(`     ⏱️  Avg Response: ${summary.performance.avgResponseTime}ms`);
  console.log(`     ⚡ Min Response: ${summary.performance.minResponseTime}ms`);
  console.log(`     🐌 Max Response: ${summary.performance.maxResponseTime}ms`);
  console.log(`     ⏰ Total Duration: ${Math.round(summary.performance.totalDuration / 1000)}s`);
  console.log(`\n   By Mode:`);
  console.log(`     🌐 PUBLIC: ${summary.byMode.PUBLIC}`);
  console.log(`     👤 USER: ${summary.byMode.USER}`);
  console.log(`     👑 ADMIN: ${summary.byMode.ADMIN}`);

  return summary;
}

// ==================== MAIN ====================

async function main() {
  const overallStartTime = Date.now();

  console.log('🚀 Starting Optimized Playwright Crawler\n');
  console.log('Configuration:');
  console.log(`   Frontend: ${config.frontendUrl}`);
  console.log(`   Backend: ${config.backendUrl}`);
  console.log(`   Headless: ${config.headless}`);
  console.log(`   Screenshots: ${config.takeScreenshots}`);
  console.log(`   Max Retries: ${config.maxRetries}`);
  console.log(`   Rate Limit Delay: ${config.rateLimitDelay}ms`);
  console.log(`   Fail Fast: ${config.failFast}`);
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
  const browser = await chromium.launch({
    headless: config.headless,
    args: ['--disable-dev-shm-usage', '--no-sandbox'], // CI/CD friendly
  });

  const allResults: RouteTest[] = [];
  let summary: TestSummary;

  try {
    // Test PUBLIC routes
    const publicResults = await testPublicMode(browser, routeMap.public, reportsDir);
    allResults.push(...publicResults);

    // Stop if fail-fast and errors found
    if (config.failFast && publicResults.some((r) => r.errorType)) {
      throw new Error('Fail-fast: Errors found in PUBLIC mode');
    }

    // Test USER routes
    const userResults = await testUserMode(browser, routeMap.user, reportsDir);
    allResults.push(...userResults);

    // Stop if fail-fast and errors found
    if (config.failFast && userResults.some((r) => r.errorType)) {
      throw new Error('Fail-fast: Errors found in USER mode');
    }

    // Test ADMIN routes
    const adminResults = await testAdminMode(browser, routeMap.admin, reportsDir);
    allResults.push(...adminResults);

    // Generate reports
    const duration = Date.now() - overallStartTime;
    summary = generateReports(allResults, reportsDir, duration);
  } catch (error) {
    console.error('❌ Fatal error:', error);

    // Still generate reports for what we have
    const duration = Date.now() - overallStartTime;
    summary = generateReports(allResults, reportsDir, duration);

    await browser.close();
    process.exit(1);
  } finally {
    await browser.close();
  }

  console.log('\n✅ Crawler completed!\n');

  // Exit with appropriate code for CI/CD
  const exitCode = summary.failed > 0 ? 1 : 0;
  process.exit(exitCode);
}

// Run the crawler
main().catch((error) => {
  console.error('❌ Unhandled error:', error);
  process.exit(1);
});
