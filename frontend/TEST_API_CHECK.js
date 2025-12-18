// TEST FILE - Check API endpoints
// Run this in browser console on http://localhost:3000

console.log('=== API Configuration Check ===');
console.log('Expected baseURL: http://localhost:8000/api/v1/');
console.log('');

// Import and test
import api from './lib/api';

console.log('API baseURL:', api.defaults.baseURL);
console.log('');

console.log('=== Test Calls ===');
console.log('Login endpoint should be: http://localhost:8000/api/v1/login');
console.log('Profile endpoint should be: http://localhost:8000/api/v1/user/profile');
console.log('');

console.log('If you see /api/v1/api/... in Network tab, there is a caching issue');
console.log('Solution: Clear ALL caches and restart dev server');
