/**
 * PHASE 5 - Admin/Platform Frontend: Company Management with Visibility Controls
 *
 * PURPOSE:
 * - Exercise platform authority over companies
 * - CRITICAL: Independent visibility controls (public/subscriber)
 * - Platform context management
 * - Risk flag management
 * - Tier approval
 * - Governance actions (suspend, freeze, investigate)
 *
 * DEFENSIVE PRINCIPLES:
 * - Visibility changes show impact preview BEFORE applying
 * - All actions require explicit reason
 * - Platform authority clearly marked
 * - Snapshot awareness enforced (historical immutability noted)
 * - Audit trail visibility
 */

"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  Building2,
  Eye,
  EyeOff,
  ShieldAlert,
  Lock,
  Unlock,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  History,
  ArrowLeft,
  Loader2,
  Save,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Separator } from "@/components/ui/separator";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  fetchAdminCompanyDetail,
  previewVisibilityChange,
  updateCompanyVisibility,
  updatePlatformContext,
  AdminCompanyDetail,
  VisibilityChangeImpact,
} from "@/lib/adminCompanyApi";

export default function AdminCompanyManagementPage() {
  const { id } = useParams();
  const router = useRouter();

  const [company, setCompany] = useState<AdminCompanyDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Visibility controls state
  const [visiblePublic, setVisiblePublic] = useState(false);
  const [visibleSubscribers, setVisibleSubscribers] = useState(false);
  const [visibilityChanged, setVisibilityChanged] = useState(false);

  // Impact preview
  const [showImpactPreview, setShowImpactPreview] = useState(false);
  const [impactPreview, setImpactPreview] = useState<VisibilityChangeImpact | null>(null);
  const [visibilityReason, setVisibilityReason] = useState("");
  const [savingVisibility, setSavingVisibility] = useState(false);

  // Platform context controls
  const [isSuspended, setIsSuspended] = useState(false);
  const [isFrozen, setIsFrozen] = useState(false);
  const [buyingEnabled, setBuyingEnabled] = useState(true);
  const [platformContextChanged, setPlatformContextChanged] = useState(false);

  // Load company data
  useEffect(() => {
    async function loadCompany() {
      if (!id || typeof id !== "string") return;

      setLoading(true);
      setError(null);

      try {
        const companyId = parseInt(id);
        const data = await fetchAdminCompanyDetail(companyId);

        setCompany(data);

        // Initialize visibility controls
        setVisiblePublic(data.is_visible_public);
        setVisibleSubscribers(data.is_visible_subscribers);

        // Initialize platform context controls
        setIsSuspended(data.platform_context.is_suspended);
        setIsFrozen(data.platform_context.is_frozen);
        setBuyingEnabled(data.platform_context.buying_enabled);
      } catch (err: any) {
        console.error("[ADMIN COMPANY] Failed to load:", err);
        setError("Unable to load company. Please try again later.");
        toast.error("Failed to load company");
      } finally {
        setLoading(false);
      }
    }

    loadCompany();
  }, [id]);

  // Track visibility changes
  useEffect(() => {
    if (!company) return;
    const changed =
      visiblePublic !== company.is_visible_public ||
      visibleSubscribers !== company.is_visible_subscribers;
    setVisibilityChanged(changed);
  }, [visiblePublic, visibleSubscribers, company]);

  // Track platform context changes
  useEffect(() => {
    if (!company) return;
    const changed =
      isSuspended !== company.platform_context.is_suspended ||
      isFrozen !== company.platform_context.is_frozen ||
      buyingEnabled !== company.platform_context.buying_enabled;
    setPlatformContextChanged(changed);
  }, [isSuspended, isFrozen, buyingEnabled, company]);

  // Handle visibility change preview
  const handlePreviewVisibilityChange = async () => {
    if (!company) return;

    try {
      const changes: any = {};
      if (visiblePublic !== company.is_visible_public) {
        changes.is_visible_public = visiblePublic;
      }
      if (visibleSubscribers !== company.is_visible_subscribers) {
        changes.is_visible_subscribers = visibleSubscribers;
      }

      const impact = await previewVisibilityChange(company.id, changes);
      setImpactPreview(impact);
      setShowImpactPreview(true);
    } catch (err: any) {
      console.error("[ADMIN] Failed to preview impact:", err);
      toast.error("Failed to preview impact");
    }
  };

  // Handle visibility save
  const handleSaveVisibility = async () => {
    if (!company || !visibilityReason.trim()) {
      toast.error("Please provide a reason for this change");
      return;
    }

    setSavingVisibility(true);

    try {
      const result = await updateCompanyVisibility(
        company.id,
        {
          is_visible_public: visiblePublic,
          is_visible_subscribers: visibleSubscribers,
        },
        visibilityReason
      );

      if (result.success) {
        toast.success("Visibility updated successfully");
        setShowImpactPreview(false);
        setVisibilityReason("");
        setVisibilityChanged(false);

        // Reload company data
        const updatedData = await fetchAdminCompanyDetail(company.id);
        setCompany(updatedData);
      }
    } catch (err: any) {
      console.error("[ADMIN] Failed to save visibility:", err);
      toast.error("Failed to update visibility");
    } finally {
      setSavingVisibility(false);
    }
  };

  // Handle platform context save
  const handleSavePlatformContext = async () => {
    if (!company) return;

    const reason = prompt("Please provide a reason for this platform action:");
    if (!reason || !reason.trim()) {
      toast.error("Reason is required for platform actions");
      return;
    }

    try {
      const result = await updatePlatformContext(
        company.id,
        {
          is_suspended: isSuspended,
          is_frozen: isFrozen,
          buying_enabled: buyingEnabled,
        },
        reason
      );

      if (result.success) {
        toast.success("Platform context updated successfully");
        setPlatformContextChanged(false);

        // Reload company data
        const updatedData = await fetchAdminCompanyDetail(company.id);
        setCompany(updatedData);
      }
    } catch (err: any) {
      console.error("[ADMIN] Failed to save platform context:", err);
      toast.error("Failed to update platform context");
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600" />
          <p className="text-gray-600">Loading company...</p>
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
          <AlertTitle>Error Loading Company</AlertTitle>
          <AlertDescription>{error || "Company not found"}</AlertDescription>
        </Alert>
        <div className="mt-4">
          <Link href="/admin/companies">
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Companies
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container py-8">
      {/* Header */}
      <div className="mb-8">
        <Link
          href="/admin/companies"
          className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 mb-4 transition-colors"
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back to All Companies
        </Link>

        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-4xl font-bold mb-2">{company.name}</h1>
            <p className="text-gray-600 dark:text-gray-400">Platform Company Management</p>
          </div>

          <Link href={`/admin/companies/${company.id}/audit-trail`}>
            <Button variant="outline">
              <History className="w-4 h-4 mr-2" />
              View Audit Trail
            </Button>
          </Link>
        </div>
      </div>

      {/* CRITICAL: Visibility Controls */}
      <Card className="mb-6 border-red-300 dark:border-red-800 bg-red-50/30 dark:bg-red-950/20">
        <CardHeader>
          <CardTitle className="text-red-900 dark:text-red-200 flex items-center">
            <Eye className="w-5 h-5 mr-2" />
            Company Visibility Controls (PLATFORM AUTHORITY)
          </CardTitle>
          <p className="text-sm text-red-700 dark:text-red-300">
            These controls are independent and override all other settings. Changes affect discovery
            and investment eligibility immediately.
          </p>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Public Visibility Toggle */}
          <div className="flex items-start justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-white dark:bg-slate-900">
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                {visiblePublic ? (
                  <Eye className="w-5 h-5 text-green-600" />
                ) : (
                  <EyeOff className="w-5 h-5 text-red-600" />
                )}
                <Label htmlFor="visible-public" className="text-lg font-semibold cursor-pointer">
                  Visible on Public Site
                </Label>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-400 ml-8">
                Controls appearance on <code>/products</code> and all public listing views. When
                disabled, company is immediately removed from public discovery. Does not affect
                existing investors.
              </p>
            </div>
            <Switch
              id="visible-public"
              checked={visiblePublic}
              onCheckedChange={setVisiblePublic}
              className="ml-4"
            />
          </div>

          {/* Subscriber Visibility Toggle */}
          <div className="flex items-start justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-white dark:bg-slate-900">
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                {visibleSubscribers ? (
                  <Eye className="w-5 h-5 text-green-600" />
                ) : (
                  <EyeOff className="w-5 h-5 text-red-600" />
                )}
                <Label htmlFor="visible-subscribers" className="text-lg font-semibold cursor-pointer">
                  Visible to Subscribers
                </Label>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-400 ml-8">
                Controls appearance on <code>/deals</code> for logged-in investors. When disabled,
                company is removed from deal listings and new investments are blocked. Does not
                affect historical snapshots.
              </p>
            </div>
            <Switch
              id="visible-subscribers"
              checked={visibleSubscribers}
              onCheckedChange={setVisibleSubscribers}
              className="ml-4"
            />
          </div>

          {/* Current State Summary */}
          <Alert className="border-blue-300 bg-blue-50 dark:bg-blue-950/30">
            <AlertDescription className="text-sm text-blue-800 dark:text-blue-200">
              <strong>Current State:</strong>
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>
                  Public Site: <strong>{company.is_visible_public ? "VISIBLE" : "HIDDEN"}</strong>
                </li>
                <li>
                  Subscribers: <strong>{company.is_visible_subscribers ? "VISIBLE" : "HIDDEN"}</strong>
                </li>
                <li>
                  Existing Investors: {company.investor_snapshots.total_investors} (unaffected by visibility changes)
                </li>
              </ul>
            </AlertDescription>
          </Alert>

          {/* Save Visibility Button */}
          {visibilityChanged && (
            <Button
              onClick={handlePreviewVisibilityChange}
              size="lg"
              className="w-full bg-red-600 hover:bg-red-700"
            >
              <Save className="w-4 h-4 mr-2" />
              Preview Impact & Save Visibility Changes
            </Button>
          )}
        </CardContent>
      </Card>

      {/* Platform Governance Controls */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center">
            <ShieldAlert className="w-5 h-5 mr-2" />
            Platform Governance Controls
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Suspension */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="suspended" className="font-semibold">
                Company Suspended
              </Label>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Blocks all issuer actions and new investments
              </p>
            </div>
            <Switch id="suspended" checked={isSuspended} onCheckedChange={setIsSuspended} />
          </div>

          {/* Freeze */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="frozen" className="font-semibold">
                Disclosures Frozen
              </Label>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Prevents issuer from editing or submitting disclosures
              </p>
            </div>
            <Switch id="frozen" checked={isFrozen} onCheckedChange={setIsFrozen} />
          </div>

          {/* Buying Enabled */}
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div>
              <Label htmlFor="buying" className="font-semibold">
                Buying Enabled
              </Label>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Controls whether investors can make new investments
              </p>
            </div>
            <Switch id="buying" checked={buyingEnabled} onCheckedChange={setBuyingEnabled} />
          </div>

          {platformContextChanged && (
            <Button onClick={handleSavePlatformContext} size="lg" className="w-full">
              <Save className="w-4 h-4 mr-2" />
              Save Platform Context Changes
            </Button>
          )}
        </CardContent>
      </Card>

      {/* Tier Status */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Tier Approval Status</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-3 gap-4">
            {Object.entries(company.platform_context.tier_status).map(([tier, approved]) => {
              if (tier.includes("_at")) return null;
              return (
                <div key={tier} className="flex items-center justify-between p-4 border rounded-lg">
                  <span className="font-semibold capitalize">{tier.replace("_", " ")}</span>
                  {approved ? (
                    <Badge className="bg-green-600 text-white">
                      <CheckCircle2 className="w-3 h-3 mr-1" />
                      Approved
                    </Badge>
                  ) : (
                    <Badge variant="outline" className="text-gray-600">
                      Pending
                    </Badge>
                  )}
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Investor Snapshots (Read-only) */}
      <Card className="mb-6 border-blue-200 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-950/20">
        <CardHeader>
          <CardTitle className="text-blue-900 dark:text-blue-200">
            Investor Snapshots (Immutable)
          </CardTitle>
          <p className="text-sm text-blue-700 dark:text-blue-300">
            Historical snapshots are permanently frozen and cannot be modified by platform actions.
          </p>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-4 gap-4 text-center">
            <div>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {company.investor_snapshots.total_investors}
              </p>
              <p className="text-sm text-blue-700 dark:text-blue-300">Total Investors</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {company.investor_snapshots.total_investments}
              </p>
              <p className="text-sm text-blue-700 dark:text-blue-300">Total Investments</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {company.investor_snapshots.snapshot_count}
              </p>
              <p className="text-sm text-blue-700 dark:text-blue-300">Snapshots Created</p>
            </div>
            <div>
              <p className="text-sm font-semibold text-blue-900 dark:text-blue-100">
                {company.investor_snapshots.latest_snapshot_at
                  ? new Date(company.investor_snapshots.latest_snapshot_at).toLocaleDateString()
                  : "N/A"}
              </p>
              <p className="text-sm text-blue-700 dark:text-blue-300">Latest Snapshot</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Impact Preview Dialog */}
      <Dialog open={showImpactPreview} onOpenChange={setShowImpactPreview}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Visibility Change Impact Preview</DialogTitle>
            <DialogDescription>
              Review the impact of your visibility changes before applying them.
            </DialogDescription>
          </DialogHeader>

          {impactPreview && (
            <div className="space-y-4">
              {/* Current vs Proposed */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Changes Summary</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  {impactPreview.proposed_change.is_visible_public !== undefined && (
                    <div className="flex justify-between items-center">
                      <span>Public Visibility</span>
                      <div className="flex items-center gap-3">
                        <Badge variant={impactPreview.current_state.is_visible_public ? "default" : "destructive"}>
                          {impactPreview.current_state.is_visible_public ? "Visible" : "Hidden"}
                        </Badge>
                        <span>→</span>
                        <Badge variant={impactPreview.proposed_change.is_visible_public ? "default" : "destructive"}>
                          {impactPreview.proposed_change.is_visible_public ? "Visible" : "Hidden"}
                        </Badge>
                      </div>
                    </div>
                  )}

                  {impactPreview.proposed_change.is_visible_subscribers !== undefined && (
                    <div className="flex justify-between items-center">
                      <span>Subscriber Visibility</span>
                      <div className="flex items-center gap-3">
                        <Badge variant={impactPreview.current_state.is_visible_subscribers ? "default" : "destructive"}>
                          {impactPreview.current_state.is_visible_subscribers ? "Visible" : "Hidden"}
                        </Badge>
                        <span>→</span>
                        <Badge variant={impactPreview.proposed_change.is_visible_subscribers ? "default" : "destructive"}>
                          {impactPreview.proposed_change.is_visible_subscribers ? "Visible" : "Hidden"}
                        </Badge>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Impact Details */}
              <Alert
                variant={
                  impactPreview.impact.will_block_new_investments ||
                  impactPreview.impact.will_remove_from_subscriber_discovery
                    ? "destructive"
                    : "default"
                }
              >
                <AlertTriangle className="h-4 w-4" />
                <AlertTitle>Impact</AlertTitle>
                <AlertDescription>
                  <ul className="list-disc list-inside space-y-1 mt-2">
                    {impactPreview.impact.will_remove_from_public_discovery && (
                      <li>Company will be removed from public discovery</li>
                    )}
                    {impactPreview.impact.will_remove_from_subscriber_discovery && (
                      <li>Company will be removed from subscriber deal listings</li>
                    )}
                    {impactPreview.impact.will_block_new_investments && (
                      <li>
                        <strong>New investments will be blocked</strong>
                      </li>
                    )}
                    <li>{impactPreview.impact.affected_existing_investors_note}</li>
                  </ul>
                </AlertDescription>
              </Alert>

              {/* Reason Input */}
              <div>
                <Label htmlFor="reason">
                  Reason for Change <span className="text-red-600">*</span>
                </Label>
                <Textarea
                  id="reason"
                  placeholder="Enter reason for this visibility change (required for audit trail)"
                  value={visibilityReason}
                  onChange={(e) => setVisibilityReason(e.target.value)}
                  rows={3}
                  className="mt-2"
                />
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowImpactPreview(false)} disabled={savingVisibility}>
              Cancel
            </Button>
            <Button onClick={handleSaveVisibility} disabled={!visibilityReason.trim() || savingVisibility}>
              {savingVisibility ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Saving...
                </>
              ) : (
                <>
                  Confirm & Save
                  <CheckCircle2 className="w-4 h-4 ml-2" />
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
