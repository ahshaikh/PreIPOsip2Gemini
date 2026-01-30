/**
 * EPIC 5 Story 5.3 - Clarification Response Panel
 *
 * PURPOSE:
 * Panel for responding to platform-issued clarification requests.
 * Issuers can view questions and submit responses, but cannot
 * control platform timelines or escalation.
 *
 * STORY 5.3 RULES:
 * - Issuer CAN respond to clarifications
 * - Issuer CANNOT close or extend clarifications
 * - Platform timelines and escalation are visible (read-only)
 * - Deadlines and overdue status clearly shown
 *
 * INVARIANT:
 * - Issuer cannot modify clarification state (open/closed/extended)
 * - Deadlines are platform-controlled and immutable by issuer
 */

import { useState } from "react";
import {
  MessageCircle,
  Clock,
  AlertTriangle,
  Send,
  CheckCircle2,
  XCircle,
  Info,
  FileText,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";

// ============================================================================
// TYPES
// ============================================================================

/**
 * Clarification status values
 */
export type ClarificationStatus =
  | "pending"
  | "answered"
  | "accepted"
  | "rejected"
  | "expired"
  | "escalated";

/**
 * Clarification data from backend
 */
export interface ClarificationData {
  id: number;
  disclosure_id: number;
  disclosure_module_name: string;
  question: string;
  status: ClarificationStatus;
  created_at: string;
  due_date: string;
  issuer_response?: string;
  issuer_response_at?: string;
  platform_feedback?: string;
  is_overdue: boolean;
  is_expired: boolean;
  can_respond: boolean;
  escalation_level?: number;
  escalation_reason?: string;
}

interface ClarificationResponsePanelProps {
  clarification: ClarificationData;
  onSubmitResponse?: (clarificationId: number, response: string) => Promise<void>;
  className?: string;
}

// ============================================================================
// STATUS HELPERS
// ============================================================================

/**
 * Get status configuration for display
 */
function getStatusConfig(status: ClarificationStatus): {
  label: string;
  icon: React.ReactNode;
  colorClass: string;
  bgClass: string;
} {
  switch (status) {
    case "pending":
      return {
        label: "Awaiting Response",
        icon: <Clock className="w-4 h-4" />,
        colorClass: "text-amber-700 dark:text-amber-300",
        bgClass: "bg-amber-100 dark:bg-amber-900/30",
      };
    case "answered":
      return {
        label: "Response Submitted",
        icon: <Send className="w-4 h-4" />,
        colorClass: "text-blue-700 dark:text-blue-300",
        bgClass: "bg-blue-100 dark:bg-blue-900/30",
      };
    case "accepted":
      return {
        label: "Accepted",
        icon: <CheckCircle2 className="w-4 h-4" />,
        colorClass: "text-green-700 dark:text-green-300",
        bgClass: "bg-green-100 dark:bg-green-900/30",
      };
    case "rejected":
      return {
        label: "Rejected - Resubmit Required",
        icon: <XCircle className="w-4 h-4" />,
        colorClass: "text-red-700 dark:text-red-300",
        bgClass: "bg-red-100 dark:bg-red-900/30",
      };
    case "expired":
      return {
        label: "Expired",
        icon: <XCircle className="w-4 h-4" />,
        colorClass: "text-gray-700 dark:text-gray-300",
        bgClass: "bg-gray-100 dark:bg-gray-800",
      };
    case "escalated":
      return {
        label: "Escalated",
        icon: <AlertTriangle className="w-4 h-4" />,
        colorClass: "text-red-700 dark:text-red-300",
        bgClass: "bg-red-100 dark:bg-red-900/30",
      };
    default:
      return {
        label: status,
        icon: <Info className="w-4 h-4" />,
        colorClass: "text-gray-700",
        bgClass: "bg-gray-100",
      };
  }
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/**
 * Calculate days remaining until deadline
 */
function getDaysRemaining(dueDate: string): number {
  const due = new Date(dueDate);
  const now = new Date();
  const diff = due.getTime() - now.getTime();
  return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

// ============================================================================
// MAIN COMPONENT
// ============================================================================

export function ClarificationResponsePanel({
  clarification,
  onSubmitResponse,
  className = "",
}: ClarificationResponsePanelProps) {
  const [response, setResponse] = useState(clarification.issuer_response || "");
  const [submitting, setSubmitting] = useState(false);

  const statusConfig = getStatusConfig(clarification.status);
  const daysRemaining = getDaysRemaining(clarification.due_date);
  const canRespond = clarification.can_respond && !clarification.is_expired;

  const handleSubmit = async () => {
    if (!onSubmitResponse || !response.trim()) return;
    setSubmitting(true);
    try {
      await onSubmitResponse(clarification.id, response);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Card className={`${className} ${clarification.is_overdue ? "border-red-300 dark:border-red-700" : ""}`}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <MessageCircle className="w-5 h-5" />
          Platform Clarification Request
        </CardTitle>
        <div className="flex items-center gap-3 mt-2">
          <Badge className={`${statusConfig.bgClass} ${statusConfig.colorClass}`}>
            {statusConfig.icon}
            <span className="ml-1">{statusConfig.label}</span>
          </Badge>
          <Badge variant="outline" className="text-xs">
            {clarification.disclosure_module_name}
          </Badge>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Overdue Warning */}
        {clarification.is_overdue && !clarification.is_expired && (
          <Alert variant="destructive">
            <AlertTriangle className="h-4 w-4" />
            <AlertTitle>Response Overdue</AlertTitle>
            <AlertDescription>
              This clarification request is past its deadline. Please respond immediately
              to avoid escalation or potential account restrictions.
            </AlertDescription>
          </Alert>
        )}

        {/* Expired Warning */}
        {clarification.is_expired && (
          <Alert variant="destructive">
            <XCircle className="h-4 w-4" />
            <AlertTitle>Clarification Expired</AlertTitle>
            <AlertDescription>
              This clarification request has expired. The platform may have taken action
              based on the lack of response. Contact support if you need assistance.
            </AlertDescription>
          </Alert>
        )}

        {/* Escalation Warning */}
        {clarification.escalation_level && clarification.escalation_level > 0 && (
          <Alert className="border-orange-300 bg-orange-50 dark:bg-orange-950/30">
            <AlertTriangle className="h-4 w-4 text-orange-600" />
            <AlertTitle className="text-orange-900 dark:text-orange-100">
              Escalated (Level {clarification.escalation_level})
            </AlertTitle>
            <AlertDescription className="text-orange-800 dark:text-orange-200">
              {clarification.escalation_reason || "This clarification has been escalated for review."}
            </AlertDescription>
          </Alert>
        )}

        {/*
         * STORY 5.3: Platform Timeline (READ-ONLY)
         * Issuer can see deadlines but cannot extend them.
         */}
        <div className="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">
            Platform Timeline (You Cannot Modify)
          </p>
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span className="text-gray-500">Issued:</span>
              <p className="font-medium">{formatDate(clarification.created_at)}</p>
            </div>
            <div>
              <span className="text-gray-500">Due By:</span>
              <p className={`font-medium ${clarification.is_overdue ? "text-red-600" : ""}`}>
                {formatDate(clarification.due_date)}
              </p>
            </div>
            {!clarification.is_expired && (
              <div className="col-span-2">
                <span className="text-gray-500">Time Remaining:</span>
                <p className={`font-medium ${daysRemaining <= 0 ? "text-red-600" : daysRemaining <= 2 ? "text-amber-600" : "text-green-600"}`}>
                  {daysRemaining <= 0
                    ? "Overdue"
                    : daysRemaining === 1
                    ? "1 day"
                    : `${daysRemaining} days`
                  }
                </p>
              </div>
            )}
          </div>
        </div>

        <Separator />

        {/* Platform Question */}
        <div className="space-y-2">
          <Label className="flex items-center gap-2">
            <FileText className="w-4 h-4" />
            Platform Question
          </Label>
          <div className="p-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <p className="text-sm whitespace-pre-wrap">{clarification.question}</p>
          </div>
        </div>

        {/* Previous Response (if any) */}
        {clarification.issuer_response && (
          <div className="space-y-2">
            <Label className="flex items-center gap-2">
              <Send className="w-4 h-4" />
              Your Previous Response
            </Label>
            <div className="p-4 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-200 dark:border-green-800">
              <p className="text-sm whitespace-pre-wrap">{clarification.issuer_response}</p>
              {clarification.issuer_response_at && (
                <p className="text-xs text-gray-500 mt-2">
                  Submitted: {formatDate(clarification.issuer_response_at)}
                </p>
              )}
            </div>
          </div>
        )}

        {/* Platform Feedback (if any) */}
        {clarification.platform_feedback && (
          <div className="space-y-2">
            <Label className="flex items-center gap-2 text-amber-700 dark:text-amber-300">
              <MessageCircle className="w-4 h-4" />
              Platform Feedback
            </Label>
            <div className="p-4 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800">
              <p className="text-sm whitespace-pre-wrap">{clarification.platform_feedback}</p>
            </div>
          </div>
        )}

        {/* Response Form */}
        {canRespond && (
          <>
            <Separator />
            <div className="space-y-3">
              <Label htmlFor="response">Your Response</Label>
              <Textarea
                id="response"
                value={response}
                onChange={(e) => setResponse(e.target.value)}
                placeholder="Enter your response to the platform's clarification request..."
                className="min-h-[150px]"
              />
              <div className="flex items-center justify-between">
                <p className="text-xs text-gray-500">
                  Your response will be reviewed by the platform. Be thorough and accurate.
                </p>
                <Button
                  onClick={handleSubmit}
                  disabled={submitting || !response.trim()}
                >
                  {submitting ? (
                    <>Submitting...</>
                  ) : (
                    <>
                      <Send className="w-4 h-4 mr-2" />
                      Submit Response
                    </>
                  )}
                </Button>
              </div>
            </div>
          </>
        )}

        {/* Cannot Respond Notice */}
        {!canRespond && !clarification.is_expired && (
          <Alert className="border-gray-200 bg-gray-50 dark:bg-gray-900/30">
            <Info className="h-4 w-4 text-gray-500" />
            <AlertDescription className="text-sm text-gray-600 dark:text-gray-400">
              {clarification.status === "answered"
                ? "Your response has been submitted and is awaiting platform review."
                : clarification.status === "accepted"
                ? "This clarification has been resolved and accepted by the platform."
                : "You cannot respond to this clarification at this time."
              }
            </AlertDescription>
          </Alert>
        )}

        {/* Control Limitation Notice */}
        <Alert className="border-gray-200 bg-gray-50 dark:bg-gray-900/30">
          <Info className="h-4 w-4 text-gray-500" />
          <AlertDescription className="text-xs text-gray-600 dark:text-gray-400">
            You can respond to clarifications but cannot close, extend, or modify deadlines.
            Platform timelines and escalation are controlled by PreIPOsip governance.
          </AlertDescription>
        </Alert>
      </CardContent>
    </Card>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

export { getStatusConfig, formatDate, getDaysRemaining };
export default ClarificationResponsePanel;
