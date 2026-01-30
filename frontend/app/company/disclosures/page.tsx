/**
 * EPIC 5 Story 5.3 - Company/Issuer Frontend: Disclosure Management Dashboard
 *
 * PURPOSE:
 * - Transform issuer UI from "form editor" into constrained interface to platform governance
 * - Allow issuers to submit and correct information
 * - Enforce platform governance
 * - Prevent accidental investor impact
 *
 * STORY 5.3 RULES:
 * - Platform State Supremacy: If frozen/suspended/investigated, disable edits immediately
 * - Review-State-Driven Editability:
 *   - Draft: editable
 *   - Under Review: conditionally editable or read-only
 *   - Approved: locked (unless reopened by platform)
 *   - Rejected: show reason + corrective guidance
 * - Investor Impact Awareness: Show aggregate metrics (read-only)
 * - Clarification Management: Respond but cannot close/extend
 *
 * HARD INVARIANTS:
 * - ❌ Issuer UI must NEVER allow mutation of locked fields in client state
 * - ❌ No hidden or disabled-but-bound inputs for locked disclosures
 * - ✅ Locked disclosures are rendered as READ-ONLY VIEWS, not disabled forms
 *
 * DEFENSIVE PRINCIPLES:
 * - Platform restrictions disable UI immediately
 * - Cannot edit during suspension/freeze
 * - Cannot override platform timelines
 * - Investor awareness is aggregate only (NO personal data)
 * - Explicit platform override messages
 * - All locks visible before submit
 * - Rejections always show reason and next steps
 */

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  Building2,
  FileText,
  Clock,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  ShieldAlert,
  Lock,
  Eye,
  Edit,
  Send,
  Loader2,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import {
  fetchIssuerCompany,
  IssuerCompanyData,
} from "@/lib/issuerCompanyApi";
import {
  PlatformStatusBanner,
  hasPlatformRestriction,
} from "@/components/issuer/PlatformStatusBanner";
import { IssuerInvestorImpactPanel } from "@/components/issuer/IssuerInvestorImpactPanel";
import { ClarificationResponsePanel } from "@/components/issuer/ClarificationResponsePanel";

