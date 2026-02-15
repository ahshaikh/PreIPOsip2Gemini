"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Loader2, FileText, AlertTriangle, Clock, RefreshCw } from "lucide-react";
import { toast } from "sonner";
import api from "@/lib/api";
import Link from "next/link";
import { FreshnessIndicator, type ArtifactFreshnessState } from "@/components/disclosures";

interface DisclosureListItem {
  id: number;
  company: {
    id: number;
    name: string;
    lifecycle_state: string;
  };
  module: {
    id: number;
    code: string;
    name: string;
    tier: number;
  };
  status: string;
  submitted_at: string | null;
  review_started_at: string | null;
  clarifications: {
    total: number;
    open: number;
    answered: number;
    accepted: number;
    disputed: number;
    blocking: number;
    overdue: number;
  };
  can_start_review: boolean;
  can_approve: boolean;
  can_reject: boolean;
  can_request_clarification: boolean;
  audit_window_breached: boolean;
  is_terminal: boolean;
  // Freshness tracking (for approved disclosures)
  freshness_state: ArtifactFreshnessState;
  freshness_signal_text?: string;
  days_since_approval?: number;
}

const STATUS_BADGES: Record<string, { color: string; label: string }> = {
  submitted: { color: "bg-amber-100 text-amber-800 border-amber-300", label: "Submitted" },
  resubmitted: { color: "bg-amber-100 text-amber-800 border-amber-300", label: "Resubmitted" },
  under_review: { color: "bg-blue-100 text-blue-800 border-blue-300", label: "Under Review" },
  clarification_required: { color: "bg-orange-100 text-orange-800 border-orange-300", label: "Clarification Required" },
  approved: { color: "bg-green-100 text-green-800 border-green-300", label: "Approved" },
  rejected: { color: "bg-red-100 text-red-800 border-red-300", label: "Rejected" },
};

const TIER_BADGES: Record<number, string> = {
  1: "bg-blue-100 text-blue-700 border-blue-300",
  2: "bg-green-100 text-green-700 border-green-300",
  3: "bg-purple-100 text-purple-700 border-purple-300",
};

function formatDate(dateStr: string | null): string {
  if (!dateStr) return "\u2014";
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays < 1) return "Today";
  if (diffDays < 7) return `${diffDays}d ago`;
  return date.toLocaleDateString();
}

