/**
 * Comprehensive Input Validation Utilities
 *
 * Provides regex patterns and validation functions for common input types
 * with special focus on Indian financial data (PAN, Aadhaar, IFSC, UPI)
 */

// ============================================
// REGEX PATTERNS
// ============================================

export const ValidationPatterns = {
  // Email validation (RFC 5322 simplified)
  email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,

  // Phone numbers
  indianMobile: /^[6-9]\d{9}$/, // Starts with 6-9, exactly 10 digits
  internationalPhone: /^\+?[1-9]\d{1,14}$/, // E.164 format

  // Indian financial identifiers
  panCard: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/, // Format: ABCDE1234F
  aadhaar: /^\d{12}$/, // Exactly 12 digits
  ifscCode: /^[A-Z]{4}0[A-Z0-9]{6}$/, // Format: ABCD0123456
  gstNumber: /^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/, // 15 char GST

  // UPI ID validation
  upiId: /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/, // Format: username@bank

  // Bank account
  bankAccount: /^\d{9,18}$/, // 9-18 digits

  // Password strength
  strongPassword: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/,

  // Username (alphanumeric, underscore, hyphen)
  username: /^[a-zA-Z0-9_-]{3,20}$/,

  // Indian PIN code
  pincode: /^[1-9][0-9]{5}$/,

  // URL validation
  url: /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/,

  // Numbers
  positiveInteger: /^\d+$/,
  positiveDecimal: /^\d+(\.\d{1,2})?$/,
  amount: /^\d+(\.\d{1,2})?$/, // Currency amount (2 decimal places)
};

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate email address
 */
export const isValidEmail = (email: string): boolean => {
  return ValidationPatterns.email.test(email.trim());
};

/**
 * Validate Indian mobile number (10 digits, starts with 6-9)
 */
export const isValidIndianMobile = (mobile: string): boolean => {
  const cleaned = mobile.replace(/\D/g, ''); // Remove non-digits
  return ValidationPatterns.indianMobile.test(cleaned);
};

/**
 * Validate PAN card number
 * Format: ABCDE1234F (5 letters, 4 digits, 1 letter)
 */
export const isValidPAN = (pan: string): boolean => {
  return ValidationPatterns.panCard.test(pan.toUpperCase());
};

/**
 * Validate Aadhaar number (12 digits)
 */
export const isValidAadhaar = (aadhaar: string): boolean => {
  const cleaned = aadhaar.replace(/\D/g, ''); // Remove non-digits
  return ValidationPatterns.aadhaar.test(cleaned);
};

/**
 * Validate IFSC code
 * Format: ABCD0123456 (4 letters, 0, 6 alphanumeric)
 */
export const isValidIFSC = (ifsc: string): boolean => {
  return ValidationPatterns.ifscCode.test(ifsc.toUpperCase());
};

/**
 * Validate UPI ID
 * Format: username@bank
 */
export const isValidUPI = (upi: string): boolean => {
  return ValidationPatterns.upiId.test(upi.trim().toLowerCase());
};

/**
 * Validate bank account number (9-18 digits)
 */
export const isValidBankAccount = (account: string): boolean => {
  const cleaned = account.replace(/\D/g, ''); // Remove non-digits
  return ValidationPatterns.bankAccount.test(cleaned);
};

/**
 * Validate password strength
 * Requirements:
 * - At least 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one digit
 * - At least one special character
 */
export const isStrongPassword = (password: string): boolean => {
  return ValidationPatterns.strongPassword.test(password);
};

/**
 * Get password strength level
 */
