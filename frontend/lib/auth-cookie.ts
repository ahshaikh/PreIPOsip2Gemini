// frontend/lib/auth-cookie.ts

// Using js-cookie to manage browser cookies easily
// You may need to run: npm install js-cookie
// and: npm install -D @types/js-cookie
import Cookies from 'js-cookie';

// Define the standard name for our auth cookie to prevent typos across files
const TOKEN_NAME = 'auth_token';

/**
 * Sets the authentication token in a cookie.
 * * @param token - The JWT or API token received from the backend
 */
export const setAuthToken = (token: string) => {
  // We set the cookie to expire in 7 days to match standard session durations
  // 'secure: true' ensures it's only sent over HTTPS in production
  // 'sameSite: strict' provides CSRF protection
  Cookies.set(TOKEN_NAME, token, {
    expires: 7, 
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    path: '/' // Accessible across the entire app
  });
};

/**
 * Retrieves the authentication token from the cookie.
 * * @returns The token string or undefined if not found
 */
export const getAuthToken = () => {
  return Cookies.get(TOKEN_NAME);
};

/**
 * Removes the authentication token cookie.
 * Used during logout or when a token is invalid.
 */
export const removeAuthToken = () => {
  Cookies.remove(TOKEN_NAME, { path: '/' });
};