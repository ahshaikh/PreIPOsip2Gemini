/**
 * Bonus Calculations Library
 *
 * Client-side bonus calculation functions for preview and estimation.
 * Mirrors the backend BonusCalculatorService logic for UI previews.
 *
 * Types are re-exported from the canonical plan types module.
 */

// Re-export config types from canonical source
export type {
  ProgressiveConfig,
  MilestoneEntry,
  ConsistencyConfig,
  WelcomeBonusConfig,
  StreakRule,
} from '@/types/plan';

// Local alias for backward compatibility
import type {
  ProgressiveConfig,
  MilestoneEntry,
  ConsistencyConfig,
  WelcomeBonusConfig,
} from '@/types/plan';

/**
 * @deprecated Use MilestoneEntry[] instead
 * Preserved for backward compatibility with existing code
 */
export type MilestoneConfig = MilestoneEntry;

/**
 * Calculate progressive bonus for a specific month
 */
export function calculateProgressiveBonus(
  month: number,
  paymentAmount: number,
  config: ProgressiveConfig,
  multiplier: number = 1.0
): number {
  if (month < config.start_month) return 0;

  let baseRate = 0;

  // Check for month-specific override
  if (config.overrides && config.overrides[month]) {
    baseRate = config.overrides[month];
  } else {
    // Calculate progressive rate
    const growthFactor = month - config.start_month + 1;
    baseRate = growthFactor * config.rate;
  }

  // Apply max percentage cap
  if (baseRate > config.max_percentage) {
    baseRate = config.max_percentage;
  }

  const bonus = (baseRate / 100) * paymentAmount * multiplier;
  return Math.round(bonus * 100) / 100; // Round to 2 decimal places
}

/**
 * Get progressive bonus for all months
 */
export function getProgressiveBonusSchedule(
  paymentAmount: number,
  durationMonths: number,
  config: ProgressiveConfig,
  multiplier: number = 1.0
): Array<{ month: number; rate: number; bonus: number }> {
  const schedule = [];

  for (let month = 1; month <= durationMonths; month++) {
    const bonus = calculateProgressiveBonus(month, paymentAmount, config, multiplier);

    let rate = 0;
    if (month >= config.start_month) {
      if (config.overrides && config.overrides[month]) {
        rate = config.overrides[month];
      } else {
        const growthFactor = month - config.start_month + 1;
        rate = Math.min(growthFactor * config.rate, config.max_percentage);
      }
    }

    schedule.push({ month, rate, bonus });
  }

  return schedule;
}

/**
 * Calculate milestone bonus for a specific month
 */
export function calculateMilestoneBonus(
  month: number,
  milestones: MilestoneConfig[],
  multiplier: number = 1.0
): number {
  const milestone = milestones.find(m => m.month === month);
  if (!milestone) return 0;

  return milestone.amount * multiplier;
}

/**
 * Calculate consistency bonus with streak multiplier
 */
export function calculateConsistencyBonus(
  currentStreak: number,
  config: ConsistencyConfig
): number {
  let bonus = config.amount_per_payment;

  // Apply streak multiplier if applicable
  if (config.streaks && config.streaks.length > 0) {
    // Find the highest applicable streak
    const applicableStreaks = config.streaks
      .filter(s => currentStreak >= s.months)
      .sort((a, b) => b.months - a.months);

    if (applicableStreaks.length > 0) {
      bonus *= applicableStreaks[0].multiplier;
    }
  }

  return Math.round(bonus * 100) / 100;
}

/**
 * Calculate total bonuses for entire plan duration
 */
