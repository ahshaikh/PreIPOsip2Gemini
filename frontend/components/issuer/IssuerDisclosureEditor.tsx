/**
 * EPIC 5 Story 5.3 - Issuer Disclosure Editor
 *
 * PURPOSE:
 * Disclosure editing component with review-state-driven editability.
 * Transforms issuer UI from "form editor" to constrained interface
 * enforcing platform governance.
 *
 * STORY 5.3 EDITABILITY RULES:
 * - Draft sections: EDITABLE
 * - Under-review sections: Conditionally editable or READ-ONLY
 * - Approved sections: LOCKED (unless reopened by platform)
 * - Rejected sections: Show rejection reason + corrective guidance
 *
 * HARD INVARIANT:
 * - ❌ Issuer UI must NEVER allow mutation of locked fields in client state
 * - ❌ No hidden or disabled-but-bound inputs for locked disclosures
 * - ✅ Locked disclosures are rendered as READ-ONLY VIEWS, not disabled forms
 *
 * This means: When a disclosure is locked, we render a VIEW component,
 * NOT a form with disabled inputs. This prevents any accidental mutation
 * at the client state level.
 */

import { useState } from "react";
import {
  FileText,
  Lock,
  Edit3,
  Clock,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Info,
  Save,
  Send,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";

// ============================================================================
// TYPES
// ============================================================================

/**
 * Disclosure status values
 */
export type DisclosureStatus =
  | "draft"
  | "submitted"
  | "under_review"
  | "clarification_required"
  | "resubmitted"
  | "approved"
  | "rejected";

/**
 * Disclosure data from backend
 */
export interface DisclosureData {
  id: number;
  module_id: number;
  module_name: string;
  module_code: string;
  status: DisclosureStatus;
  version: number;
  data: Record<string, any>;
  can_edit: boolean;
  can_submit: boolean;
  rejection_reason?: string;
  rejection_guidance?: string;
  approved_at?: string;
  submitted_at?: string;
  clarification_pending?: boolean;
}

/**
 * Field schema for disclosure module
 */
export interface FieldSchema {
  key: string;
  label: string;
  type: "text" | "textarea" | "number" | "date" | "select" | "boolean";
  required: boolean;
  description?: string;
  options?: { value: string; label: string }[];
  validation?: {
    min?: number;
    max?: number;
    pattern?: string;
  };
}

/**
 * Module schema from backend
 */
export interface ModuleSchema {
  module_code: string;
  module_name: string;
  fields: FieldSchema[];
  description?: string;
}

interface IssuerDisclosureEditorProps {
  disclosure: DisclosureData;
  schema?: ModuleSchema;
  platformRestricted?: boolean;
  onSaveDraft?: (data: Record<string, any>) => Promise<void>;
  onSubmit?: (data: Record<string, any>) => Promise<void>;
  className?: string;
}

// ============================================================================
// STATUS HELPERS
// ============================================================================

/**
 * Get status configuration for display
 */
function getStatusConfig(status: DisclosureStatus): {
  label: string;
  icon: React.ReactNode;
  colorClass: string;
  bgClass: string;
} {
  switch (status) {
    case "draft":
      return {
        label: "Draft",
        icon: <Edit3 className="w-4 h-4" />,
        colorClass: "text-gray-700 dark:text-gray-300",
        bgClass: "bg-gray-100 dark:bg-gray-800",
      };
    case "submitted":
    case "under_review":
    case "resubmitted":
      return {
        label: "Under Review",
        icon: <Clock className="w-4 h-4" />,
        colorClass: "text-amber-700 dark:text-amber-300",
        bgClass: "bg-amber-100 dark:bg-amber-900/30",
      };
    case "clarification_required":
      return {
        label: "Clarification Required",
        icon: <AlertTriangle className="w-4 h-4" />,
        colorClass: "text-orange-700 dark:text-orange-300",
        bgClass: "bg-orange-100 dark:bg-orange-900/30",
      };
    case "approved":
      return {
        label: "Approved",
        icon: <CheckCircle2 className="w-4 h-4" />,
        colorClass: "text-green-700 dark:text-green-300",
        bgClass: "bg-green-100 dark:bg-green-900/30",
      };
    case "rejected":
      return {
        label: "Rejected",
        icon: <XCircle className="w-4 h-4" />,
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
 * Check if disclosure is in editable state
 */
function isEditable(disclosure: DisclosureData, platformRestricted: boolean): boolean {
  // Platform restrictions always take precedence
  if (platformRestricted) return false;

  // Backend-provided permission is authoritative
  if (!disclosure.can_edit) return false;

  // Only draft and clarification_required are editable
  return disclosure.status === "draft" || disclosure.status === "clarification_required";
}

/**
 * Check if disclosure is locked (should show read-only view)
 */
function isLocked(disclosure: DisclosureData): boolean {
  return disclosure.status === "approved" ||
         disclosure.status === "under_review" ||
         disclosure.status === "submitted" ||
         disclosure.status === "resubmitted";
}

// ============================================================================
// READ-ONLY VIEW COMPONENT
// ============================================================================

/**
 * HARD INVARIANT: Locked disclosures render as read-only views, NOT disabled forms.
 * This component renders disclosure data without any input elements.
 */
function DisclosureReadOnlyView({
  disclosure,
  schema,
}: {
  disclosure: DisclosureData;
  schema?: ModuleSchema;
}) {
  const statusConfig = getStatusConfig(disclosure.status);

  return (
    <div className="space-y-4">
      {/* Locked Status Banner */}
      <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
        <Lock className="h-4 w-4 text-blue-600" />
        <AlertTitle className="text-blue-900 dark:text-blue-100">
          Disclosure Locked
        </AlertTitle>
        <AlertDescription className="text-blue-800 dark:text-blue-200">
          {disclosure.status === "approved" && (
            <span>This disclosure has been approved and is now visible to investors. It cannot be edited unless reopened by the platform.</span>
          )}
          {(disclosure.status === "under_review" || disclosure.status === "submitted" || disclosure.status === "resubmitted") && (
            <span>This disclosure is currently under platform review. Edits are temporarily locked until review is complete.</span>
          )}
        </AlertDescription>
      </Alert>

      {/* Version & Status Info */}
      <div className="flex items-center gap-4 text-sm">
        <Badge className={`${statusConfig.bgClass} ${statusConfig.colorClass}`}>
          {statusConfig.icon}
          <span className="ml-1">{statusConfig.label}</span>
        </Badge>
        <span className="text-gray-500">Version {disclosure.version}</span>
        {disclosure.approved_at && (
          <span className="text-gray-500">
            Approved: {new Date(disclosure.approved_at).toLocaleDateString()}
          </span>
        )}
      </div>

      {/* Data Display - READ ONLY */}
      <div className="space-y-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700">
        {schema?.fields ? (
          // Render using schema
          schema.fields.map((field) => (
            <div key={field.key} className="space-y-1">
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {field.label}
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100">
                {disclosure.data[field.key] !== undefined && disclosure.data[field.key] !== null
                  ? String(disclosure.data[field.key])
                  : <span className="italic text-gray-400">Not provided</span>
                }
              </p>
            </div>
          ))
        ) : (
          // Fallback: render raw data
          Object.entries(disclosure.data).map(([key, value]) => (
            <div key={key} className="space-y-1">
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {key.replace(/_/g, " ")}
              </p>
              <p className="text-sm text-gray-900 dark:text-gray-100">
                {value !== undefined && value !== null
                  ? String(value)
                  : <span className="italic text-gray-400">Not provided</span>
                }
              </p>
            </div>
          ))
        )}
      </div>
    </div>
  );
}

// ============================================================================
// REJECTION VIEW COMPONENT
// ============================================================================

/**
 * Rejected disclosures show rejection reason + corrective guidance.
 * Still allows editing to make corrections.
 */
function DisclosureRejectionBanner({
  disclosure,
}: {
  disclosure: DisclosureData;
}) {
  if (disclosure.status !== "rejected") return null;

  return (
    <Alert variant="destructive" className="mb-4">
      <XCircle className="h-5 w-5" />
      <AlertTitle>Disclosure Rejected</AlertTitle>
      <AlertDescription>
        {disclosure.rejection_reason && (
          <div className="mt-2">
            <p className="font-semibold text-sm">Reason:</p>
            <p className="text-sm">{disclosure.rejection_reason}</p>
          </div>
        )}
        {disclosure.rejection_guidance && (
          <div className="mt-3 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
            <p className="font-semibold text-sm">Corrective Guidance:</p>
            <p className="text-sm">{disclosure.rejection_guidance}</p>
          </div>
        )}
        <p className="text-sm mt-3 font-medium">
          Please review the feedback above and make the necessary corrections before resubmitting.
        </p>
      </AlertDescription>
    </Alert>
  );
}

// ============================================================================
// EDITABLE FORM COMPONENT
// ============================================================================

/**
 * Editable form for draft and clarification_required states.
 * Only renders when disclosure is actually editable.
 */
function DisclosureEditableForm({
  disclosure,
  schema,
  onSaveDraft,
  onSubmit,
}: {
  disclosure: DisclosureData;
  schema?: ModuleSchema;
  onSaveDraft?: (data: Record<string, any>) => Promise<void>;
  onSubmit?: (data: Record<string, any>) => Promise<void>;
}) {
  const [formData, setFormData] = useState<Record<string, any>>(disclosure.data || {});
  const [saving, setSaving] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleFieldChange = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const handleSaveDraft = async () => {
    if (!onSaveDraft) return;
    setSaving(true);
    try {
      await onSaveDraft(formData);
    } finally {
      setSaving(false);
    }
  };

  const handleSubmit = async () => {
    if (!onSubmit) return;
    setSubmitting(true);
    try {
      await onSubmit(formData);
    } finally {
      setSubmitting(false);
    }
  };

  const statusConfig = getStatusConfig(disclosure.status);

  return (
    <div className="space-y-4">
      {/* Status Banner */}
      <div className="flex items-center gap-4 text-sm">
        <Badge className={`${statusConfig.bgClass} ${statusConfig.colorClass}`}>
          {statusConfig.icon}
          <span className="ml-1">{statusConfig.label}</span>
        </Badge>
        <span className="text-gray-500">Version {disclosure.version}</span>
        <Badge variant="outline" className="text-green-600 border-green-600">
          <Edit3 className="w-3 h-3 mr-1" />
          Editable
        </Badge>
      </div>

      {/* Form Fields */}
      <div className="space-y-4">
        {schema?.fields ? (
          // Render using schema
          schema.fields.map((field) => (
            <div key={field.key} className="space-y-2">
              <Label htmlFor={field.key} className="flex items-center gap-1">
                {field.label}
                {field.required && <span className="text-red-500">*</span>}
              </Label>
              {field.description && (
                <p className="text-xs text-gray-500">{field.description}</p>
              )}
              {field.type === "textarea" ? (
                <Textarea
                  id={field.key}
                  value={formData[field.key] || ""}
                  onChange={(e) => handleFieldChange(field.key, e.target.value)}
                  className="min-h-[100px]"
                />
              ) : field.type === "select" && field.options ? (
                <select
                  id={field.key}
                  value={formData[field.key] || ""}
                  onChange={(e) => handleFieldChange(field.key, e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-slate-800"
                >
                  <option value="">Select...</option>
                  {field.options.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              ) : (
                <Input
                  id={field.key}
                  type={field.type === "number" ? "number" : field.type === "date" ? "date" : "text"}
                  value={formData[field.key] || ""}
                  onChange={(e) => handleFieldChange(field.key, e.target.value)}
                />
              )}
            </div>
          ))
        ) : (
          // Fallback: render raw data as text fields
          Object.entries(disclosure.data || {}).map(([key, value]) => (
            <div key={key} className="space-y-2">
              <Label htmlFor={key}>{key.replace(/_/g, " ")}</Label>
              <Input
                id={key}
                value={formData[key] || ""}
                onChange={(e) => handleFieldChange(key, e.target.value)}
              />
            </div>
          ))
        )}
      </div>

      {/* Action Buttons */}
      <Separator />
      <div className="flex justify-end gap-3">
        <Button
          variant="outline"
          onClick={handleSaveDraft}
          disabled={saving || submitting}
        >
          {saving ? (
            <>Saving...</>
          ) : (
            <>
              <Save className="w-4 h-4 mr-2" />
              Save Draft
            </>
          )}
        </Button>
        {disclosure.can_submit && (
          <Button
            onClick={handleSubmit}
            disabled={saving || submitting}
          >
            {submitting ? (
              <>Submitting...</>
            ) : (
              <>
                <Send className="w-4 h-4 mr-2" />
                Submit for Review
              </>
            )}
          </Button>
        )}
      </div>
    </div>
  );
}

// ============================================================================
// MAIN COMPONENT
// ============================================================================

export function IssuerDisclosureEditor({
  disclosure,
  schema,
  platformRestricted = false,
  onSaveDraft,
  onSubmit,
  className = "",
}: IssuerDisclosureEditorProps) {
  const editable = isEditable(disclosure, platformRestricted);
  const locked = isLocked(disclosure);

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <FileText className="w-5 h-5" />
          {disclosure.module_name}
          {locked && <Lock className="w-4 h-4 text-blue-600 ml-auto" />}
        </CardTitle>
        {schema?.description && (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {schema.description}
          </p>
        )}
      </CardHeader>
      <CardContent>
        {/* Platform Restriction Warning */}
        {platformRestricted && (
          <Alert variant="destructive" className="mb-4">
            <Lock className="h-4 w-4" />
            <AlertTitle>Platform Restriction Active</AlertTitle>
            <AlertDescription>
              Editing is blocked due to platform restrictions on your company.
              Contact platform support for assistance.
            </AlertDescription>
          </Alert>
        )}

        {/* Rejection Banner (if applicable) */}
        <DisclosureRejectionBanner disclosure={disclosure} />

        {/*
         * HARD INVARIANT:
         * - If locked → render READ-ONLY VIEW (no form, no inputs)
         * - If editable → render EDITABLE FORM
         *
         * This ensures no accidental client-state mutation of locked data.
         */}
        {locked ? (
          <DisclosureReadOnlyView disclosure={disclosure} schema={schema} />
        ) : editable ? (
          <DisclosureEditableForm
            disclosure={disclosure}
            schema={schema}
            onSaveDraft={onSaveDraft}
            onSubmit={onSubmit}
          />
        ) : (
          // Fallback: show read-only if neither locked nor editable
          // (e.g., rejected but platform restricted)
          <DisclosureReadOnlyView disclosure={disclosure} schema={schema} />
        )}
      </CardContent>
    </Card>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

export { isEditable, isLocked, getStatusConfig };
export default IssuerDisclosureEditor;