export default function IssuerDisclosuresPage() {
  const [company, setCompany] = useState<IssuerCompanyData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Load issuer company data
  useEffect(() => {
    async function loadCompanyData() {
      setLoading(true);
      setError(null);

      try {
        const data = await fetchIssuerCompany();
        setCompany(data);
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

  // Get status badge for disclosure
  const getStatusBadge = (status: string) => {
    switch (status) {
      case "draft":
        return (
          <Badge variant="outline" className="text-gray-600 border-gray-400">
            <Edit className="w-3 h-3 mr-1" />
            Draft
          </Badge>
        );
      case "under_review":
        return (
          <Badge variant="outline" className="text-amber-600 border-amber-400">
            <Clock className="w-3 h-3 mr-1" />
            Under Review
          </Badge>
        );
      case "approved":
        return (
          <Badge variant="outline" className="text-green-600 border-green-400">
            <CheckCircle2 className="w-3 h-3 mr-1" />
            Approved
          </Badge>
        );
      case "rejected":
        return (
          <Badge variant="outline" className="text-red-600 border-red-400">
            <XCircle className="w-3 h-3 mr-1" />
            Rejected
          </Badge>
        );
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  // Calculate completion percentage
  const getCompletionPercentage = () => {
    if (!company || !company.disclosures) return 0;
    const approved = company.disclosures.filter((d) => d.status === "approved").length;
    return Math.round((approved / company.disclosures.length) * 100);
  };

  // Loading state
  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600" />
          <p className="text-gray-600">Loading disclosures...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error || !company) {
    return (
      <div className="container py-20">
        <Alert variant="destructive">
          <AlertTriangle className="h-5 w-5" />
          <AlertTitle>Error Loading Data</AlertTitle>
          <AlertDescription>{error || "Company not found"}</AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="container py-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-4xl font-bold mb-2">Disclosure Management</h1>
        <p className="text-gray-600 dark:text-gray-400">
          Manage your company's platform disclosures
        </p>
      </div>

      {/*
       * STORY 5.3: Platform State Supremacy
       *
       * The PlatformStatusBanner component enforces platform governance:
       * - If frozen/suspended/investigated, edits are disabled immediately
       * - Clear explanations are shown
       * - UI does not allow submit-and-fail behavior
       */}
      <PlatformStatusBanner
        platformContext={company.platform_context}
        effectivePermissions={company.effective_permissions}
        platformOverrides={company.platform_overrides}
        variant="full"
        className="mb-6"
      />

      {/* Completion Progress */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Disclosure Completion</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Overall Progress</span>
              <span className="font-semibold">{getCompletionPercentage()}%</span>
            </div>
            <Progress value={getCompletionPercentage()} className="h-3" />
            <p className="text-xs text-gray-500">
              {company.disclosures.filter((d) => d.status === "approved").length} of{" "}
              {company.disclosures.length} disclosures approved
            </p>
          </div>
        </CardContent>
      </Card>

      {/*
       * STORY 5.3: Investor Impact Awareness (READ-ONLY)
       *
       * Shows aggregate investor metrics without exposing personal data.
       * Issuer can see what version investors have but CANNOT change it.
       * This panel is strictly informational.
       */}
      {company.investor_snapshot_awareness && company.investor_snapshot_awareness.total_investors > 0 && (
        <IssuerInvestorImpactPanel
          investorAwareness={{
            total_investors: company.investor_snapshot_awareness.total_investors,
            version_distribution: company.investor_snapshot_awareness.version_distribution.map((dist) => ({
              version: dist.version_number,
              investor_count: dist.investor_count,
              percentage: dist.percentage,
              is_current: dist.is_current || false,
            })),
          }}
          className="mb-6"
        />
      )}

      {/*
       * STORY 5.3: Disclosures List with Review-State-Driven Editability
       *
       * C2 Acceptance Criteria:
       * - Draft sections are editable
       * - Under-review sections are conditionally editable or read-only
       * - Approved sections are locked
       * - Rejected sections show rejection reason + corrective guidance
       *
       * HARD INVARIANT: Locked disclosures render as read-only views.
       * Edit buttons are hidden (not disabled) for locked disclosures.
       */}
      <div className="space-y-4">
        <h2 className="text-2xl font-bold">Disclosure Modules</h2>

        {company.disclosures.map((disclosure) => (
          <Card key={disclosure.id} className={!disclosure.can_edit ? "opacity-60" : ""}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg">{disclosure.module_name}</CardTitle>
                {getStatusBadge(disclosure.status)}
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Rejection Reason */}
              {disclosure.status === "rejected" && disclosure.rejection_reason && (
                <Alert variant="destructive">
                  <XCircle className="h-4 w-4" />
                  <AlertTitle>Rejection Reason</AlertTitle>
                  <AlertDescription>{disclosure.rejection_reason}</AlertDescription>
                  {disclosure.corrective_guidance && (
                    <AlertDescription className="mt-2">
                      <strong>Corrective Guidance:</strong> {disclosure.corrective_guidance}
                    </AlertDescription>
                  )}
                </Alert>
              )}

              {/* Approved - Locked */}
              {disclosure.status === "approved" && (
                <Alert className="border-green-300 bg-green-50 dark:bg-green-950/30">
                  <Lock className="h-4 w-4 text-green-600" />
                  <AlertDescription className="text-sm text-green-800 dark:text-green-200">
                    This disclosure has been approved by the platform and is now locked. Contact
                    platform support to request changes.
                  </AlertDescription>
                </Alert>
              )}

              {/* Under Review - Read-only or Conditionally Editable */}
              {disclosure.status === "under_review" && (
                <Alert className="border-amber-300 bg-amber-50 dark:bg-amber-950/30">
                  <Clock className="h-4 w-4 text-amber-600" />
                  <AlertDescription className="text-sm text-amber-800 dark:text-amber-200">
                    This disclosure is currently under platform review. Edit permissions may be limited.
                  </AlertDescription>
                </Alert>
              )}

              {/* Platform Restrictions Blocking Edit */}
              {!disclosure.can_edit && disclosure.status !== "approved" && (
                <Alert variant="destructive">
                  <ShieldAlert className="h-4 w-4" />
                  <AlertDescription className="text-sm">
                    Platform restrictions prevent editing this disclosure. See platform status above.
                  </AlertDescription>
                </Alert>
              )}

              {/* Action Buttons */}
              <div className="flex gap-3">
                <Link href={`/company/disclosures/${disclosure.id}`}>
                  <Button variant="outline">
                    <Eye className="w-4 h-4 mr-2" />
                    View Details
                  </Button>
                </Link>

                {disclosure.can_edit && (
                  <Link href={`/company/disclosures/${disclosure.id}/edit`}>
                    <Button>
                      <Edit className="w-4 h-4 mr-2" />
                      Edit
                    </Button>
                  </Link>
                )}

                {disclosure.can_submit && disclosure.status === "draft" && (
                  <Link href={`/company/disclosures/${disclosure.id}/submit`}>
                    <Button variant="default">
                      <Send className="w-4 h-4 mr-2" />
                      Submit for Review
                    </Button>
                  </Link>
                )}

                {!disclosure.can_edit && !disclosure.can_submit && disclosure.status === "draft" && (
                  <Button variant="outline" disabled>
                    <Lock className="w-4 h-4 mr-2" />
                    Editing Blocked
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/*
       * STORY 5.3: Clarifications Section
       *
       * C4 Acceptance Criteria:
       * - Issuer CAN respond to clarifications
       * - Issuer CANNOT close or extend clarifications
       * - Platform timelines and escalation are visible (read-only)
       */}
      {company.clarifications && company.clarifications.length > 0 && (
        <div className="mt-8">
          <h2 className="text-2xl font-bold mb-4">Platform Clarifications</h2>
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
            You can respond to clarifications but cannot close or extend deadlines. Platform timelines are final.
          </p>
          <div className="space-y-4">
            {company.clarifications.map((clarification) => (
              <Card
                key={clarification.id}
                className={
                  clarification.is_expired
                    ? "border-red-300"
                    : clarification.issuer_response_overdue
                    ? "border-amber-300"
                    : ""
                }
              >
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Clarification Request</CardTitle>
                    {clarification.is_expired && (
                      <Badge variant="destructive">Expired</Badge>
                    )}
                    {clarification.is_escalated && !clarification.is_expired && (
                      <Badge variant="destructive">Escalated</Badge>
                    )}
                    {clarification.issuer_response_overdue && !clarification.is_expired && (
                      <Badge className="bg-amber-600 text-white">Overdue</Badge>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-3">
                  <p className="text-gray-700 dark:text-gray-300">{clarification.question}</p>

                  {clarification.issuer_response_due_at && (
                    <div className="flex items-center gap-2 text-sm">
                      <Clock className="w-4 h-4 text-amber-600" />
                      <span className="text-gray-600 dark:text-gray-400">
                        Response Due:{" "}
                        <strong>{new Date(clarification.issuer_response_due_at).toLocaleString()}</strong>
                      </span>
                    </div>
                  )}

                  {clarification.is_expired ? (
                    <Alert variant="destructive">
                      <AlertDescription>
                        This clarification has expired. Contact platform support.
                      </AlertDescription>
                    </Alert>
                  ) : (
                    <Link href={`/company/clarifications/${clarification.id}`}>
                      <Button disabled={!company.effective_permissions.can_answer_clarifications}>
                        {company.effective_permissions.can_answer_clarifications
                          ? "Respond to Clarification"
                          : "Response Blocked by Platform"}
                      </Button>
                    </Link>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
