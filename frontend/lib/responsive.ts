/**
 * Responsive Design Utilities
 * Consistent breakpoints and responsive helpers
 */

/**
 * Standard breakpoints (matching Tailwind CSS defaults)
 * Use these values for consistency across the application
 */
export const Breakpoints = {
  sm: 640,   // Small devices (landscape phones)
  md: 768,   // Medium devices (tablets)
  lg: 1024,  // Large devices (desktops)
  xl: 1280,  // Extra large devices (large desktops)
  '2xl': 1536, // 2X Extra large devices
} as const;

export type BreakpointKey = keyof typeof Breakpoints;

/**
 * Get current breakpoint
 * Returns the current active breakpoint based on window width
 */
export function getCurrentBreakpoint(): BreakpointKey | 'xs' {
  if (typeof window === 'undefined') return 'xs';

  const width = window.innerWidth;

  if (width >= Breakpoints['2xl']) return '2xl';
  if (width >= Breakpoints.xl) return 'xl';
  if (width >= Breakpoints.lg) return 'lg';
  if (width >= Breakpoints.md) return 'md';
  if (width >= Breakpoints.sm) return 'sm';
  return 'xs';
}

/**
 * Check if current viewport matches breakpoint
 */
export function isBreakpoint(breakpoint: BreakpointKey): boolean {
  if (typeof window === 'undefined') return false;
  return window.innerWidth >= Breakpoints[breakpoint];
}

/**
 * Check if viewport is mobile (< md)
 */
export function isMobile(): boolean {
  if (typeof window === 'undefined') return false;
  return window.innerWidth < Breakpoints.md;
}

/**
 * Check if viewport is tablet (md to lg)
 */
export function isTablet(): boolean {
  if (typeof window === 'undefined') return false;
  const width = window.innerWidth;
  return width >= Breakpoints.md && width < Breakpoints.lg;
}

/**
 * Check if viewport is desktop (>= lg)
 */
export function isDesktop(): boolean {
  if (typeof window === 'undefined') return false;
  return window.innerWidth >= Breakpoints.lg;
}

/**
 * Responsive value selector
 * Returns different values based on current breakpoint
 *
 * @example
 * const columns = getResponsiveValue({ xs: 1, md: 2, lg: 3 });
 */
export function getResponsiveValue<T>(values: {
  xs?: T;
  sm?: T;
  md?: T;
  lg?: T;
  xl?: T;
  '2xl'?: T;
}): T | undefined {
  const current = getCurrentBreakpoint();

  // Try exact match first
  if (values[current] !== undefined) return values[current];

  // Fall back to nearest lower breakpoint
  const breakpointOrder: (BreakpointKey | 'xs')[] = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];
  const currentIndex = breakpointOrder.indexOf(current);

  for (let i = currentIndex - 1; i >= 0; i--) {
    const key = breakpointOrder[i];
    if (values[key as keyof typeof values] !== undefined) {
      return values[key as keyof typeof values];
    }
  }

  return undefined;
}

/**
 * Common responsive class patterns
 * Use these for consistent responsive design
 */
export const ResponsivePatterns = {
  // Grid columns
  gridCols: {
    default: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    cards: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    dashboard: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    list: 'grid-cols-1',
  },

  // Spacing
  padding: {
    page: 'px-4 sm:px-6 lg:px-8',
    section: 'py-8 sm:py-12 lg:py-16',
    card: 'p-4 sm:p-6',
  },

  // Text sizes
  heading: {
    h1: 'text-3xl sm:text-4xl lg:text-5xl',
    h2: 'text-2xl sm:text-3xl lg:text-4xl',
    h3: 'text-xl sm:text-2xl lg:text-3xl',
    h4: 'text-lg sm:text-xl lg:text-2xl',
  },

  // Container
  container: 'container mx-auto px-4 sm:px-6 lg:px-8',

  // Flex
  flex: {
    column: 'flex flex-col',
    row: 'flex flex-col md:flex-row',
    rowReverse: 'flex flex-col-reverse md:flex-row',
  },

  // Gap
  gap: {
    small: 'gap-2 sm:gap-3 lg:gap-4',
    medium: 'gap-4 sm:gap-6 lg:gap-8',
    large: 'gap-6 sm:gap-8 lg:gap-12',
  },

  // Width
  width: {
    prose: 'w-full max-w-prose',
    container: 'w-full max-w-7xl mx-auto',
    narrow: 'w-full max-w-2xl mx-auto',
    wide: 'w-full max-w-screen-2xl mx-auto',
  },
};