export default function DisclosureListPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [disclosures, setDisclosures] = useState<DisclosureListItem[]>([]);
  const [statusFilter, setStatusFilter] = useState("actionable");
  const [tierFilter, setTierFilter] = useState("all");
  const [freshnessFilter, setFreshnessFilter] = useState("all");

  useEffect(() => {
    loadDisclosures();
  }, [statusFilter, tierFilter, freshnessFilter]);

  async function loadDisclosures() {
    try {
      setLoading(true);
      const params: Record<string, string> = {};

      if (statusFilter !== "all" && statusFilter !== "actionable") {
        params.status = statusFilter;
      } else if (statusFilter === "actionable") {
        params.status = "submitted,resubmitted,under_review,clarification_required";
      }

      if (tierFilter !== "all") {
        params.tier = tierFilter;
      }

      if (freshnessFilter !== "all") {
        params.freshness = freshnessFilter;
      }

      const response = await api.get("/admin/disclosures", { params });

      if (response.data.status === "success") {
        setDisclosures(response.data.data || []);
      } else {
        toast.error("Failed to load disclosures");
      }
    } catch (error: any) {
      console.error("Failed to load disclosures:", error);
      toast.error("Failed to load disclosures");
    } finally {
      setLoading(false);
    }
  }

  function renderClarifications(c: DisclosureListItem["clarifications"]): string {
    if (c.total === 0) return "\u2014";
    const pending = c.open + c.disputed;
    if (pending > 0) return `${pending}/${c.total} pending`;
    return `${c.total} resolved`;
  }

  return (
    <div className="container mx-auto py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">Disclosure Review</h1>
        <p className="text-sm text-gray-500 mt-1">
          Review company disclosure submissions
        </p>
      </div>

      {/* Filters */}
      <div className="flex gap-4 mb-6">
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-[220px]">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="actionable">Actionable</SelectItem>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="submitted">Submitted</SelectItem>
            <SelectItem value="resubmitted">Resubmitted</SelectItem>
            <SelectItem value="under_review">Under Review</SelectItem>
            <SelectItem value="clarification_required">Clarification Required</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>

        <Select value={tierFilter} onValueChange={setTierFilter}>
          <SelectTrigger className="w-[160px]">
            <SelectValue placeholder="Filter by tier" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Tiers</SelectItem>
            <SelectItem value="1">Tier 1</SelectItem>
            <SelectItem value="2">Tier 2</SelectItem>
            <SelectItem value="3">Tier 3</SelectItem>
          </SelectContent>
        </Select>

        <Select value={freshnessFilter} onValueChange={setFreshnessFilter}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by freshness" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Freshness</SelectItem>
            <SelectItem value="stale">Stale</SelectItem>
            <SelectItem value="unstable">Unstable</SelectItem>
            <SelectItem value="aging">Aging</SelectItem>
            <SelectItem value="current">Current</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Table */}
      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="flex items-center justify-center py-16">
              <Loader2 className="w-8 h-8 animate-spin text-purple-600" />
            </div>
          ) : disclosures.length === 0 ? (
            <div className="py-16 text-center">
              <FileText className="w-12 h-12 mx-auto text-gray-400 mb-4" />
              <h3 className="text-lg font-semibold text-gray-700 mb-1">
                No Disclosures Found
              </h3>
              <p className="text-sm text-gray-500">
                No disclosures match the current filters.
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Company</TableHead>
                  <TableHead>Module</TableHead>
                  <TableHead>Tier</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Freshness</TableHead>
                  <TableHead>Submitted</TableHead>
                  <TableHead>Review Started</TableHead>
                  <TableHead>Clarifications</TableHead>
                  <TableHead className="text-right">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {disclosures.map((d) => (
                  <TableRow
                    key={d.id}
                    className="cursor-pointer hover:bg-gray-50"
                    onClick={() => router.push(`/admin/disclosures/${d.id}`)}
                  >
                    <TableCell className="font-medium">
                      <div className="flex items-center gap-2">
                        {d.company.name}
                        {d.audit_window_breached && (
                          <span title="Audit window exceeded">
                            <AlertTriangle className="w-4 h-4 text-amber-500" />
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <span>{d.module.name}</span>
                        <span className="ml-2 text-xs text-gray-400">{d.module.code}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline" className={TIER_BADGES[d.module.tier] || "bg-gray-100 text-gray-700"}>
                        T{d.module.tier}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline" className={STATUS_BADGES[d.status]?.color || "bg-gray-100 text-gray-700"}>
                        {STATUS_BADGES[d.status]?.label || d.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {d.status === "approved" && d.freshness_state ? (
                        <FreshnessIndicator
                          state={d.freshness_state}
                          signalText={d.days_since_approval !== undefined ? `${d.days_since_approval}d` : undefined}
                          variant="badge"
                        />
                      ) : d.status === "approved" ? (
                        <span className="text-xs text-gray-400">—</span>
                      ) : (
                        <span className="text-xs text-gray-400">N/A</span>
                      )}
                    </TableCell>
                    <TableCell className="text-sm text-gray-600">
                      {formatDate(d.submitted_at)}
                    </TableCell>
                    <TableCell className="text-sm text-gray-600">
                      {formatDate(d.review_started_at)}
                    </TableCell>
                    <TableCell className="text-sm text-gray-600">
                      {renderClarifications(d.clarifications)}
                    </TableCell>
                    <TableCell className="text-right">
                      <Link
                        href={`/admin/disclosures/${d.id}`}
                        onClick={(e) => e.stopPropagation()}
                      >
                        <Button variant="outline" size="sm">
                          {d.is_terminal ? "View" : "Review"}
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <div className="mt-4 text-xs text-gray-400">
        {disclosures.length} disclosure{disclosures.length !== 1 ? "s" : ""} shown
      </div>
    </div>
  );
}
