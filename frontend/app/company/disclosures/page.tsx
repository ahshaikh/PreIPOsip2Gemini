/**
 * PHASE 5 - Company/Issuer Frontend: Disclosure Management Dashboard
 *
 * PURPOSE:
 * - Show issuer's disclosure submission status
 * - Enforce platform state supremacy (UI reflects platform restrictions)
 * - Review-state-driven editability
 * - Investor impact awareness (aggregate only)
 * - Clarification management with deadlines
 *
 * DEFENSIVE PRINCIPLES:
 * - Platform restrictions disable UI immediately
 * - Cannot edit during suspension/freeze
 * - Cannot override platform timelines
 * - Investor awareness is aggregate only (NO personal data)
 * - Explicit platform override messages
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

      {/* PHASE 5: Platform Supremacy - Show Platform Restrictions First */}
      {(company.platform_context.is_suspended ||
        company.platform_context.is_frozen ||
        company.platform_context.is_under_investigation ||
        company.platform_overrides.length > 0) && (
        <Alert variant="destructive" className="mb-6">
          <ShieldAlert className="h-5 w-5" />
          <AlertTitle>Platform Restrictions Active</AlertTitle>
          <AlertDescription>
            <p className="mb-2">
              The platform has imposed restrictions on your account. Your ability to edit and
              submit disclosures is limited.
            </p>
            <ul className="list-disc list-inside space-y-1">
              {company.platform_context.is_suspended && (
                <li className="font-semibold">Company Suspended - All edits blocked</li>
              )}
              {company.platform_context.is_frozen && (
                <li className="font-semibold">Disclosures Frozen - Cannot edit or submit</li>
              )}
              {company.platform_context.is_under_investigation && (
                <li className="font-semibold">Under Investigation - Limited access</li>
              )}
              {company.platform_overrides.map((msg, i) => (
                <li key={i}>{msg}</li>
              ))}
            </ul>
            {company.platform_context.buying_pause_reason && (
              <p className="mt-2 text-sm">
                <strong>Buying Pause Reason:</strong> {company.platform_context.buying_pause_reason}
              </p>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Platform Context Summary */}
      <Card className="mb-6 border-purple-200 dark:border-purple-800">
        <CardHeader>
          <CardTitle className="flex items-center">
            <Building2 className="w-5 h-5 mr-2" />
            Platform Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
              <span className="text-gray-500">Lifecycle State</span>
              <p className="font-semibold capitalize">
                {company.platform_context.lifecycle_state.replace("_", " ")}
              </p>
            </div>
            <div>
              <span className="text-gray-500">Buying Status</span>
              <p
                className={`font-semibold ${
                  company.platform_context.buying_enabled ? "text-green-600" : "text-red-600"
                }`}
              >
                {company.platform_context.buying_enabled ? "Enabled" : "Disabled"}
              </p>
            </div>
            <div>
              <span className="text-gray-500">Tier 2 Approval</span>
              <p
                className={`font-semibold ${
                  company.platform_context.tier_status.tier_2_approved
                    ? "text-green-600"
                    : "text-amber-600"
                }`}
              >
                {company.platform_context.tier_status.tier_2_approved ? "Approved" : "Pending"}
              </p>
            </div>
            <div>
              <span className="text-gray-500">Edit Permissions</span>
              <p
                className={`font-semibold ${
                  company.effective_permissions.can_edit_disclosures
                    ? "text-green-600"
                    : "text-red-600"
                }`}
              >
                {company.effective_permissions.can_edit_disclosures ? "Allowed" : "Blocked"}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

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

      {/* Investor Snapshot Awareness (Aggregate) */}
      {company.investor_snapshot_awareness && company.investor_snapshot_awareness.total_investors > 0 && (
        <Card className="mb-6 border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/20">
          <CardHeader>
            <CardTitle className="flex items-center text-blue-900 dark:text-blue-200">
              <Eye className="w-5 h-5 mr-2" />
              Investor Snapshot Awareness
            </CardTitle>
            <p className="text-sm text-blue-700 dark:text-blue-300">
              {company.investor_snapshot_awareness.privacy_note}
            </p>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-blue-700 dark:text-blue-300">Total Investors</span>
                <Badge className="bg-blue-600 text-white">
                  {company.investor_snapshot_awareness.total_investors}
                </Badge>
              </div>

              {company.investor_snapshot_awareness.version_distribution.length > 0 && (
                <div>
                  <p className="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">
                    Version Distribution
                  </p>
                  <div className="space-y-2">
                    {company.investor_snapshot_awareness.version_distribution.map((dist) => (
                      <div key={dist.version_number} className="flex justify-between items-center text-sm">
                        <span className="text-blue-700 dark:text-blue-300">
                          Version {dist.version_number}
                        </span>
                        <span className="text-blue-900 dark:text-blue-100">
                          {dist.investor_count} investors ({dist.percentage.toFixed(1)}%)
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <Alert className="border-blue-300 bg-blue-100 dark:bg-blue-950/50">
                <AlertDescription className="text-xs text-blue-800 dark:text-blue-200">
                  <strong>Important:</strong> If you update and resubmit disclosures, existing
                  investors will continue to see the version they invested based on. Only new
                  investors will see updated versions. Individual investor identities are never exposed.
                </AlertDescription>
              </Alert>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Disclosures List */}
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

      {/* Clarifications Section */}
      {company.clarifications && company.clarifications.length > 0 && (
        <div className="mt-8">
          <h2 className="text-2xl font-bold mb-4">Platform Clarifications</h2>
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
