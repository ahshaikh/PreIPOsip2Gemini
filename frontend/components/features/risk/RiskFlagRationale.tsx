'use client';

/**
 * P0 FIX (GAP 35): Risk Flag Rationale Display
 *
 * PURPOSE:
 * Display risk flags WITH explanations to investors.
 * Previously flags were visible but rationale was missing.
 *
 * FEATURES:
 * - Severity-based color coding
 * - Expandable rationale sections
 * - Mitigation guidance display
 * - Category grouping
 */

import { useState } from 'react';
import {
  AlertTriangle,
  AlertCircle,
  Info,
  ChevronDown,
  ChevronUp,
  Shield,
  TrendingDown,
  Scale,
  Settings,
  DollarSign
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { RiskFlag, CompanyRiskAssessment } from '@/types/api';

interface RiskFlagRationaleProps {
  assessment: CompanyRiskAssessment;
  showMitigation?: boolean;
  expandedByDefault?: boolean;
}

interface RiskFlagItemProps {
  flag: RiskFlag;
  showMitigation: boolean;
  expandedByDefault: boolean;
}

/**
 * Get severity color classes
 */
function getSeverityStyles(severity: RiskFlag['severity']) {
  switch (severity) {
    case 'critical':
      return {
        bg: 'bg-red-50 dark:bg-red-950/30',
        border: 'border-red-300 dark:border-red-800',
        text: 'text-red-800 dark:text-red-200',
        badge: 'destructive' as const,
        icon: AlertTriangle,
      };
    case 'high':
      return {
        bg: 'bg-orange-50 dark:bg-orange-950/30',
        border: 'border-orange-300 dark:border-orange-800',
        text: 'text-orange-800 dark:text-orange-200',
        badge: 'warning' as const,
        icon: AlertCircle,
      };
    case 'medium':
      return {
        bg: 'bg-amber-50 dark:bg-amber-950/30',
        border: 'border-amber-300 dark:border-amber-800',
        text: 'text-amber-800 dark:text-amber-200',
        badge: 'warning' as const,
        icon: AlertCircle,
      };
    case 'low':
    default:
      return {
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        border: 'border-blue-300 dark:border-blue-800',
        text: 'text-blue-800 dark:text-blue-200',
        badge: 'secondary' as const,
        icon: Info,
      };
  }
}

/**
 * Get category icon
 */
function getCategoryIcon(category: RiskFlag['category']) {
  switch (category) {
    case 'market':
      return TrendingDown;
    case 'liquidity':
      return DollarSign;
    case 'regulatory':
      return Scale;
    case 'operational':
      return Settings;
    case 'financial':
      return DollarSign;
    default:
      return Shield;
  }
}

/**
 * Individual Risk Flag Item
 */
function RiskFlagItem({ flag, showMitigation, expandedByDefault }: RiskFlagItemProps) {
  const [isExpanded, setIsExpanded] = useState(expandedByDefault);
  const styles = getSeverityStyles(flag.severity);
  const SeverityIcon = styles.icon;
  const CategoryIcon = getCategoryIcon(flag.category);

  return (
    <div className={`rounded-lg border ${styles.border} ${styles.bg} p-4`}>
      {/* Header */}
      <div
        className="flex items-start justify-between cursor-pointer"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="flex items-start gap-3">
          <SeverityIcon className={`h-5 w-5 mt-0.5 ${styles.text}`} />
          <div>
            <div className="flex items-center gap-2">
              <span className={`font-medium ${styles.text}`}>{flag.name}</span>
              <Badge variant={styles.badge} className="text-xs">
                {flag.severity.toUpperCase()}
              </Badge>
            </div>
            <div className="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
              <CategoryIcon className="h-3 w-3" />
              <span className="capitalize">{flag.category} Risk</span>
              <span className="text-muted-foreground/50">|</span>
              <code className="text-xs bg-muted px-1 rounded">{flag.code}</code>
            </div>
          </div>
        </div>
        <button
          className={`p-1 rounded hover:bg-muted/50 ${styles.text}`}
          aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
        >
          {isExpanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
        </button>
      </div>

      {/* Expanded Content */}
      {isExpanded && (
        <div className="mt-4 pt-4 border-t border-current/10 space-y-3">
          {/* Rationale - GAP 35 FIX: This was missing before */}
          <div>
            <h4 className={`text-xs font-semibold uppercase tracking-wide ${styles.text} mb-1`}>
              Why This Risk Exists
            </h4>
            <p className={`text-sm ${styles.text}`}>
              {flag.rationale || 'No rationale provided.'}
            </p>
          </div>

          {/* Mitigation Guidance */}
          {showMitigation && flag.mitigation_guidance && (
            <div>
              <h4 className={`text-xs font-semibold uppercase tracking-wide ${styles.text} mb-1`}>
                What This Means For You
              </h4>
              <p className={`text-sm ${styles.text}`}>
                {flag.mitigation_guidance}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

/**
 * Overall Risk Level Banner
 */
function OverallRiskBanner({ level, score }: { level: string; score: number }) {
  const styles = getSeverityStyles(level as RiskFlag['severity']);
  const Icon = styles.icon;

  return (
    <div className={`rounded-lg border-2 ${styles.border} ${styles.bg} p-4 mb-6`}>
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Icon className={`h-6 w-6 ${styles.text}`} />
          <div>
            <p className={`font-semibold ${styles.text}`}>
              Overall Risk Level: <span className="uppercase">{level}</span>
            </p>
            <p className="text-xs text-muted-foreground">
              Based on {score > 0 ? 'active risk assessment' : 'current market conditions'}
            </p>
          </div>
        </div>
        <div className={`text-2xl font-bold ${styles.text}`}>
          {score}/100
        </div>
      </div>
    </div>
  );
}

/**
 * Main Risk Flag Rationale Component
 * GAP 35 FIX: Displays risk flags WITH explanations
 */
export function RiskFlagRationale({
  assessment,
  showMitigation = true,
  expandedByDefault = false
}: RiskFlagRationaleProps) {
  const activeFlags = assessment.flags.filter(f => f.is_active);

  // Group flags by category
  const flagsByCategory = activeFlags.reduce((acc, flag) => {
    if (!acc[flag.category]) {
      acc[flag.category] = [];
    }
    acc[flag.category].push(flag);
    return acc;
  }, {} as Record<string, RiskFlag[]>);

  if (activeFlags.length === 0) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5 text-green-600" />
            Risk Assessment
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="bg-green-50 dark:bg-green-950/30 border border-green-300 dark:border-green-800 rounded-lg p-4">
            <p className="text-green-800 dark:text-green-200">
              No active risk flags for this investment. However, all investments carry inherent risk.
              Please review the full disclosure documents before investing.
            </p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <AlertTriangle className="h-5 w-5 text-amber-600" />
          Risk Assessment for {assessment.company_name}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Overall Risk Banner */}
        <OverallRiskBanner
          level={assessment.overall_risk_level}
          score={assessment.risk_score}
        />

        {/* Risk Flags by Category */}
        {Object.entries(flagsByCategory).map(([category, flags]) => (
          <div key={category}>
            <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3 flex items-center gap-2">
              {(() => {
                const CategoryIcon = getCategoryIcon(category as RiskFlag['category']);
                return <CategoryIcon className="h-4 w-4" />;
              })()}
              {category} Risks ({flags.length})
            </h3>
            <div className="space-y-3">
              {flags.map((flag) => (
                <RiskFlagItem
                  key={flag.id}
                  flag={flag}
                  showMitigation={showMitigation}
                  expandedByDefault={expandedByDefault}
                />
              ))}
            </div>
          </div>
        ))}

        {/* Assessment Timestamp */}
        <div className="text-xs text-muted-foreground pt-4 border-t">
          <p>
            Last assessed: {new Date(assessment.last_assessed_at).toLocaleString()}
          </p>
          {assessment.assessor_notes && (
            <p className="mt-1 italic">Note: {assessment.assessor_notes}</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Compact Risk Summary (for inline use)
 */
export function RiskFlagSummary({
  flags,
  onViewDetails
}: {
  flags: RiskFlag[];
  onViewDetails?: () => void;
}) {
  const activeFlags = flags.filter(f => f.is_active);
  const criticalCount = activeFlags.filter(f => f.severity === 'critical').length;
  const highCount = activeFlags.filter(f => f.severity === 'high').length;

  if (activeFlags.length === 0) {
    return (
      <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
        <Shield className="h-4 w-4" />
        <span>No active risk flags</span>
      </div>
    );
  }

  return (
    <div
      className="flex items-center gap-2 cursor-pointer hover:opacity-80"
      onClick={onViewDetails}
    >
      {criticalCount > 0 && (
        <Badge variant="destructive" className="text-xs">
          {criticalCount} Critical
        </Badge>
      )}
      {highCount > 0 && (
        <Badge variant="warning" className="text-xs">
          {highCount} High
        </Badge>
      )}
      {activeFlags.length - criticalCount - highCount > 0 && (
        <Badge variant="secondary" className="text-xs">
          {activeFlags.length - criticalCount - highCount} Other
        </Badge>
      )}
      {onViewDetails && (
        <ChevronDown className="h-4 w-4 text-muted-foreground" />
      )}
    </div>
  );
}
