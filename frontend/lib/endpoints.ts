// V-FINAL-1730-001-ENDPOINTS
// Centralized API route definitions for the entire Frontend

const API = {
  // --- AUTH ---
  LOGIN: '/login',
  LOGOUT: '/logout',
  REGISTER: '/register',
  VERIFY_OTP: '/verify-otp',
  TWO_FACTOR_VERIFY: '/two-factor/verify',
  TWO_FACTOR_RESEND: '/two-factor/resend',

  // --- USER PROFILE ---
  USER_PROFILE: '/user/profile',
  USER_UPDATE: '/user/update',
  USER_NOTIFICATIONS: '/user/notifications',

  // --- ADMIN AUTH ---
  ADMIN_LOGIN: '/admin/login',
  ADMIN_LOGOUT: '/admin/logout',
  ADMIN_ME: '/admin/me',

  // --- SETTINGS & GLOBAL CONFIG ---
  GLOBAL_SETTINGS: '/public/settings',
  SYSTEM_STATUS: '/public/system-status',

  // --- WALLET ---
  WALLET: '/wallet',
  WALLET_TRANSACTIONS: '/wallet/transactions',
  WALLET_DEPOSIT: '/wallet/deposit',
  WALLET_WITHDRAW: '/wallet/withdraw',

  // --- KYC ---
  KYC_STATUS: '/kyc/status',
  KYC_SUBMIT: '/kyc/submit',

  // --- SUBSCRIPTIONS ---
  SUBSCRIPTION_CURRENT: '/subscription/current',
  SUBSCRIPTION_PURCHASE: '/subscription/purchase',

  // --- NOTIFICATIONS ---
  MARK_NOTIFICATION_READ: '/notifications/read',

  // --- MISC ---
  HEALTHCHECK: '/healthcheck',
};

export default API;