/**
 * Media query hooks (for use with window.matchMedia)
 */
export const MediaQueries = {
  sm: `(min-width: ${Breakpoints.sm}px)`,
  md: `(min-width: ${Breakpoints.md}px)`,
  lg: `(min-width: ${Breakpoints.lg}px)`,
  xl: `(min-width: ${Breakpoints.xl}px)`,
  '2xl': `(min-width: ${Breakpoints['2xl']}px)`,
  mobile: `(max-width: ${Breakpoints.md - 1}px)`,
  tablet: `(min-width: ${Breakpoints.md}px) and (max-width: ${Breakpoints.lg - 1}px)`,
  desktop: `(min-width: ${Breakpoints.lg}px)`,
  darkMode: '(prefers-color-scheme: dark)',
  reducedMotion: '(prefers-reduced-motion: reduce)',
} as const;

/**
 * Responsive image sizes
 * For use with Next.js Image component
 */
export const ImageSizes = {
  thumbnail: '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw',
  card: '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 350px',
  hero: '100vw',
  avatar: '(max-width: 640px) 48px, (max-width: 1024px) 64px, 96px',
};

/**
 * Responsive font sizes (in pixels)
 */
export const FontSizes = {
  xs: { base: 12, sm: 12, md: 12, lg: 12 },
  sm: { base: 14, sm: 14, md: 14, lg: 14 },
  base: { base: 16, sm: 16, md: 16, lg: 16 },
  lg: { base: 18, sm: 18, md: 18, lg: 20 },
  xl: { base: 20, sm: 20, md: 24, lg: 24 },
  '2xl': { base: 24, sm: 24, md: 28, lg: 30 },
  '3xl': { base: 28, sm: 30, md: 34, lg: 36 },
  '4xl': { base: 32, sm: 36, md: 40, lg: 48 },
  '5xl': { base: 36, sm: 48, md: 56, lg: 64 },
};

/**
 * Common layout patterns
 */
export const LayoutPatterns = {
  // Centered content
  centered: 'flex items-center justify-center min-h-screen',

  // Two column layout
  twoColumn: 'grid grid-cols-1 lg:grid-cols-12 gap-6',
  twoColumnMain: 'lg:col-span-8',
  twoColumnSidebar: 'lg:col-span-4',

  // Three column layout
  threeColumn: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',

  // Dashboard layout
  dashboard: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4',

  // Form layout
  formGrid: 'grid grid-cols-1 md:grid-cols-2 gap-4',
  formFull: 'col-span-1 md:col-span-2',

  // Card grid
  cardGrid: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6',
};

/**
 * Responsive design best practices
 */
export const ResponsiveGuidelines = {
  breakpoints: {
    rule: 'Always use mobile-first approach',
    example: 'text-base md:text-lg (not lg:text-lg md:text-base)',
    tip: 'Default styles apply to mobile, use breakpoint prefixes for larger screens',
  },
  images: {
    rule: 'Use Next.js Image component with responsive sizes',
    example: '<Image sizes="(max-width: 768px) 100vw, 50vw" />',
    tip: 'Provide multiple image sizes for optimal performance',
  },
  text: {
    rule: 'Scale font sizes responsively',
    example: 'text-2xl md:text-3xl lg:text-4xl',
    tip: 'Headings should get larger on bigger screens',
  },
  spacing: {
    rule: 'Increase spacing on larger screens',
    example: 'gap-4 md:gap-6 lg:gap-8',
    tip: 'More whitespace improves readability on desktop',
  },
  layout: {
    rule: 'Stack on mobile, side-by-side on desktop',
    example: 'flex-col md:flex-row',
    tip: 'Use grid for complex layouts, flex for simple ones',
  },
  testing: {
    rule: 'Test on real devices, not just browser DevTools',
    devices: ['iPhone SE', 'iPhone 14', 'iPad', 'Desktop 1920x1080'],
    tip: 'Pay special attention to tablet breakpoint (often overlooked)',
  },
};

/**
 * Utility to get responsive class string
 */
export function cn(...classes: (string | undefined | null | false)[]): string {
  return classes.filter(Boolean).join(' ');
}

/**
 * Generate responsive grid columns class
 */
export function responsiveGridCols(mobile: number, tablet: number, desktop: number): string {
  return `grid-cols-${mobile} md:grid-cols-${tablet} lg:grid-cols-${desktop}`;
}

/**
 * Generate responsive gap class
 */
export function responsiveGap(mobile: number, tablet: number, desktop: number): string {
  return `gap-${mobile} md:gap-${tablet} lg:gap-${desktop}`;
}
