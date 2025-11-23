// V-PHASE4-1730-098
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1/',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
});

// Add an interceptor to attach the auth token (SSR-safe)
api.interceptors.request.use(config => {
  // Only access localStorage on the client side
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

export default api;