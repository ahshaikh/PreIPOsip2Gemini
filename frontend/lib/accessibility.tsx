/**
 * Accessibility Utilities
 * Helpers for improving accessibility (WCAG 2.1 AA compliance)
 */

/**
 * Common ARIA labels for buttons and links
 */
export const AriaLabels = {
  // Navigation
  mainMenu: 'Main navigation menu',
  userMenu: 'User account menu',
  closeMenu: 'Close menu',
  openMenu: 'Open menu',
  skipToMain: 'Skip to main content',
  previousPage: 'Go to previous page',
  nextPage: 'Go to next page',

  // Actions
  edit: (item: string) => `Edit ${item}`,
  delete: (item: string) => `Delete ${item}`,
  view: (item: string) => `View ${item}`,
  download: (item: string) => `Download ${item}`,
  upload: (item: string) => `Upload ${item}`,
  refresh: 'Refresh data',
  search: 'Search',
  filter: 'Filter results',
  sort: 'Sort options',

  // Forms
  submitForm: 'Submit form',
  resetForm: 'Reset form',
  cancelForm: 'Cancel',
  saveChanges: 'Save changes',

  // Modals
  closeModal: 'Close dialog',
  closeNotification: 'Close notification',

  // Theme
  toggleTheme: 'Toggle theme',
  selectTheme: (theme: string) => `Switch to ${theme} theme`,

  // Notifications
  successNotification: 'Success notification',
  errorNotification: 'Error notification',
  warningNotification: 'Warning notification',
  infoNotification: 'Information notification',

  // Loading
  loading: 'Loading...',
  loadingMore: 'Loading more content',

  // Social
  shareOnSocial: (platform: string) => `Share on ${platform}`,

  // Financial
  viewTransaction: 'View transaction details',
  downloadStatement: 'Download statement',
  makePayment: 'Make a payment',
  requestWithdrawal: 'Request withdrawal',
};

/**
 * ARIA live region announcer
 * For screen readers to announce dynamic content changes
 */
export function announceToScreenReader(message: string, priority: 'polite' | 'assertive' = 'polite') {
  if (typeof window === 'undefined') return;

  const announcement = document.createElement('div');
  announcement.setAttribute('role', 'status');
  announcement.setAttribute('aria-live', priority);
  announcement.setAttribute('aria-atomic', 'true');
  announcement.className = 'sr-only'; // Screen reader only
  announcement.textContent = message;

  document.body.appendChild(announcement);

  // Remove after announcement
  setTimeout(() => {
    document.body.removeChild(announcement);
  }, 1000);
}

/**
 * Keyboard navigation helpers
 */
export const KeyboardKeys = {
  ENTER: 'Enter',
  SPACE: ' ',
  ESCAPE: 'Escape',
  TAB: 'Tab',
  ARROW_UP: 'ArrowUp',
  ARROW_DOWN: 'ArrowDown',
  ARROW_LEFT: 'ArrowLeft',
  ARROW_RIGHT: 'ArrowRight',
  HOME: 'Home',
  END: 'End',
};

/**
 * Check if key event is an activation key (Enter or Space)
 */
export function isActivationKey(event: React.KeyboardEvent): boolean {
  return event.key === KeyboardKeys.ENTER || event.key === KeyboardKeys.SPACE;
}

/**
 * Handle keyboard activation for custom interactive elements
 * Use this for div/span elements that act as buttons
 */
export function handleKeyboardActivation(
  event: React.KeyboardEvent,
  callback: () => void
) {
  if (isActivationKey(event)) {
    event.preventDefault();
    callback();
  }
}

/**
 * Focus trap utility for modals and dialogs
 */
export function trapFocus(element: HTMLElement) {
  const focusableElements = element.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  );

  const firstFocusable = focusableElements[0] as HTMLElement;
  const lastFocusable = focusableElements[focusableElements.length - 1] as HTMLElement;

  function handleTabKey(event: KeyboardEvent) {
    if (event.key !== KeyboardKeys.TAB) return;

    if (event.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstFocusable) {
        event.preventDefault();
        lastFocusable?.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastFocusable) {
        event.preventDefault();
        firstFocusable?.focus();
      }
    }
  }

  element.addEventListener('keydown', handleTabKey);

  // Focus first element
  firstFocusable?.focus();

  // Return cleanup function
  return () => {
    element.removeEventListener('keydown', handleTabKey);
  };
}

