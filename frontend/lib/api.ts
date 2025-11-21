// V-PHASE4-1730-098
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1/',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  },
});

// We'll add an interceptor to attach the auth token once we have auth logic
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;