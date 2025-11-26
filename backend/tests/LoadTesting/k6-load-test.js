import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const loginDuration = new Trend('login_duration');
const apiCallCounter = new Counter('api_calls');

// Test configuration
export const options = {
  stages: [
    { duration: '1m', target: 20 },   // Ramp up to 20 users
    { duration: '3m', target: 50 },   // Ramp up to 50 users
    { duration: '5m', target: 50 },   // Stay at 50 users
    { duration: '2m', target: 100 },  // Spike to 100 users
    { duration: '3m', target: 100 },  // Stay at 100 users
    { duration: '2m', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000', 'p(99)<5000'], // 95% of requests under 2s, 99% under 5s
    http_req_failed: ['rate<0.01'],                   // Error rate under 1%
    errors: ['rate<0.05'],                            // Custom error rate under 5%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const API_PREFIX = '/api/v1';

// Test data
const testUsers = [
  { email: 'test1@example.com', password: 'password123' },
  { email: 'test2@example.com', password: 'password123' },
  { email: 'test3@example.com', password: 'password123' },
];

function getRandomUser() {
  return testUsers[Math.floor(Math.random() * testUsers.length)];
}

function login() {
  const user = getRandomUser();
  const loginRes = http.post(`${BASE_URL}${API_PREFIX}/auth/login`, JSON.stringify(user), {
    headers: { 'Content-Type': 'application/json' },
  });

  const success = check(loginRes, {
    'login status is 200': (r) => r.status === 200,
    'login has token': (r) => r.json('token') !== undefined,
  });

  errorRate.add(!success);
  loginDuration.add(loginRes.timings.duration);

  if (success) {
    return loginRes.json('token');
  }
  return null;
}

export default function () {
  group('User Authentication Flow', () => {
    const token = login();
    apiCallCounter.add(1);

    if (token) {
      // Get user profile
      const profileRes = http.get(`${BASE_URL}${API_PREFIX}/user/profile`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      check(profileRes, {
        'profile status is 200': (r) => r.status === 200,
      });
      apiCallCounter.add(1);
    }

    sleep(1);
  });

  group('Browse Plans and Products', () => {
    // Get plans
    const plansRes = http.get(`${BASE_URL}${API_PREFIX}/plans`);
    check(plansRes, {
      'plans status is 200': (r) => r.status === 200,
      'plans response is json': (r) => r.headers['Content-Type']?.includes('json'),
    });
    apiCallCounter.add(1);

    // Get products
    const productsRes = http.get(`${BASE_URL}${API_PREFIX}/products`);
    check(productsRes, {
      'products status is 200': (r) => r.status === 200,
    });
    apiCallCounter.add(1);

    sleep(2);
  });

  group('Dashboard and Analytics', () => {
    const token = login();
    apiCallCounter.add(1);

    if (token) {
      // Get dashboard
      const dashboardRes = http.get(`${BASE_URL}${API_PREFIX}/user/dashboard`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      check(dashboardRes, {
        'dashboard status is 200': (r) => r.status === 200,
      });
      apiCallCounter.add(1);

      // Get investments
      const investmentsRes = http.get(`${BASE_URL}${API_PREFIX}/user/investments`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      check(investmentsRes, {
        'investments status is 200': (r) => r.status === 200,
      });
      apiCallCounter.add(1);
    }

    sleep(3);
  });

  group('Referral System', () => {
    const token = login();
    apiCallCounter.add(1);

    if (token) {
      // Get referrals
      const referralsRes = http.get(`${BASE_URL}${API_PREFIX}/user/referrals`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      check(referralsRes, {
        'referrals status is 200': (r) => r.status === 200,
      });
      apiCallCounter.add(1);

      // Get referral stats
      const statsRes = http.get(`${BASE_URL}${API_PREFIX}/user/referrals/stats`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      check(statsRes, {
        'referral stats status is 200': (r) => r.status === 200,
      });
      apiCallCounter.add(1);
    }

    sleep(2);
  });
}

export function handleSummary(data) {
  return {
    'load-test-results.json': JSON.stringify(data, null, 2),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}

function textSummary(data, options = {}) {
  const indent = options.indent || '';
  const enableColors = options.enableColors || false;

  let summary = `\n${indent}Load Test Summary\n${indent}${'='.repeat(50)}\n`;

  // Add metrics
  if (data.metrics) {
    summary += `\n${indent}Metrics:\n`;
    Object.keys(data.metrics).forEach(metric => {
      const m = data.metrics[metric];
      if (m.type === 'trend') {
        summary += `${indent}  ${metric}:\n`;
        summary += `${indent}    avg: ${m.values.avg.toFixed(2)}ms\n`;
        summary += `${indent}    p95: ${m.values['p(95)'].toFixed(2)}ms\n`;
        summary += `${indent}    p99: ${m.values['p(99)'].toFixed(2)}ms\n`;
      }
    });
  }

  return summary;
}
