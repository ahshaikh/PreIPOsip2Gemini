/**
 * Company Disclosures Page - Redesigned
 *
 * DESIGN PRINCIPLES:
 * - Preserve founder dignity with respectful language
 * - Present compliance as collaborative, audit-ready process
 * - Progress based on fulfilled requirements, not uploads
 * - Timeline-style disclosure threads (like PR reviews)
 * - Clear governance status context
 *
 * LANGUAGE RULES:
 * - "Action Requested" not "Rejected"
 * - "Clarification Requested" not "Needs Fixing"
 * - "Pending Review" not "Waiting"
 * - Professional, calm, collaborative tone
 */

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  Building2,
  FileText,
  Clock,
  CheckCircle2,
  AlertCircle,
  Shield,
  TrendingUp,
  Eye,
  MessageSquare,
  Loader2,
  ChevronRight,
  Info,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import {
  fetchIssuerCompany,
  IssuerCompanyData,
} from "@/lib/issuerCompanyApi";
import { normalizeIssuerCompanyData } from "@/lib/dataNomalizer";
import companyApi from "@/lib/companyApi";
import {
  PlatformStatusBanner,
  hasPlatformRestriction,
} from "@/components/issuer/PlatformStatusBanner";

// Types
// Backend provides authoritative disclosure requirement taxonomy
// Each requirement includes:
// - category (backend-provided, no frontend inference)
// - tier (minimum tier requirement)
// - is_required (whether it's mandatory)
// - status (including "not_started" when no thread exists)
interface DisclosureRequirement {
  id: number | null; // null when status is "not_started"
  module_id: number;
  module_name: string;
  category: 'governance' | 'financial' | 'legal' | 'operational'; // Backend-provided
  tier: number; // Backend-provided minimum tier requirement
  description?: string;
  status: string; // Backend normalizes to "not_started" when no thread exists
  can_edit: boolean;
  can_submit: boolean;
  rejection_reason?: string | null;
  corrective_guidance?: string | null;
  data: any;
  last_updated?: string;
  is_required: boolean; // Backend-provided
  required_for_tier?: number; // Same as tier, kept for compatibility
}

// Disclosure category configuration
const DISCLOSURE_CATEGORIES = {
  governance: {
    label: "Governance",
    icon: Shield,
    description: "Board structure, policies, and decision-making processes",
    color: "text-blue-600",
    bgColor: "bg-blue-50",
    borderColor: "border-blue-200",
  },
  financial: {
    label: "Financial",
    icon: TrendingUp,
    description: "Financial statements, funding, and projections",
    color: "text-green-600",
    bgColor: "bg-green-50",
    borderColor: "border-green-200",
  },
  legal: {
    label: "Legal & Risk",
    icon: FileText,
    description: "Legal compliance, contracts, and risk management",
    color: "text-purple-600",
    bgColor: "bg-purple-50",
    borderColor: "border-purple-200",
  },
  operational: {
    label: "Operational",
    icon: Building2,
    description: "Business operations, team, and infrastructure",
    color: "text-orange-600",
    bgColor: "bg-orange-50",
    borderColor: "border-orange-200",
  },
};

