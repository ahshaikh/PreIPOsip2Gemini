"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Loader2,
  ArrowLeft,
  Building2,
  FileText,
  CheckCircle,
  XCircle,
  Clock,
  AlertTriangle,
  MessageSquare,
  Shield,
  Paperclip,
  RefreshCw,
  CalendarDays,
} from "lucide-react";
import { toast } from "sonner";
import api from "@/lib/api";
import Link from "next/link";
import { FreshnessIndicator, VitalityBadge, type ArtifactFreshnessState } from "@/components/disclosures";

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface DisclosureDetail {
  disclosure: {
    id: number;
    status: string;
    version_number: number;
    completion_percentage: number;
    disclosure_data: Record<string, any> | null;
    attachments: any[] | null;
    submitted_at: string | null;
    submitted_by: number | null;
    review_started_at: string | null;
    review_started_by: number | null;
    approved_at: string | null;
    approved_by: number | null;
    rejected_at: string | null;
    rejected_by: number | null;
    rejection_reason: string | null;
    is_locked: boolean;
    edits_during_review: any[] | null;
    edit_count_during_review: number | null;
  };
  // Freshness tracking (backend-computed, only for approved disclosures)
  freshness?: {
    state: ArtifactFreshnessState;
    days_since_approval: number | null;
    expected_cadence_days: number | null;
    next_update_expected: string | null;
    update_count_in_window: number;
    signal_text: string;
    is_update_required_document: boolean;
    has_override: boolean;
    override_reason?: string | null;
  };
  company: {
    id: number;
    name: string;
    lifecycle_state: string;
    buying_enabled: boolean;
  };
  module: {
    id: number;
    code: string;
    name: string;
    tier: number;
    json_schema: any;
  };
  clarifications: Clarification[];
  summary: {
    can_start_review: boolean;
    can_approve: boolean;
    can_reject: boolean;
    can_request_clarification: boolean;
    audit_window_breached: boolean;
    is_terminal: boolean;
    clarifications: {
      total: number;
      open: number;
      answered: number;
      blocking: number;
    };
    edits_during_review: {
      count: number;
      last_edit_at: string | null;
    };
  };
}

interface Clarification {
  id: number;
  question_subject: string;
  question_body: string;
  question_type: string;
  asked_by: string | null;
  asked_at: string;
  field_path: string | null;
  status: string;
  priority: string;
  is_blocking: boolean;
  due_date: string | null;
  answer_body: string | null;
  answered_by: string | null;
  answered_at: string | null;
  resolution_notes: string | null;
}

