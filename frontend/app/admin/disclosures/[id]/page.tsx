"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Loader2, CheckCircle, XCircle, ArrowLeft, Building2, FileText } from "lucide-react";
import { toast } from "sonner";
import api from "@/lib/api";
import Link from "next/link";

// FIX: Updated interface to match backend nested response structure
interface DisclosureDetail {
  disclosure: {
    id: number;
    status: string;
    version_number: number;
    completion_percentage: number;
    disclosure_data: any;
    attachments: any[];
    submitted_at: string;
    submitted_by: number | null;
    review_started_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    rejection_reason: string | null;
    is_locked: boolean;
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
  clarifications: any[];
  summary: any;
}

export default function DisclosureDetailPage() {
  const params = useParams();
  const router = useRouter();
  const disclosureId = params?.id;

  const [loading, setLoading] = useState(true);
  const [disclosure, setDisclosure] = useState<DisclosureDetail | null>(null);
  const [reviewNotes, setReviewNotes] = useState("");
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (disclosureId) {
      loadDisclosure();
    }
  }, [disclosureId]);

  async function loadDisclosure() {
    try {
      setLoading(true);
      const response = await api.get(`/admin/disclosures/${disclosureId}`);

      // FIX: Backend returns { status: 'success', data: {...} } not { success: true }
      if (response.data.status === 'success') {
        setDisclosure(response.data.data);
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

  async function handleApprove() {
    if (!disclosure) return;

    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosure.disclosure.id}/approve`, {
        notes: reviewNotes,
      });

      if (response.data.success) {
        toast.success("Disclosure approved successfully");
        router.push("/admin/disclosures/pending");
      } else {
        toast.error(response.data.message || "Failed to approve disclosure");
      }
    } catch (error: any) {
      console.error("Failed to approve:", error);
      toast.error("Failed to approve disclosure");
    } finally {
      setSubmitting(false);
    }
  }

  async function handleReject() {
    if (!disclosure) return;

    if (!reviewNotes.trim()) {
      toast.error("Please provide rejection reason");
      return;
    }

    try {
      setSubmitting(true);
      const response = await api.post(`/admin/disclosures/${disclosure.disclosure.id}/reject`, {
        reason: reviewNotes,
      });

      if (response.data.success) {
        toast.success("Disclosure rejected");
        router.push("/admin/disclosures/pending");
      } else {
        toast.error(response.data.message || "Failed to reject disclosure");
      }
    } catch (error: any) {
      console.error("Failed to reject:", error);
      toast.error("Failed to reject disclosure");
    } finally {
      setSubmitting(false);
    }
  }

  if (loading) {
    return (
      <div className="container mx-auto py-8">
        <div className="flex items-center justify-center min-h-[400px]">
          <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
        </div>
      </div>
    );
  }

  if (!disclosure) {
    return (
      <div className="container mx-auto py-8">
        <Card>
          <CardContent className="py-12 text-center">
            <XCircle className="w-12 h-12 mx-auto text-red-500 mb-4" />
            <h3 className="text-lg font-semibold mb-2">Disclosure Not Found</h3>
            <Link href="/admin/disclosures/pending">
              <Button variant="outline" className="mt-4">
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back to Pending
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-8">
      <Link
        href="/admin/disclosures/pending"
        className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 mb-6"
      >
        <ArrowLeft className="w-4 h-4 mr-2" />
        Back to Pending Disclosures
      </Link>

      <div className="space-y-6">
        {/* Disclosure Header - FIX: Updated to use nested structure */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="flex items-center gap-3">
                  <Building2 className="w-6 h-6 text-purple-600" />
                  {disclosure.company.name}
                </CardTitle>
                <p className="text-sm text-gray-500 mt-1">
                  Reviewing disclosure submission
                </p>
              </div>
              <Badge className="bg-blue-100 text-blue-700 border-blue-300">
                Tier {disclosure.module.tier}
              </Badge>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span className="text-gray-500">Module:</span>
                <p className="font-medium">{disclosure.module.name}</p>
              </div>
              <div>
                <span className="text-gray-500">Status:</span>
                <p className="font-medium capitalize">{disclosure.disclosure.status}</p>
              </div>
              <div>
                <span className="text-gray-500">Submitted:</span>
                <p className="font-medium">
                  {disclosure.disclosure.submitted_at ? new Date(disclosure.disclosure.submitted_at).toLocaleString() : 'N/A'}
                </p>
              </div>
              <div>
                <span className="text-gray-500">Version:</span>
                <p className="font-medium">v{disclosure.disclosure.version_number}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Disclosure Content */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="w-5 h-5" />
              Disclosure Data
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
              <pre className="text-sm overflow-x-auto">
                {JSON.stringify(disclosure.disclosure.disclosure_data, null, 2)}
              </pre>
            </div>
          </CardContent>
        </Card>

        {/* Review Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Review & Decision</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="notes">Review Notes {disclosure.disclosure.status === "pending" && "(Required for rejection)"}</Label>
              <Textarea
                id="notes"
                placeholder="Add your review notes here..."
                value={reviewNotes}
                onChange={(e) => setReviewNotes(e.target.value)}
                rows={5}
                className="mt-2"
              />
            </div>

            <div className="flex gap-3 pt-4">
              <Button
                onClick={handleApprove}
                disabled={submitting}
                className="bg-green-600 hover:bg-green-700"
              >
                {submitting ? (
                  <>
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                    Processing...
                  </>
                ) : (
                  <>
                    <CheckCircle className="w-4 h-4 mr-2" />
                    Approve Disclosure
                  </>
                )}
              </Button>

              <Button
                onClick={handleReject}
                disabled={submitting || !reviewNotes.trim()}
                variant="destructive"
              >
                {submitting ? (
                  <>
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                    Processing...
                  </>
                ) : (
                  <>
                    <XCircle className="w-4 h-4 mr-2" />
                    Reject Disclosure
                  </>
                )}
              </Button>
            </div>

            <p className="text-xs text-gray-500 mt-4">
              <strong>Note:</strong> Approving all required Tier 2 modules for a company will automatically
              enable investment buying and transition the company to "live_investable" state.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