export default function IssuerDisclosuresPage() {
  const [company, setCompany] = useState<IssuerCompanyData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedCategory, setSelectedCategory] = useState<string>("all");

  // Risk 2: Track progress changes for narrative fallback
  const [previousProgress, setPreviousProgress] = useState<{
    percentage: number;
    total: number;
    tier: number;
  } | null>(null);
  const [progressChangeReason, setProgressChangeReason] = useState<string | null>(null);

  // Load issuer company data
  useEffect(() => {
    async function loadCompanyData() {
      setLoading(true);
      setError(null);

      try {
        const rawData = await fetchIssuerCompany();
        const normalizedData = normalizeIssuerCompanyData(rawData);
        setCompany(normalizedData);
      } catch (err: any) {
        console.error("[ISSUER DISCLOSURES] Failed to load:", err);
        setError("Unable to load company data. Please try again later.");
        toast.error("Failed to load data");
      } finally {
        setLoading(false);
      }
    }

    loadCompanyData();
  }, []);

  // Risk 2 Fix: Detect progress changes and provide narrative
  useEffect(() => {
    if (!company) return;

    const currentProgress = getRequirementCompletion();

    // Load previous progress from localStorage
    const storageKey = `disclosure_progress_${company.id}`;
    const stored = localStorage.getItem(storageKey);

    if (stored) {
      const previous = JSON.parse(stored);

      // Detect significant changes
      if (previous.tier !== currentProgress.tier) {
        // Tier changed
        if (currentProgress.tier > previous.tier) {
          setProgressChangeReason(
            `You've advanced to Tier ${currentProgress.tier}! New disclosure requirements have been added.`
          );
        } else if (currentProgress.tier < previous.tier) {
          setProgressChangeReason(
            `Your tier status changed to Tier ${currentProgress.tier}. Requirement count has been updated.`
          );
        }
      } else if (currentProgress.total !== previous.total) {
        // Same tier but total changed
        if (currentProgress.total > previous.total) {
          const newCount = currentProgress.total - previous.total;
          setProgressChangeReason(
            `${newCount} new disclosure ${newCount === 1 ? 'requirement was' : 'requirements were'} added to your current tier.`
          );
        } else {
          const removedCount = previous.total - currentProgress.total;
          setProgressChangeReason(
            `${removedCount} disclosure ${removedCount === 1 ? 'requirement was' : 'requirements were'} removed or reclassified.`
          );
        }
      } else if (Math.abs(currentProgress.percentage - previous.percentage) >= 10) {
        // Significant percentage change without total change (completions)
        if (currentProgress.percentage > previous.percentage) {
          setProgressChangeReason(
            `Great progress! You've completed ${currentProgress.completed - previous.completed} more ${
              currentProgress.completed - previous.completed === 1 ? 'requirement' : 'requirements'
            }.`
          );
        }
      }

      setPreviousProgress(previous);
    }

    // Save current progress
    localStorage.setItem(
      storageKey,
      JSON.stringify({
        percentage: currentProgress.percentage,
        total: currentProgress.total,
        tier: currentProgress.tier,
        completed: currentProgress.completed,
      })
    );
  }, [company]);

  // Get respectful status badge (no "rejected" language)
  const getStatusBadge = (status: string) => {
    switch (status) {
      case "not_started":
        return (
          <Badge variant="outline" className="text-gray-600 border-gray-300 bg-gray-50">
            <FileText className="w-3 h-3 mr-1" />
            Not Started
          </Badge>
        );
      case "draft":
        return (
          <Badge variant="outline" className="text-gray-600 border-gray-300 bg-gray-50">
            <Clock className="w-3 h-3 mr-1" />
            Draft
          </Badge>
        );
      case "submitted":
      case "under_review":
        return (
          <Badge variant="outline" className="text-blue-600 border-blue-300 bg-blue-50">
            <Clock className="w-3 h-3 mr-1" />
            Pending Review
          </Badge>
        );
      case "approved":
        return (
          <Badge variant="outline" className="text-green-600 border-green-300 bg-green-50">
            <CheckCircle2 className="w-3 h-3 mr-1" />
            Approved
          </Badge>
        );
      case "rejected":
      case "clarification_required":
        return (
          <Badge variant="outline" className="text-amber-600 border-amber-300 bg-amber-50">
            <MessageSquare className="w-3 h-3 mr-1" />
            Action Requested
          </Badge>
        );
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  // Tier-aware requirement calculation using backend-provided taxonomy
  const getRequirementCompletion = () => {
    if (!company) return { completed: 0, total: 0, percentage: 0, tier: 0 };

    const currentTier = getTierInfo().current;

    // Backend provides tier for each requirement - filter by current tier
    const requiredDisclosures = company.disclosures.filter((d: any) => {
      // Must be required
      if (!d.is_required) return false;

      // Must be for current tier or lower
      return d.tier <= currentTier;
    });

    const completedDisclosures = requiredDisclosures.filter((d: any) => d.status === "approved");

    return {
      completed: completedDisclosures.length,
      total: requiredDisclosures.length,
      percentage: requiredDisclosures.length > 0
        ? Math.round((completedDisclosures.length / requiredDisclosures.length) * 100)
        : 0,
      tier: currentTier,
    };
  };

  // Get governance lifecycle label
  const getGovernanceStatusLabel = (lifecycleState: string) => {
    const labels: Record<string, string> = {
      'draft': 'Pre-Investment',
      'active': 'Investment Enabled',
      'full_transparency': 'Full Transparency',
      'ipo_ready': 'IPO Ready',
      'unknown': 'Getting Started',
    };
    return labels[lifecycleState] || lifecycleState;
  };

  // Get current and next tier
  const getTierInfo = () => {
    if (!company) return { current: 0, next: 1 };

    const tierStatus = company.platform_context?.tier_status || {};
    let current = 0;

    if (tierStatus.tier_3_approved) current = 3;
    else if (tierStatus.tier_2_approved) current = 2;
    else if (tierStatus.tier_1_approved) current = 1;

    return {
      current,
      next: current < 3 ? current + 1 : null,
    };
  };

  // Group disclosure requirements by category (using backend-provided taxonomy)
  const groupDisclosuresByCategory = () => {
    if (!company) return {};

    const grouped: Record<string, DisclosureRequirement[]> = {
      governance: [],
      financial: [],
      legal: [],
      operational: [],
    };

    // Backend provides authoritative category for each disclosure
    company.disclosures.forEach((disclosure: any) => {
      const category = disclosure.category || 'operational'; // Fallback to operational if missing

      grouped[category].push({
        ...disclosure,
        category,
        is_required: disclosure.is_required,
        required_for_tier: disclosure.tier, // Backend provides tier field
      });
    });

    return grouped;
  };

  // Loading state
  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-blue-600" />
          <p className="text-gray-600">Loading disclosure status...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error || !company) {
    return (
      <div className="container py-20">
        <Alert variant="destructive">
          <AlertCircle className="h-5 w-5" />
          <AlertTitle>Unable to Load Data</AlertTitle>
          <AlertDescription>{error || "Company not found"}</AlertDescription>
        </Alert>
      </div>
    );
  }

  const requirementCompletion = getRequirementCompletion();
  const tierInfo = getTierInfo();
  const groupedDisclosures = groupDisclosuresByCategory();
  const lifecycleState = company.platform_context?.lifecycle_state || 'unknown';

  return (
    <div className="container py-8 space-y-8">
      {/* 1. Platform Governance Overview */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold">Disclosure Management</h1>
            <p className="text-gray-600 mt-1">
              Collaborative review process for investment readiness
            </p>
          </div>
          <Badge variant="outline" className="px-4 py-2 text-sm">
            Tier {tierInfo.current || 'Getting Started'}
          </Badge>
        </div>

        {/* Platform Status Banner */}
        <PlatformStatusBanner
          platformContext={company.platform_context}
          effectivePermissions={company.effective_permissions}
          platformOverrides={company.platform_overrides}
          variant="full"
        />

        {/* Governance Status Card */}
        <Card className="border-blue-200 bg-blue-50/50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-4">
              <div className="p-3 rounded-lg bg-blue-100">
                <Shield className="w-6 h-6 text-blue-600" />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h3 className="font-semibold text-lg">
                    {getGovernanceStatusLabel(lifecycleState)}
                  </h3>
                  {tierInfo.next && (
                    <>
                      <ChevronRight className="w-4 h-4 text-gray-400" />
                      <span className="text-sm text-gray-600">
                        Progress toward Tier {tierInfo.next}
                      </span>
                    </>
                  )}
                </div>
                <p className="text-sm text-gray-600">
                  {tierInfo.current === 0 && "Complete required disclosures to enable platform features"}
                  {tierInfo.current === 1 && "Tier 1 complete. Continue to Tier 2 for investor access"}
                  {tierInfo.current === 2 && "Tier 2 complete. Tier 3 provides full transparency"}
                  {tierInfo.current === 3 && "All disclosure requirements met. Platform fully enabled"}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* 2. Disclosure Completion Indicator */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Tier Readiness (Current Requirements)</CardTitle>
              <CardDescription className="mt-1 flex items-center gap-1">
                <Info className="w-3 h-3" />
                Based on disclosures required for Tier {requirementCompletion.tier}
              </CardDescription>
            </div>
            <div className="text-right">
              <div className="text-2xl font-bold">{requirementCompletion.percentage}%</div>
              <div className="text-sm text-gray-600">
                {requirementCompletion.completed} of {requirementCompletion.total} completed
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-3">
          <Progress value={requirementCompletion.percentage} className="h-3" />

          {/* Risk 2: Narrative fallback for progress changes */}
          {progressChangeReason && (
            <Alert className="border-blue-300 bg-blue-50">
              <Info className="h-4 w-4 text-blue-600" />
              <AlertDescription className="text-blue-900">
                {progressChangeReason}
              </AlertDescription>
            </Alert>
          )}

          <p className="text-xs text-gray-500">
            Progress reflects approved disclosure requirements, not document count
          </p>
        </CardContent>
      </Card>

      {/* 3. Disclosure Requirements by Category */}
      <div className="space-y-6">
        <div>
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-2xl font-bold">Disclosure Requirements</h2>
            <Tabs value={selectedCategory} onValueChange={setSelectedCategory}>
              <TabsList>
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="governance">Governance</TabsTrigger>
                <TabsTrigger value="financial">Financial</TabsTrigger>
                <TabsTrigger value="legal">Legal & Risk</TabsTrigger>
                <TabsTrigger value="operational">Operational</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
          <p className="text-sm text-gray-600">
            All disclosure requirements for your company, organized by category. Start with required items to progress through platform tiers.
          </p>
        </div>

        {/* Show disclosure requirements */}
        {Object.entries(groupedDisclosures).map(([category, requirements]) => {
          if (selectedCategory !== "all" && selectedCategory !== category) return null;
          if (requirements.length === 0) return null;

          const categoryConfig = DISCLOSURE_CATEGORIES[category as keyof typeof DISCLOSURE_CATEGORIES];
          const CategoryIcon = categoryConfig.icon;

          return (
            <div key={category} className="space-y-4">
              <div className="flex items-center gap-3">
                <div className={`p-2 rounded-lg ${categoryConfig.bgColor}`}>
                  <CategoryIcon className={`w-5 h-5 ${categoryConfig.color}`} />
                </div>
                <div>
                  <h3 className="font-semibold text-lg">{categoryConfig.label}</h3>
                  <p className="text-sm text-gray-600">{categoryConfig.description}</p>
                </div>
              </div>

              <div className="grid gap-4">
                {requirements.map((requirement) => (
                  <Card
                    key={requirement.module_id}
                    className={`transition-shadow hover:shadow-md ${
                      requirement.status === 'rejected' || requirement.status === 'clarification_required'
                        ? 'border-amber-200 bg-amber-50/30'
                        : ''
                    }`}
                  >
                    <CardHeader>
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-2">
                            <CardTitle className="text-base">{requirement.module_name}</CardTitle>
                            {requirement.is_required && (
                              <Badge variant="outline" className="text-xs">Required</Badge>
                            )}
                          </div>
                          {requirement.description && (
                            <CardDescription>{requirement.description}</CardDescription>
                          )}
                          {/* Show tier requirement if higher than current tier */}
                          {requirement.is_required && requirement.required_for_tier && requirement.required_for_tier > requirementCompletion.tier && (
                            <p className="text-xs text-gray-500 mt-1">
                              Required at Tier {requirement.required_for_tier}
                            </p>
                          )}
                        </div>
                        {getStatusBadge(requirement.status)}
                      </div>
                    </CardHeader>

                    <CardContent className="space-y-4">
                      {/* Action Requested Alert (respectful language) */}
                      {(requirement.status === 'rejected' || requirement.status === 'clarification_required') && requirement.rejection_reason && (
                        <Alert className="border-amber-300 bg-amber-50">
                          <MessageSquare className="h-4 w-4 text-amber-600" />
                          <AlertTitle className="text-amber-900">Action Requested</AlertTitle>
                          <AlertDescription className="text-amber-800">
                            {requirement.rejection_reason}
                            {requirement.corrective_guidance && (
                              <div className="mt-2 pt-2 border-t border-amber-200">
                                <strong>Guidance:</strong> {requirement.corrective_guidance}
                              </div>
                            )}
                          </AlertDescription>
                        </Alert>
                      )}

                      {/* Approved Status */}
                      {requirement.status === 'approved' && (
                        <Alert className="border-green-300 bg-green-50">
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                          <AlertDescription className="text-green-800">
                            This disclosure has been approved and is now part of your platform record.
                          </AlertDescription>
                        </Alert>
                      )}

                      {/* Actions */}
                      <div className="flex items-center gap-2">
                        {requirement.status === 'not_started' && requirement.can_edit && (
                          <Button
                            onClick={async () => {
                              try {
                                const response = await companyApi.post('/disclosures', {
                                  module_id: requirement.module_id,
                                  disclosure_data: {},
                                  edit_reason: 'Initial draft creation',
                                });

                                toast.success('Disclosure draft created');
                                window.location.href = `/company/disclosures/${response.data.data.disclosure_id}`;
                              } catch (error: any) {
                                console.error('[START DISCLOSURE] Failed:', error);
                                toast.error(error.response?.data?.message || 'Failed to start disclosure');
                              }
                            }}
                          >
                            <FileText className="w-4 h-4 mr-2" />
                            Start Disclosure
                          </Button>
                        )}

                        {requirement.id && requirement.status !== 'not_started' && (
                          <>
                            <Link href={`/company/disclosures/${requirement.id}`}>
                              <Button variant="outline">
                                <Eye className="w-4 h-4 mr-2" />
                                View Thread
                              </Button>
                            </Link>

                            {requirement.can_edit && requirement.status !== 'approved' && (
                              <Link href={`/company/disclosures/${requirement.id}/respond`}>
                                <Button>
                                  <MessageSquare className="w-4 h-4 mr-2" />
                                  {requirement.status === 'clarification_required' ? 'Respond' : 'Edit'}
                                </Button>
                              </Link>
                            )}
                          </>
                        )}

                        {requirement.status === 'approved' && (
                          <Button variant="ghost" disabled className="text-gray-500">
                            No action needed
                          </Button>
                        )}
                      </div>

                      {/* Last updated */}
                      {requirement.last_updated && (
                        <p className="text-xs text-gray-500">
                          Last updated: {new Date(requirement.last_updated).toLocaleDateString()}
                        </p>
                      )}
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          );
        })}

        {/* Empty state - should rarely happen since backend always provides requirements */}
        {company.disclosures.length === 0 && (
          <Card>
            <CardContent className="py-12">
              <div className="flex flex-col items-center justify-center text-center space-y-4">
                <FileText className="w-16 h-16 text-gray-400" />
                <div>
                  <h3 className="text-lg font-semibold mb-2">No Requirements Configured</h3>
                  <p className="text-gray-600">
                    The platform administrator has not yet configured disclosure requirements for your account.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Platform Clarifications Section */}
      {company.clarifications.length > 0 && (
        <Card className="border-amber-200">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <MessageSquare className="w-5 h-5" />
              Active Clarifications
            </CardTitle>
            <CardDescription>
              Platform requests for additional information or documentation
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {company.clarifications.map((clarification: any) => (
                <div
                  key={clarification.id}
                  className="flex items-start justify-between p-4 border rounded-lg bg-amber-50/50"
                >
                  <div className="flex-1">
                    <p className="font-medium">{clarification.question}</p>
                    {clarification.issuer_response_overdue && (
                      <Badge variant="destructive" className="mt-2">
                        Response Overdue
                      </Badge>
                    )}
                  </div>
                  <Link href={`/company/clarifications/${clarification.id}`}>
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={!company.effective_permissions.can_answer_clarifications}
                    >
                      {company.effective_permissions.can_answer_clarifications
                        ? "Respond"
                        : "View Only"}
                    </Button>
                  </Link>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