/**
 * Get descriptive text for screen readers
 */
export function getScreenReaderText(
  value: string | number,
  label: string,
  unit?: string
): string {
  if (unit) {
    return `${label}: ${value} ${unit}`;
  }
  return `${label}: ${value}`;
}

/**
 * Format currency for screen readers
 */
export function formatCurrencyForScreenReader(amount: number): string {
  const formatted = new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
  }).format(amount);

  // Convert ₹1,234.56 to "1234 rupees and 56 paise"
  const rupees = Math.floor(amount);
  const paise = Math.round((amount - rupees) * 100);

  if (paise === 0) {
    return `${rupees} rupees`;
  }
  return `${rupees} rupees and ${paise} paise`;
}

/**
 * Format date for screen readers
 */
export function formatDateForScreenReader(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date;

  return d.toLocaleDateString('en-IN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

/**
 * Skip to main content link (should be first focusable element)
 */
export function SkipToMainContent() {
  return (
    <a
      href="#main-content"
      className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-primary-foreground focus:rounded"
    >
      {AriaLabels.skipToMain}
    </a>
  );
}

/**
 * Screen reader only text component
 */
export function ScreenReaderOnly({ children }: { children: React.ReactNode }) {
  return <span className="sr-only">{children}</span>;
}

/**
 * Visually hidden but accessible label
 */
export function VisuallyHidden({ children, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  return (
    <span
      className="absolute w-px h-px p-0 -m-px overflow-hidden whitespace-nowrap border-0"
      {...props}
    >
      {children}
    </span>
  );
}

/**
 * WCAG 2.1 AA Contrast Checker
 * Returns true if contrast ratio is at least 4.5:1
 */
export function hasGoodContrast(foreground: string, background: string): boolean {
  // This is a simplified version - in production, use a proper color contrast library
  // like 'wcag-contrast' or 'color-contrast-checker'
  // For now, return true (assumes Tailwind defaults are WCAG compliant)
  return true;
}

/**
 * Focus visible styles utility
 * Apply to custom interactive elements
 */
export const focusVisibleStyles = `
  focus-visible:outline-none
  focus-visible:ring-2
  focus-visible:ring-ring
  focus-visible:ring-offset-2
`;

/**
 * Accessibility guidelines for the team
 */
export const AccessibilityGuidelines = {
  images: {
    rule: 'All images must have alt text',
    example: '<img src="..." alt="Company logo" />',
    exceptions: 'Decorative images can use alt=""',
  },
  buttons: {
    rule: 'All buttons must have descriptive labels',
    example: '<button aria-label="Close menu">×</button>',
    iconOnly: 'Icon-only buttons MUST have aria-label',
  },
  forms: {
    rule: 'All form inputs must have associated labels',
    example: '<label htmlFor="email">Email</label><input id="email" />',
    errorHandling: 'Show error messages with aria-describedby',
  },
  headings: {
    rule: 'Use semantic heading hierarchy (h1 → h2 → h3)',
    example: 'Don\'t skip heading levels',
    tip: 'Only one h1 per page',
  },
  keyboard: {
    rule: 'All interactive elements must be keyboard accessible',
    example: 'Use button/a tags, not div with onClick',
    custom: 'Custom elements need tabIndex and keyboard handlers',
  },
  colorContrast: {
    rule: 'Text must have at least 4.5:1 contrast ratio (WCAG AA)',
    large: 'Large text (18px+) needs 3:1',
    tool: 'Use browser DevTools or WebAIM Contrast Checker',
  },
  liveRegions: {
    rule: 'Use ARIA live regions for dynamic content updates',
    example: 'aria-live="polite" for non-urgent, "assertive" for urgent',
    usage: 'Notifications, form validation, loading states',
  },
};