export function calculateTotalBonuses(
  paymentAmount: number,
  durationMonths: number,
  configs: {
    welcome?: WelcomeBonusConfig;
    progressive?: ProgressiveConfig;
    milestones?: MilestoneConfig[];
    consistency?: ConsistencyConfig;
  },
  multiplier: number = 1.0
): {
  totalBonus: number;
  breakdown: {
    welcome: number;
    progressive: number;
    milestone: number;
    consistency: number;
  };
  monthlySchedule: Array<{
    month: number;
    progressive: number;
    milestone: number;
    consistency: number;
    total: number;
  }>;
} {
  const welcomeTotal = configs.welcome?.amount || 0;
  let progressiveTotal = 0;
  let milestoneTotal = 0;
  let consistencyTotal = 0;

  const monthlySchedule = [];

  for (let month = 1; month <= durationMonths; month++) {
    const progressive = configs.progressive
      ? calculateProgressiveBonus(month, paymentAmount, configs.progressive, multiplier)
      : 0;

    const milestone = configs.milestones
      ? calculateMilestoneBonus(month, configs.milestones, multiplier)
      : 0;

    const consistency = configs.consistency
      ? calculateConsistencyBonus(month, configs.consistency)
      : 0;

    progressiveTotal += progressive;
    milestoneTotal += milestone;
    consistencyTotal += consistency;

    monthlySchedule.push({
      month,
      progressive,
      milestone,
      consistency,
      total: progressive + milestone + consistency,
    });
  }

  return {
    totalBonus: welcomeTotal + progressiveTotal + milestoneTotal + consistencyTotal,
    breakdown: {
      welcome: welcomeTotal,
      progressive: progressiveTotal,
      milestone: milestoneTotal,
      consistency: consistencyTotal,
    },
    monthlySchedule,
  };
}

/**
 * Format currency for display in Indian Rupees.
 * Uses Intl.NumberFormat for proper locale-aware formatting.
 */
export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

/**
 * Validate progressive config
 */
export function validateProgressiveConfig(config: Partial<ProgressiveConfig>): string[] {
  const errors: string[] = [];

  if (config.rate !== undefined && (config.rate < 0 || config.rate > 100)) {
    errors.push('Rate must be between 0 and 100%');
  }

  if (config.start_month !== undefined && config.start_month < 1) {
    errors.push('Start month must be at least 1');
  }

  if (config.max_percentage !== undefined && (config.max_percentage < 0 || config.max_percentage > 100)) {
    errors.push('Max percentage must be between 0 and 100%');
  }

  if (config.overrides) {
    Object.entries(config.overrides).forEach(([month, rate]) => {
      if (rate < 0 || rate > 100) {
        errors.push(`Override for month ${month} must be between 0 and 100%`);
      }
    });
  }

  return errors;
}

/**
 * Validate milestone config
 */
export function validateMilestoneConfig(milestones: MilestoneConfig[]): string[] {
  const errors: string[] = [];

  // Check for duplicate months
  const months = milestones.map(m => m.month);
  const duplicates = months.filter((month, index) => months.indexOf(month) !== index);
  if (duplicates.length > 0) {
    errors.push(`Duplicate milestone months: ${duplicates.join(', ')}`);
  }

  // Validate amounts
  milestones.forEach((milestone, index) => {
    if (milestone.month < 1) {
      errors.push(`Milestone ${index + 1}: Month must be at least 1`);
    }
    if (milestone.amount < 0) {
      errors.push(`Milestone ${index + 1}: Amount must be positive`);
    }
  });

  return errors;
}

/**
 * Validate consistency config
 */
export function validateConsistencyConfig(config: Partial<ConsistencyConfig>): string[] {
  const errors: string[] = [];

  if (config.amount_per_payment !== undefined && config.amount_per_payment < 0) {
    errors.push('Amount per payment must be positive');
  }

  if (config.streaks) {
    // Check for duplicate streak months
    const months = config.streaks.map(s => s.months);
    const duplicates = months.filter((month, index) => months.indexOf(month) !== index);
    if (duplicates.length > 0) {
      errors.push(`Duplicate streak months: ${duplicates.join(', ')}`);
    }

    // Validate multipliers
    config.streaks.forEach((streak, index) => {
      if (streak.months < 1) {
        errors.push(`Streak ${index + 1}: Months must be at least 1`);
      }
      if (streak.multiplier < 1) {
        errors.push(`Streak ${index + 1}: Multiplier must be at least 1.0`);
      }
    });
  }

  return errors;
}