export const getPasswordStrength = (password: string): {
  level: 'weak' | 'medium' | 'strong' | 'very-strong';
  score: number;
  feedback: string[];
} => {
  const feedback: string[] = [];
  let score = 0;

  if (password.length >= 8) score++;
  else feedback.push('At least 8 characters required');

  if (password.length >= 12) score++;

  if (/[a-z]/.test(password)) score++;
  else feedback.push('Add lowercase letters');

  if (/[A-Z]/.test(password)) score++;
  else feedback.push('Add uppercase letters');

  if (/\d/.test(password)) score++;
  else feedback.push('Add numbers');

  if (/[@$!%*?&#]/.test(password)) score++;
  else feedback.push('Add special characters (@$!%*?&#)');

  const levels: Array<'weak' | 'medium' | 'strong' | 'very-strong'> = ['weak', 'weak', 'medium', 'medium', 'strong', 'strong', 'very-strong'];

  return {
    level: levels[score] || 'weak',
    score,
    feedback: feedback.length > 0 ? feedback : ['Strong password!'],
  };
};

/**
 * Validate username
 */
export const isValidUsername = (username: string): boolean => {
  return ValidationPatterns.username.test(username);
};

/**
 * Validate Indian PIN code (6 digits, doesn't start with 0)
 */
export const isValidPincode = (pincode: string): boolean => {
  return ValidationPatterns.pincode.test(pincode);
};

/**
 * Validate URL
 */
export const isValidURL = (url: string): boolean => {
  return ValidationPatterns.url.test(url);
};

/**
 * Validate amount (positive number with up to 2 decimal places)
 */
export const isValidAmount = (amount: string): boolean => {
  return ValidationPatterns.amount.test(amount) && parseFloat(amount) > 0;
};

/**
 * Validate GST number
 */
export const isValidGST = (gst: string): boolean => {
  return ValidationPatterns.gstNumber.test(gst.toUpperCase());
};

// ============================================
// FORMATTING FUNCTIONS
// ============================================

/**
 * Format PAN card (add spaces for readability)
 * Input: ABCDE1234F
 * Output: ABCDE 1234 F
 */
export const formatPAN = (pan: string): string => {
  const cleaned = pan.replace(/\s/g, '').toUpperCase();
  if (cleaned.length !== 10) return pan;
  return `${cleaned.slice(0, 5)} ${cleaned.slice(5, 9)} ${cleaned.slice(9)}`;
};

/**
 * Format Aadhaar (add spaces every 4 digits)
 * Input: 123456789012
 * Output: 1234 5678 9012
 */
export const formatAadhaar = (aadhaar: string): string => {
  const cleaned = aadhaar.replace(/\D/g, '');
  if (cleaned.length !== 12) return aadhaar;
  return cleaned.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
};

/**
 * Format IFSC code (add space after bank code)
 * Input: ABCD0123456
 * Output: ABCD 0123456
 */
export const formatIFSC = (ifsc: string): string => {
  const cleaned = ifsc.replace(/\s/g, '').toUpperCase();
  if (cleaned.length !== 11) return ifsc;
  return `${cleaned.slice(0, 4)} ${cleaned.slice(4)}`;
};

/**
 * Format Indian mobile number
 * Input: 9876543210
 * Output: +91 98765 43210
 */
export const formatIndianMobile = (mobile: string): string => {
  const cleaned = mobile.replace(/\D/g, '');
  if (cleaned.length !== 10) return mobile;
  return `+91 ${cleaned.slice(0, 5)} ${cleaned.slice(5)}`;
};

/**
 * Format currency (Indian Rupees)
 * Input: 1234567.89
 * Output: ₹12,34,567.89
 */
export const formatCurrency = (amount: number | string): string => {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;
  if (isNaN(num)) return '₹0.00';

  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
};

/**
 * Mask sensitive data
 */
export const maskPAN = (pan: string): string => {
  if (pan.length !== 10) return pan;
  return `${pan.slice(0, 2)}XXXX${pan.slice(6)}`;
};

export const maskAadhaar = (aadhaar: string): string => {
  const cleaned = aadhaar.replace(/\D/g, '');
  if (cleaned.length !== 12) return aadhaar;
  return `XXXX XXXX ${cleaned.slice(8)}`;
};

export const maskBankAccount = (account: string): string => {
  const cleaned = account.replace(/\D/g, '');
  if (cleaned.length < 4) return account;
  return `XXXX${cleaned.slice(-4)}`;
};

// ============================================
// ERROR MESSAGES
// ============================================

export const ValidationMessages = {
  email: 'Please enter a valid email address',
  mobile: 'Please enter a valid 10-digit mobile number starting with 6-9',
  pan: 'Please enter a valid PAN card number (Format: ABCDE1234F)',
  aadhaar: 'Please enter a valid 12-digit Aadhaar number',
  ifsc: 'Please enter a valid IFSC code (Format: ABCD0123456)',
  upi: 'Please enter a valid UPI ID (Format: username@bank)',
  bankAccount: 'Please enter a valid bank account number (9-18 digits)',
  password: 'Password must be at least 8 characters with uppercase, lowercase, number, and special character',
  username: 'Username must be 3-20 characters (letters, numbers, underscore, hyphen only)',
  pincode: 'Please enter a valid 6-digit PIN code',
  amount: 'Please enter a valid amount',
  required: 'This field is required',
  gst: 'Please enter a valid GST number (15 characters)',
};