interface TimelineEvent {
  id: number;
  event_type: string;
  actor_name: string;
  actor_type: string;
  message: string | null;
  metadata: Record<string, any> | null;
  created_at: string;
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STATUS_BADGES: Record<string, { color: string; label: string }> = {
  submitted: { color: "bg-amber-100 text-amber-800 border-amber-300", label: "Submitted" },
  resubmitted: { color: "bg-amber-100 text-amber-800 border-amber-300", label: "Resubmitted" },
  under_review: { color: "bg-blue-100 text-blue-800 border-blue-300", label: "Under Review" },
  clarification_required: { color: "bg-orange-100 text-orange-800 border-orange-300", label: "Clarification Required" },
  approved: { color: "bg-green-100 text-green-800 border-green-300", label: "Approved" },
  rejected: { color: "bg-red-100 text-red-800 border-red-300", label: "Rejected" },
};

const PRIORITY_BADGES: Record<string, string> = {
  low: "bg-gray-100 text-gray-700",
  medium: "bg-blue-100 text-blue-700",
  high: "bg-orange-100 text-orange-700",
  critical: "bg-red-100 text-red-700",
};

const CLARIFICATION_STATUS_BADGES: Record<string, { color: string; label: string }> = {
  open: { color: "bg-amber-100 text-amber-800", label: "Open" },
  answered: { color: "bg-blue-100 text-blue-800", label: "Answered" },
  accepted: { color: "bg-green-100 text-green-800", label: "Accepted" },
  disputed: { color: "bg-red-100 text-red-800", label: "Disputed" },
};

const QUESTION_TYPE_LABELS: Record<string, string> = {
  missing_data: "Missing Data",
  inconsistency: "Inconsistency",
  insufficient_detail: "Insufficient Detail",
  verification: "Verification",
  compliance: "Compliance",
  other: "Other",
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatTimestamp(ts: string | null): string {
  if (!ts) return "\u2014";
  return new Date(ts).toLocaleString();
}

function actorLabel(actorType: string): string {
  if (actorType.includes("CompanyUser")) return "Company";
  if (actorType.includes("User")) return "Admin";
  return "System";
}

function eventIcon(eventType: string) {
  switch (eventType) {
    case "approval":
      return <CheckCircle className="w-4 h-4 text-green-600" />;
    case "rejection":
      return <XCircle className="w-4 h-4 text-red-600" />;
    case "clarification_requested":
      return <MessageSquare className="w-4 h-4 text-orange-600" />;
    case "submission":
    case "response":
      return <FileText className="w-4 h-4 text-blue-600" />;
    default:
      return <Clock className="w-4 h-4 text-gray-500" />;
  }
}

// ---------------------------------------------------------------------------
// Disclosure Content Renderer (structured, not raw JSON)
// ---------------------------------------------------------------------------

function DisclosureContentRenderer({ data }: { data: Record<string, any> | null }) {
  if (!data || Object.keys(data).length === 0) {
    return <p className="text-sm text-gray-500 italic">No disclosure data provided.</p>;
  }

  function renderValue(value: any, depth: number = 0): React.ReactNode {
    if (value === null || value === undefined) {
      return <span className="text-gray-400 italic">Not provided</span>;
    }
    if (typeof value === "boolean") {
      return <span>{value ? "Yes" : "No"}</span>;
    }
    if (typeof value === "string" || typeof value === "number") {
      return <span className="text-gray-900">{String(value)}</span>;
    }
    if (Array.isArray(value)) {
      if (value.length === 0) return <span className="text-gray-400 italic">None</span>;
      return (
        <ul className="list-disc list-inside space-y-1">
          {value.map((item, i) => (
            <li key={i} className="text-sm text-gray-900">
              {typeof item === "object" ? renderValue(item, depth + 1) : String(item)}
            </li>
          ))}
        </ul>
      );
    }
    if (typeof value === "object") {
      return (
        <div className={depth > 0 ? "ml-4 border-l-2 border-gray-200 pl-4 mt-1" : ""}>
          {Object.entries(value).map(([k, v]) => (
            <div key={k} className="mb-2">
              <span className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                {k.replace(/_/g, " ")}
              </span>
              <div className="mt-0.5">{renderValue(v, depth + 1)}</div>
            </div>
          ))}
        </div>
      );
    }
    return <span>{JSON.stringify(value)}</span>;
  }

  return (
    <div className="space-y-4">
      {Object.entries(data).map(([key, value]) => (
        <div key={key} className="border-b border-gray-100 pb-3 last:border-0">
          <Label className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
            {key.replace(/_/g, " ")}
          </Label>
          <div className="mt-1 text-sm">{renderValue(value)}</div>
        </div>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Main Component
// ---------------------------------------------------------------------------

export default function DisclosureDetailPage() {
  const params = useParams();
  const router = useRouter();
  const disclosureId = params?.id;

  // Data state
  const [loading, setLoading] = useState(true);
  const [detail, setDetail] = useState<DisclosureDetail | null>(null);
  const [timeline, setTimeline] = useState<TimelineEvent[]>([]);
  const [timelineLoading, setTimelineLoading] = useState(true);

  // Action state
  const [submitting, setSubmitting] = useState(false);
  const [reviewNotes, setReviewNotes] = useState("");
  const [rejectionReason, setRejectionReason] = useState("");

  // Clarification form state
  const [showClarificationForm, setShowClarificationForm] = useState(false);
  const [clarificationSubject, setClarificationSubject] = useState("");
  const [clarificationBody, setClarificationBody] = useState("");
  const [clarificationType, setClarificationType] = useState("verification");
  const [clarificationPriority, setClarificationPriority] = useState("medium");
  const [clarificationBlocking, setClarificationBlocking] = useState(false);

  // Confirmation dialogs
  const [showApproveDialog, setShowApproveDialog] = useState(false);
  const [showRejectDialog, setShowRejectDialog] = useState(false);

  useEffect(() => {
    if (disclosureId) {
      loadDetail();
      loadTimeline();
    }
  }, [disclosureId]);

  async function loadDetail() {
    try {
      setLoading(true);
      const response = await api.get(`/admin/disclosures/${disclosureId}`);
      if (response.data.status === "success") {
        setDetail(response.data.data);
      } else {
        toast.error("Failed to load disclosure");
      }
    } catch (error: any) {
      console.error("Failed to load disclosure:", error);
      toast.error("Failed to load disclosure details");
    } finally {
      setLoading(false);
    }
  }

  async function loadTimeline() {
    try {
      setTimelineLoading(true);
      const response = await api.get(`/admin/disclosures/${disclosureId}/timeline`);
      if (response.data.status === "success") {
        setTimeline(response.data.data || []);
      }
    } catch (error: any) {
      // Timeline may not exist yet — not critical
      console.error("Failed to load timeline:", error);
    } finally {
      setTimelineLoading(false);
    }
  }

  async function handleStartReview() {
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosureId}/start-review`);
      if (response.data.status === "success") {
        toast.success("Review started");
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to start review");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to start review");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleApprove() {
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosureId}/approve`, {
        notes: reviewNotes || null,
      });
      if (response.data.status === "success") {
        toast.success("Disclosure approved");
        setReviewNotes("");
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to approve disclosure");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to approve disclosure");
    } finally {
      setSubmitting(false);
      setShowApproveDialog(false);
    }
  }

  async function handleReject() {
    if (!rejectionReason.trim()) {
      toast.error("Reason for rejection is required");
      return;
    }
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosureId}/reject`, {
        reason: rejectionReason,
      });
      if (response.data.status === "success") {
        toast.success("Disclosure rejected");
        setRejectionReason("");
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to reject disclosure");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to reject disclosure");
    } finally {
      setSubmitting(false);
      setShowRejectDialog(false);
    }
  }

  async function handleRequestClarification() {
    if (!clarificationSubject.trim() || !clarificationBody.trim()) {
      toast.error("Subject and question are required");
      return;
    }
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosureId}/clarifications`, {
        clarifications: [
          {
            question_subject: clarificationSubject,
            question_body: clarificationBody,
            question_type: clarificationType,
            priority: clarificationPriority,
            is_blocking: clarificationBlocking,
          },
        ],
      });
      if (response.data.status === "success") {
        toast.success("Clarification requested");
        setClarificationSubject("");
        setClarificationBody("");
        setClarificationType("verification");
        setClarificationPriority("medium");
        setClarificationBlocking(false);
        setShowClarificationForm(false);
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to request clarification");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to request clarification");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleAcceptClarification(clarificationId: number) {
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/clarifications/${clarificationId}/accept`, {
        notes: null,
      });
      if (response.data.status === "success") {
        toast.success("Clarification answer accepted");
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to accept clarification");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to accept clarification");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleDisputeClarification(clarificationId: number, reason: string) {
    if (!reason.trim()) {
      toast.error("Dispute reason is required");
      return;
    }
    try {
      setSubmitting(true);
      const response = await api.post(`/admin/clarifications/${clarificationId}/dispute`, {
        reason,
      });
      if (response.data.status === "success") {
        toast.success("Clarification answer disputed");
        await loadDetail();
        await loadTimeline();
      } else {
        toast.error(response.data.message || "Failed to dispute clarification");
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || "Failed to dispute clarification");
    } finally {
      setSubmitting(false);
    }
  }

  // ---------------------------------------------------------------------------
  // Loading / Not Found
  // ---------------------------------------------------------------------------

  if (loading) {
    return (
      <div className="container mx-auto py-8">
        <div className="flex items-center justify-center min-h-[400px]">
          <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
        </div>
      </div>
    );
  }

  if (!detail) {
    return (
      <div className="container mx-auto py-8">
        <Card>
          <CardContent className="py-12 text-center">
            <XCircle className="w-12 h-12 mx-auto text-red-500 mb-4" />
            <h3 className="text-lg font-semibold mb-2">Disclosure Not Found</h3>
            <Link href="/admin/disclosures">
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back to Disclosures
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  const { disclosure, company, module, clarifications, summary } = detail;
  const statusBadge = STATUS_BADGES[disclosure.status] || { color: "bg-gray-100 text-gray-700", label: disclosure.status };

  // ---------------------------------------------------------------------------
  // Render
  // ---------------------------------------------------------------------------

  return (
    <div className="container mx-auto py-8 max-w-5xl">
      <Link
        href="/admin/disclosures"
        className="inline-flex items-center text-sm text-gray-600 hover:text-purple-600 mb-6"
      >
        <ArrowLeft className="w-4 h-4 mr-2" />
        Back to Disclosure Review
      </Link>

      <div className="space-y-6">
        {/* ================================================================= */}
        {/* SECTION 1: Requirement Header */}
        {/* ================================================================= */}
        <Card>
          <CardHeader className="pb-4">
            <div className="flex items-start justify-between">
              <div>
                <CardTitle className="flex items-center gap-3">
                  <Building2 className="w-5 h-5 text-purple-600" />
                  {company.name}
                </CardTitle>
                <CardDescription className="mt-1">
                  {module.name} ({module.code})
                </CardDescription>
              </div>
              <div className="flex items-center gap-2">
                <Badge variant="outline" className={`bg-${module.tier === 1 ? "blue" : module.tier === 2 ? "green" : "purple"}-100 text-${module.tier === 1 ? "blue" : module.tier === 2 ? "green" : "purple"}-700`}>
                  Tier {module.tier}
                </Badge>
                <Badge variant="outline" className={statusBadge.color}>
                  {statusBadge.label}
                </Badge>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {/* Audit Window Breach Banner */}
            {summary.audit_window_breached && (
              <Alert className="mb-4 border-amber-300 bg-amber-50">
                <AlertTriangle className="h-4 w-4 text-amber-600" />
                <AlertTitle className="text-amber-900">Audit Window Exceeded</AlertTitle>
                <AlertDescription className="text-amber-800">
                  The review period for this disclosure has exceeded the expected audit window.
                </AlertDescription>
              </Alert>
            )}

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <span className="text-gray-500 text-xs uppercase tracking-wide">Version</span>
                <p className="font-medium">v{disclosure.version_number}</p>
              </div>
              <div>
                <span className="text-gray-500 text-xs uppercase tracking-wide">Company State</span>
                <p className="font-medium capitalize">{company.lifecycle_state?.replace(/_/g, " ") || "\u2014"}</p>
              </div>
              <div>
                <span className="text-gray-500 text-xs uppercase tracking-wide">Submitted</span>
                <p className="font-medium">{formatTimestamp(disclosure.submitted_at)}</p>
              </div>
              <div>
                <span className="text-gray-500 text-xs uppercase tracking-wide">Review Started</span>
                <p className="font-medium">{formatTimestamp(disclosure.review_started_at)}</p>
              </div>
              {disclosure.approved_at && (
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Approved</span>
                  <p className="font-medium">{formatTimestamp(disclosure.approved_at)}</p>
                </div>
              )}
              {disclosure.rejected_at && (
                <>
                  <div>
                    <span className="text-gray-500 text-xs uppercase tracking-wide">Rejected</span>
                    <p className="font-medium">{formatTimestamp(disclosure.rejected_at)}</p>
                  </div>
                  <div className="col-span-2">
                    <span className="text-gray-500 text-xs uppercase tracking-wide">Rejection Reason</span>
                    <p className="font-medium text-red-700">{disclosure.rejection_reason}</p>
                  </div>
                </>
              )}
              {(disclosure.edit_count_during_review ?? 0) > 0 && (
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Edits During Review</span>
                  <p className="font-medium text-amber-700">{disclosure.edit_count_during_review} edit(s)</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* ================================================================= */}
        {/* SECTION 1.5: Document Freshness (only for approved disclosures) */}
        {/* ================================================================= */}
        {disclosure.status === "approved" && detail.freshness && (
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <RefreshCw className="w-5 h-5" />
                  Document Freshness
                </CardTitle>
                <FreshnessIndicator state={detail.freshness.state} />
              </div>
              <CardDescription>
                Backend-computed freshness status for this approved disclosure.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Days Since Approval</span>
                  <p className="font-medium">{detail.freshness.days_since_approval ?? "—"}</p>
                </div>
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Expected Cadence</span>
                  <p className="font-medium">
                    {detail.freshness.expected_cadence_days
                      ? `${detail.freshness.expected_cadence_days} days`
                      : "N/A (Version Controlled)"}
                  </p>
                </div>
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Next Update Expected</span>
                  <p className="font-medium">
                    {detail.freshness.next_update_expected
                      ? new Date(detail.freshness.next_update_expected).toLocaleDateString()
                      : "—"}
                  </p>
                </div>
                <div>
                  <span className="text-gray-500 text-xs uppercase tracking-wide">Updates in Window</span>
                  <p className="font-medium">{detail.freshness.update_count_in_window}</p>
                </div>
              </div>

              {/* Signal Text */}
              <div className="p-3 rounded-lg bg-gray-50 border">
                <p className="text-sm text-gray-700">
                  {detail.freshness.signal_text}
                </p>
              </div>

              {/* Override Notice (Audit-Only) */}
              {detail.freshness.has_override && (
                <Alert className="mt-4 border-purple-300 bg-purple-50">
                  <AlertTriangle className="h-4 w-4 text-purple-600" />
                  <AlertTitle className="text-purple-900">Freshness Override Active</AlertTitle>
                  <AlertDescription className="text-purple-800">
                    {detail.freshness.override_reason || "An administrative override is in effect for this disclosure."}
                    <span className="block mt-1 text-xs text-purple-600">
                      Note: Overrides are audit-only and do not improve the computed freshness state.
                    </span>
                  </AlertDescription>
                </Alert>
              )}
            </CardContent>
          </Card>
        )}

        {/* ================================================================= */}
        {/* SECTION 2: Disclosure Content */}
        {/* ================================================================= */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <FileText className="w-5 h-5" />
              Disclosure Content
            </CardTitle>
          </CardHeader>
          <CardContent>
            <DisclosureContentRenderer data={disclosure.disclosure_data} />

            {/* Attachments */}
            {disclosure.attachments && disclosure.attachments.length > 0 && (
              <div className="mt-6 pt-4 border-t">
                <h4 className="text-sm font-semibold text-gray-600 mb-2 flex items-center gap-2">
                  <Paperclip className="w-4 h-4" />
                  Attachments ({disclosure.attachments.length})
                </h4>
                <div className="space-y-2">
                  {disclosure.attachments.map((attachment: any, i: number) => (
                    <div key={i} className="flex items-center gap-2 text-sm text-blue-600">
                      <FileText className="w-4 h-4" />
                      <span>{attachment.file_name || attachment.name || `Attachment ${i + 1}`}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* ================================================================= */}
        {/* SECTION 3: Clarifications Panel */}
        {/* ================================================================= */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-lg">
                <MessageSquare className="w-5 h-5" />
                Clarifications
                {clarifications.length > 0 && (
                  <Badge variant="outline" className="ml-2">{clarifications.length}</Badge>
                )}
              </CardTitle>
              {summary.can_request_clarification && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setShowClarificationForm(!showClarificationForm)}
                >
                  {showClarificationForm ? "Cancel" : "Request Clarification"}
                </Button>
              )}
            </div>
            <CardDescription className="text-xs text-gray-500">
              Clarifications are non-decisional. They gather information only and do not affect compliance status.
            </CardDescription>
          </CardHeader>
          <CardContent>
            {/* Clarification Request Form */}
            {showClarificationForm && (
              <div className="mb-6 p-4 border rounded-lg bg-gray-50 space-y-4">
                <div>
                  <Label htmlFor="clar-subject">Subject</Label>
                  <Input
                    id="clar-subject"
                    value={clarificationSubject}
                    onChange={(e) => setClarificationSubject(e.target.value)}
                    placeholder="Brief subject for the clarification"
                    className="mt-1"
                  />
                </div>
                <div>
                  <Label htmlFor="clar-body">Question</Label>
                  <Textarea
                    id="clar-body"
                    value={clarificationBody}
                    onChange={(e) => setClarificationBody(e.target.value)}
                    placeholder="Describe what information is needed..."
                    rows={3}
                    className="mt-1"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label>Type</Label>
                    <Select value={clarificationType} onValueChange={setClarificationType}>
                      <SelectTrigger className="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="missing_data">Missing Data</SelectItem>
                        <SelectItem value="inconsistency">Inconsistency</SelectItem>
                        <SelectItem value="insufficient_detail">Insufficient Detail</SelectItem>
                        <SelectItem value="verification">Verification</SelectItem>
                        <SelectItem value="compliance">Compliance</SelectItem>
                        <SelectItem value="other">Other</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div>
                    <Label>Priority</Label>
                    <Select value={clarificationPriority} onValueChange={setClarificationPriority}>
                      <SelectTrigger className="mt-1">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="low">Low</SelectItem>
                        <SelectItem value="medium">Medium</SelectItem>
                        <SelectItem value="high">High</SelectItem>
                        <SelectItem value="critical">Critical</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Checkbox
                    id="clar-blocking"
                    checked={clarificationBlocking}
                    onCheckedChange={(checked) => setClarificationBlocking(checked === true)}
                  />
                  <Label htmlFor="clar-blocking" className="text-sm">
                    Blocking (prevents approval until resolved)
                  </Label>
                </div>
                <Button onClick={handleRequestClarification} disabled={submitting}>
                  {submitting ? (
                    <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Submitting...</>
                  ) : (
                    "Submit Clarification Request"
                  )}
                </Button>
              </div>
            )}

            {/* Clarification List */}
            {clarifications.length === 0 ? (
              <p className="text-sm text-gray-500">No clarifications requested.</p>
            ) : (
              <div className="space-y-4">
                {clarifications.map((c) => (
                  <ClarificationItem
                    key={c.id}
                    clarification={c}
                    onAccept={handleAcceptClarification}
                    onDispute={handleDisputeClarification}
                    submitting={submitting}
                  />
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* ================================================================= */}
        {/* SECTION 4: Timeline (Audit Spine) */}
        {/* ================================================================= */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Clock className="w-5 h-5" />
              Timeline
            </CardTitle>
          </CardHeader>
          <CardContent>
            {timelineLoading ? (
              <div className="flex justify-center py-8">
                <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
              </div>
            ) : timeline.length === 0 ? (
              <p className="text-sm text-gray-500">No timeline events recorded.</p>
            ) : (
              <div className="space-y-0">
                {timeline.map((event, i) => (
                  <div key={event.id} className="flex gap-4 pb-4 relative">
                    {/* Connector line */}
                    {i < timeline.length - 1 && (
                      <div className="absolute left-[7px] top-6 bottom-0 w-px bg-gray-200" />
                    )}
                    <div className="mt-1 z-10">{eventIcon(event.event_type)}</div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-baseline gap-2">
                        <span className="text-sm font-medium text-gray-900">
                          {event.actor_name}
                        </span>
                        <Badge variant="outline" className="text-[10px] px-1.5 py-0">
                          {actorLabel(event.actor_type)}
                        </Badge>
                        <span className="text-xs text-gray-400">
                          {formatTimestamp(event.created_at)}
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 mt-0.5 capitalize">
                        {event.event_type.replace(/_/g, " ")}
                      </p>
                      {event.message && (
                        <p className="text-sm text-gray-900 mt-1 bg-gray-50 rounded p-2">
                          {event.message}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* ================================================================= */}
        {/* SECTION 5: Admin Decision Panel */}
        {/* ================================================================= */}
        {!summary.is_terminal && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Shield className="w-5 h-5" />
                Decision
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Start Review */}
              {summary.can_start_review && (
                <div>
                  <Button
                    onClick={handleStartReview}
                    disabled={submitting}
                    className="bg-blue-600 hover:bg-blue-700"
                  >
                    {submitting ? (
                      <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Processing...</>
                    ) : (
                      "Begin Review"
                    )}
                  </Button>
                  <p className="text-xs text-gray-500 mt-2">
                    This action is recorded and cannot be undone.
                  </p>
                </div>
              )}

              {/* Approve */}
              {summary.can_approve && (
                <div className="space-y-3">
                  <div>
                    <Label htmlFor="approve-notes">Review Notes (optional)</Label>
                    <Textarea
                      id="approve-notes"
                      value={reviewNotes}
                      onChange={(e) => setReviewNotes(e.target.value)}
                      placeholder="Optional notes for the approval record..."
                      rows={3}
                      className="mt-1"
                    />
                  </div>
                  <Button
                    onClick={() => setShowApproveDialog(true)}
                    disabled={submitting}
                    className="bg-green-600 hover:bg-green-700"
                  >
                    <CheckCircle className="w-4 h-4 mr-2" />
                    Approve Disclosure
                  </Button>
                </div>
              )}

              {/* Reject */}
              {summary.can_reject && (
                <div className="space-y-3 pt-4 border-t">
                  <div>
                    <Label htmlFor="reject-reason">Reason for Rejection (required)</Label>
                    <Textarea
                      id="reject-reason"
                      value={rejectionReason}
                      onChange={(e) => setRejectionReason(e.target.value)}
                      placeholder="Provide the reason for rejection..."
                      rows={3}
                      className="mt-1"
                    />
                  </div>
                  <Button
                    onClick={() => {
                      if (!rejectionReason.trim()) {
                        toast.error("Reason for rejection is required");
                        return;
                      }
                      setShowRejectDialog(true);
                    }}
                    disabled={submitting}
                    variant="destructive"
                  >
                    <XCircle className="w-4 h-4 mr-2" />
                    Reject Disclosure
                  </Button>
                </div>
              )}

              {/* No actions available */}
              {!summary.can_start_review && !summary.can_approve && !summary.can_reject && !summary.can_request_clarification && (
                <p className="text-sm text-gray-500">
                  No actions available. The disclosure is pending company response.
                </p>
              )}
            </CardContent>
          </Card>
        )}

        {/* Terminal state read-only notice */}
        {summary.is_terminal && (
          <Card className="border-gray-200 bg-gray-50">
            <CardContent className="py-6 text-center">
              <p className="text-sm text-gray-500">
                This disclosure has been {disclosure.status === "approved" ? "approved" : "rejected"} and is read-only.
              </p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* ================================================================= */}
      {/* Confirmation Dialogs */}
      {/* ================================================================= */}

      {/* Approve Confirmation */}
      <AlertDialog open={showApproveDialog} onOpenChange={setShowApproveDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Approve Disclosure</AlertDialogTitle>
            <AlertDialogDescription>
              This will lock this disclosure version and may trigger a tier transition.
              {summary.audit_window_breached && (
                <span className="block mt-2 font-medium text-amber-700">
                  This action is being taken after the audit review window.
                </span>
              )}
              <span className="block mt-2">This action is recorded and cannot be undone.</span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleApprove}
              disabled={submitting}
              className="bg-green-600 hover:bg-green-700"
            >
              {submitting ? "Processing..." : "Confirm Approval"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Reject Confirmation */}
      <AlertDialog open={showRejectDialog} onOpenChange={setShowRejectDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Reject Disclosure</AlertDialogTitle>
            <AlertDialogDescription>
              The company will be notified of the rejection with the provided reason.
              {summary.audit_window_breached && (
                <span className="block mt-2 font-medium text-amber-700">
                  This action is being taken after the audit review window.
                </span>
              )}
              <span className="block mt-2">This action is recorded and cannot be undone.</span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={submitting}>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleReject}
              disabled={submitting}
              className="bg-red-600 hover:bg-red-700"
            >
              {submitting ? "Processing..." : "Confirm Rejection"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Clarification Item Sub-Component
// ---------------------------------------------------------------------------

function ClarificationItem({
  clarification,
  onAccept,
  onDispute,
  submitting,
}: {
  clarification: Clarification;
  onAccept: (id: number) => void;
  onDispute: (id: number, reason: string) => void;
  submitting: boolean;
}) {
  const [disputeReason, setDisputeReason] = useState("");
  const [showDispute, setShowDispute] = useState(false);

  const statusBadge = CLARIFICATION_STATUS_BADGES[clarification.status] || {
    color: "bg-gray-100 text-gray-700",
    label: clarification.status,
  };

  return (
    <div className={`border rounded-lg p-4 ${clarification.is_blocking ? "border-red-200 bg-red-50/30" : ""}`}>
      <div className="flex items-start justify-between mb-2">
        <div>
          <h4 className="font-medium text-sm">{clarification.question_subject}</h4>
          <div className="flex items-center gap-2 mt-1">
            <Badge variant="outline" className={statusBadge.color}>
              {statusBadge.label}
            </Badge>
            <Badge variant="outline" className={PRIORITY_BADGES[clarification.priority] || "bg-gray-100 text-gray-700"}>
              {clarification.priority}
            </Badge>
            <Badge variant="outline" className="text-xs">
              {QUESTION_TYPE_LABELS[clarification.question_type] || clarification.question_type}
            </Badge>
            {clarification.is_blocking && (
              <Badge variant="outline" className="bg-red-100 text-red-700 border-red-300 text-xs">
                Blocking
              </Badge>
            )}
          </div>
        </div>
        <span className="text-xs text-gray-400">{formatTimestamp(clarification.asked_at)}</span>
      </div>

      <p className="text-sm text-gray-700 mt-2">{clarification.question_body}</p>

      {/* Company Answer */}
      {clarification.answer_body && (
        <div className="mt-3 p-3 bg-blue-50 rounded border border-blue-100">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-semibold text-blue-700">Company Response</span>
            <span className="text-xs text-gray-400">{formatTimestamp(clarification.answered_at)}</span>
          </div>
          <p className="text-sm text-gray-900">{clarification.answer_body}</p>
        </div>
      )}

      {/* Resolution Actions (only for answered clarifications) */}
      {clarification.status === "answered" && (
        <div className="mt-3 flex items-center gap-2">
          <Button
            size="sm"
            variant="outline"
            className="text-green-700 border-green-300 hover:bg-green-50"
            onClick={() => onAccept(clarification.id)}
            disabled={submitting}
          >
            <CheckCircle className="w-3 h-3 mr-1" />
            Accept
          </Button>
          <Button
            size="sm"
            variant="outline"
            className="text-red-700 border-red-300 hover:bg-red-50"
            onClick={() => setShowDispute(!showDispute)}
            disabled={submitting}
          >
            <XCircle className="w-3 h-3 mr-1" />
            Dispute
          </Button>
        </div>
      )}

      {/* Dispute Form */}
      {showDispute && (
        <div className="mt-3 space-y-2">
          <Textarea
            value={disputeReason}
            onChange={(e) => setDisputeReason(e.target.value)}
            placeholder="Reason for disputing this answer..."
            rows={2}
          />
          <Button
            size="sm"
            variant="destructive"
            onClick={() => {
              onDispute(clarification.id, disputeReason);
              setShowDispute(false);
              setDisputeReason("");
            }}
            disabled={submitting || !disputeReason.trim()}
          >
            Submit Dispute
          </Button>
        </div>
      )}

      {/* Resolution Notes */}
      {clarification.resolution_notes && (
        <div className="mt-2 text-xs text-gray-500">
          <span className="font-semibold">Resolution:</span> {clarification.resolution_notes}
        </div>
      )}
    </div>
  );
}
