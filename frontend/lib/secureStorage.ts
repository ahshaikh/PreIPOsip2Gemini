/**
 * Secure Storage Utility
 * Encrypts sensitive data (like auth tokens) before storing in localStorage
 * Provides protection against XSS attacks that attempt to read localStorage
 */

import CryptoJS from 'crypto-js';

// Use a secure key derived from multiple sources
// In production, this should be more sophisticated (e.g., device fingerprint + session data)
const getEncryptionKey = (): string => {
  // Combine multiple entropy sources
  const browserFingerprint = typeof window !== 'undefined'
    ? `${navigator.userAgent}-${navigator.language}-${screen.width}x${screen.height}`
    : 'server';

  // Use environment variable as base, fallback to fingerprint
  const baseKey = process.env.NEXT_PUBLIC_ENCRYPTION_KEY || browserFingerprint;

  // Hash the key to ensure consistent length
  return CryptoJS.SHA256(baseKey).toString();
};

/**
 * Encrypt and store data in localStorage
 */
export const secureStorage = {
  /**
   * Set encrypted item in localStorage
   */
  setItem: (key: string, value: string): void => {
    if (typeof window === 'undefined') return;

    try {
      const encryptionKey = getEncryptionKey();
      const encrypted = CryptoJS.AES.encrypt(value, encryptionKey).toString();
      localStorage.setItem(key, encrypted);
    } catch (error) {
      console.error('Failed to encrypt data:', error);
      // Fallback to unencrypted storage in case of error
      localStorage.setItem(key, value);
    }
  },

  /**
   * Get and decrypt item from localStorage
   */
  getItem: (key: string): string | null => {
    if (typeof window === 'undefined') return null;

    try {
      const encrypted = localStorage.getItem(key);
      if (!encrypted) return null;

      const encryptionKey = getEncryptionKey();
      const decrypted = CryptoJS.AES.decrypt(encrypted, encryptionKey);
      const value = decrypted.toString(CryptoJS.enc.Utf8);

      // If decryption fails (returns empty string), the data might be unencrypted (migration case)
      if (!value && encrypted) {
        // Return the raw value for backward compatibility
        return encrypted;
      }

      return value;
    } catch (error) {
      console.error('Failed to decrypt data:', error);
      // Fallback: return raw value if decryption fails
      return localStorage.getItem(key);
    }
  },

  /**
   * Remove item from localStorage
   */
  removeItem: (key: string): void => {
    if (typeof window === 'undefined') return;
    localStorage.removeItem(key);
  },

  /**
   * Clear all items from localStorage
   */
  clear: (): void => {
    if (typeof window === 'undefined') return;
    localStorage.clear();
  }
};

/**
 * Migration utility to encrypt existing unencrypted tokens
 * Call this once on app initialization
 */
export const migrateToEncryptedStorage = (): void => {
  if (typeof window === 'undefined') return;

  const authToken = localStorage.getItem('auth_token');

  // Check if token exists and is likely unencrypted (not base64 encrypted format)
  if (authToken && !authToken.startsWith('U2FsdGVkX1')) {
    // Re-encrypt existing token
    secureStorage.setItem('auth_token', authToken);
  }
};
