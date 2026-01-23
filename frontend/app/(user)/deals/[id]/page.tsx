/**
 * PHASE 5 - Subscriber/Investor Frontend: Company Detail with Investment Flow
 *
 * PURPOSE:
 * - Show complete investor view of company
 * - Wallet allocation input
 * - Risk acknowledgements enforcement
 * - Pre-buy validation with BuyEnablementGuardService
 * - Investment review and confirmation
 * - Snapshot binding via InvestmentSnapshotService
 *
 * DEFENSIVE PRINCIPLES:
 * - Show ALL platform warnings and restrictions
 * - Require explicit risk acknowledgements
 * - Prevent over-allocation
 * - Complete review before submission
 * - Surface all blockers explicitly
 */

"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Image from "next/image";
import Link from "next/link";
import {
  Building2,
  Wallet,
  AlertTriangle,
  ShieldAlert,
  CheckCircle2,
  XCircle,
  ArrowLeft,
  ArrowRight,
  Loader2,
  Info,
  FileText,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
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
  fetchInvestorCompanyDetail,
  checkBuyEligibility,
  getRequiredAcknowledgements,
  recordAcknowledgement,
  submitInvestment,
  getWalletBalance,
  InvestorCompanyDetail,
  WalletBalance,
} from "@/lib/investorCompanyApi";

export default function InvestorCompanyDetailPage() {
  const { id } = useParams();
  const router = useRouter();

  const [company, setCompany] = useState<InvestorCompanyDetail | null>(null);
  const [wallet, setWallet] = useState<WalletBalance | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Allocation state
  const [allocationAmount, setAllocationAmount] = useState<string>("");
  const [acknowledgements, setAcknowledgements] = useState<Record<string, boolean>>({});
  const [requiredAcknowledgements, setRequiredAcknowledgements] = useState<
    Array<{ type: string; text: string; required: boolean }>
  >([]);

  // Review modal state
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // ISSUE 1 FIX: Restore form state from localStorage on mount
  useEffect(() => {
    if (company) {
      const storageKey = `investment-form-${company.id}`;
      const savedState = localStorage.getItem(storageKey);

      if (savedState) {
        try {
          const parsed = JSON.parse(savedState);
          const age = Date.now() - (parsed.timestamp || 0);

          // Only restore if less than 1 hour old (3600000 ms)
          if (age < 3600000) {
            if (parsed.allocationAmount) {
              setAllocationAmount(parsed.allocationAmount);
            }
            if (parsed.acknowledgements) {
              setAcknowledgements(parsed.acknowledgements);
            }
            console.log("[FORM RESTORE] Restored form state from localStorage");
          } else {
            // Clear stale data
            localStorage.removeItem(storageKey);
          }
        } catch (err) {
          console.error("[FORM RESTORE] Failed to parse saved state:", err);
          localStorage.removeItem(storageKey);
        }
      }
    }
  }, [company]);

  // ISSUE 1 FIX: Save form state to localStorage whenever it changes
  useEffect(() => {
    if (company && (allocationAmount || Object.values(acknowledgements).some(v => v))) {
      const storageKey = `investment-form-${company.id}`;
      const formState = {
        allocationAmount,
        acknowledgements,
        timestamp: Date.now(),
      };
      localStorage.setItem(storageKey, JSON.stringify(formState));
    }
  }, [allocationAmount, acknowledgements, company]);

  // Load company detail and wallet
  useEffect(() => {
    async function loadCompanyDetail() {
      if (!id || typeof id !== "string") return;

      setLoading(true);
      setError(null);

      try {
        const companyId = parseInt(id);

        const [companyData, walletData, acknowledgementsData] = await Promise.all([
          fetchInvestorCompanyDetail(companyId),
          getWalletBalance(),
          getRequiredAcknowledgements(companyId),
        ]);

        setCompany(companyData);
        setWallet(walletData);
        setRequiredAcknowledgements(acknowledgementsData);

        // DEBUG: Log acknowledgements received from API
        console.log('[DEAL PAGE] Acknowledgements from API:', acknowledgementsData);
        console.log('[DEAL PAGE] Acknowledgement types:', acknowledgementsData.map((a: any) => a.type));

        // Initialize acknowledgements state (will be overridden by localStorage if exists)
        const initialAcks: Record<string, boolean> = {};
        acknowledgementsData.forEach((ack) => {
          initialAcks[ack.type] = false;
        });
        setAcknowledgements(initialAcks);
      } catch (err: any) {
        console.error("[INVESTOR COMPANY DETAIL] Failed to load:", err);
        setError("Unable to load company details. Please try again later.");
        toast.error("Failed to load company");
      } finally {
        setLoading(false);
      }
    }

    loadCompanyDetail();
  }, [id]);

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: wallet?.currency || "INR",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // Handle allocation amount change
  const handleAllocationChange = (value: string) => {
    // Allow only numbers
    const numericValue = value.replace(/[^0-9]/g, "");
    setAllocationAmount(numericValue);
  };

  // Get allocation amount as number
  const getAllocationAmountNumber = (): number => {
    return parseInt(allocationAmount) || 0;
  };

  // Check if allocation is valid
  const isAllocationValid = (): boolean => {
    const amount = getAllocationAmountNumber();
    if (!wallet) return false;
    return amount > 0 && amount <= wallet.available_balance;
  };

  // Check if all required acknowledgements are checked
  const areAllAcknowledgementsChecked = (): boolean => {
    return requiredAcknowledgements.every(
      (ack) => !ack.required || acknowledgements[ack.type]
    );
  };

  // Handle invest button click
  const handleInvestClick = async () => {
    if (!company || !isAllocationValid() || !areAllAcknowledgementsChecked()) {
      toast.error("Please complete all required fields");
      return;
    }

    // Show review modal
    setShowReviewModal(true);
  };

  // Handle final confirmation
  const handleConfirmInvestment = async () => {
    if (!company) return;

    setSubmitting(true);

    try {
      // Record acknowledgements
      const acknowledgedTypes = Object.keys(acknowledgements).filter(
        (type) => acknowledgements[type]
      );

      for (const ackType of acknowledgedTypes) {
        await recordAcknowledgement(company.id, ackType);
      }

      // GAP 3 FIX: Generate idempotency key to prevent duplicate submissions
      const idempotencyKey = `invest-${company.id}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

      // DEBUG: Log what we're sending
      console.log('[INVESTMENT] Submitting investment with:', {
        company_id: company.id,
        amount: getAllocationAmountNumber(),
        acknowledged_risks: acknowledgedTypes,
        acknowledged_risks_count: acknowledgedTypes.length,
      });

      // Submit investment with idempotency key
      const result = await submitInvestment([
        {
          company_id: company.id,
          amount: getAllocationAmountNumber(),
          acknowledged_risks: acknowledgedTypes,
        },
      ], idempotencyKey);

      if (result.success) {
        toast.success("Investment successful!", {
          description: `Investment snapshot ID: ${result.snapshot_ids[0]}`,
        });

        // ISSUE 1 FIX: Clear localStorage after successful submission
        const storageKey = `investment-form-${company.id}`;
        localStorage.removeItem(storageKey);

        // ISSUE 3 FIX: Refresh wallet balance before navigation
        // This ensures wallet shows updated balance when user returns
        try {
          await getWalletBalance();
        } catch (err) {
          console.warn("[INVEST] Failed to refresh wallet balance:", err);
        }

        // Navigate to portfolio
        router.push("/portfolio?investment=success");
      } else {
        toast.error("Investment failed. Please try again.");
      }
    } catch (err: any) {
      // GAP 4 FIX: Structured error handling with specific error codes
      console.error("[INVEST] Failed to submit investment:", err);

      const errorCode = err?.response?.data?.error_code;
      const errorMessage = err?.response?.data?.message;

      switch (errorCode) {
        case 'INSUFFICIENT_BALANCE':
          toast.error("Insufficient Wallet Balance", {
            description: errorMessage || "Your wallet doesn't have enough balance for this investment. Please add funds first.",
          });
          break;

        case 'COMPANY_SUSPENDED':
        case 'PLATFORM_RESTRICTION':
          toast.error("Investment Not Allowed", {
            description: errorMessage || "This company is currently not accepting investments due to platform restrictions.",
          });
          break;

        case 'BUY_ELIGIBILITY_FAILED':
          toast.error("Investment Eligibility Failed", {
            description: errorMessage || "You are not eligible to invest in this company. Please check your account status.",
          });
          break;

        case 'ACKNOWLEDGEMENT_MISSING':
          toast.error("Missing Risk Acknowledgements", {
            description: "All required risk acknowledgements must be checked before investing.",
          });
          break;

        case 'INVALID_AMOUNT':
        case 'AMOUNT_EXCEEDS_BALANCE':
          toast.error("Invalid Investment Amount", {
            description: errorMessage || "The investment amount is invalid or exceeds your wallet balance.",
          });
          break;

        case 'SNAPSHOT_FAILED':
          toast.error("Snapshot Creation Failed", {
            description: "Failed to capture investment snapshot. Please try again or contact support.",
          });
          break;

        case 'WALLET_DEBIT_FAILED':
          toast.error("Wallet Transaction Failed", {
            description: "Failed to process wallet transaction. Please try again or contact support.",
          });
          break;

        case 'INTERNAL_ERROR':
        default:
          toast.error("Investment Failed", {
            description: errorMessage || "An unexpected error occurred. Please try again later.",
          });
          break;
      }
    } finally {
      setSubmitting(false);
      setShowReviewModal(false);
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600" />
          <p className="text-gray-600">Loading company details...</p>
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
          <Link href="/deals">
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Deals
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container py-8">
      {/* Back Button */}
      <Link
        href="/deals"
        className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 mb-6 transition-colors"
      >
        <ArrowLeft className="w-4 h-4 mr-2" />
        Back to All Deals
      </Link>

      {/* Comprehensive View Callout */}
      <Alert className="mb-6 border-2 border-purple-200 bg-purple-50 dark:bg-purple-950/30">
        <Info className="h-5 w-5 text-purple-600" />
        <AlertTitle className="text-purple-900 dark:text-purple-200 font-bold">
          Want Complete Investment Analysis?
        </AlertTitle>
        <AlertDescription className="text-purple-800 dark:text-purple-300">
          <p className="mb-3">
            View our comprehensive deal page with ALL 15 investment decision categories including instrument details, shareholder rights, cap table analysis, financial health, IPO readiness, risk disclosures, and more.
          </p>
          <Link href={`/deals/${id}/comprehensive`}>
            <Button className="bg-purple-600 hover:bg-purple-700">
              <FileText className="w-4 h-4 mr-2" />
              View Comprehensive Analysis
            </Button>
          </Link>
        </AlertDescription>
      </Alert>

      {/* Company Header */}
      <div className="flex flex-col md:flex-row items-start gap-6 mb-8">
        {company.logo_url && (
          <div className="relative w-24 h-24 rounded-xl overflow-hidden bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex-shrink-0">
            <Image
              src={company.logo_url}
              alt={`${company.name} logo`}
              fill
              className="object-contain p-3"
            />
          </div>
        )}

        <div className="flex-1">
          <h1 className="text-4xl font-bold mb-3">{company.name}</h1>
          {company.sector && (
            <Badge className="mb-3" variant="outline">
              {typeof company.sector === 'string' ? company.sector : company.sector.name}
            </Badge>
          )}

          {/* Buy Eligibility Badge */}
          {company.buy_eligibility.allowed ? (
            <Badge className="bg-green-100 text-green-700 border-green-300">
              <CheckCircle2 className="w-4 h-4 mr-1" />
              Eligible to Invest
            </Badge>
          ) : (
            <Badge className="bg-red-100 text-red-700 border-red-300">
              <XCircle className="w-4 h-4 mr-1" />
              Investment Blocked
            </Badge>
          )}
        </div>
      </div>

      {/* Critical Warnings */}
      {company.has_material_changes && (
        <Alert variant="destructive" className="mb-6">
          <AlertTriangle className="h-5 w-5" />
          <AlertTitle>Material Changes Detected</AlertTitle>
          <AlertDescription>
            <p className="mb-3">
              Platform context or disclosures have changed materially since your last review.
              You must review these changes before investing.
            </p>
            {company.material_change_warnings && (
              <ul className="list-disc list-inside mb-4">
                {company.material_change_warnings.map((warning, i) => (
                  <li key={i}>{warning}</li>
                ))}
              </ul>
            )}
            {/* GAP 6 FIX: Action button for material changes */}
            <div className="flex gap-3 mt-4">
              <Button
                variant="outline"
                size="sm"
                onClick={() => {
                  // Scroll to disclosure section or navigate to changes page
                  const disclosureSection = document.getElementById('company-disclosures');
                  if (disclosureSection) {
                    disclosureSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                  }
                }}
                className="bg-white dark:bg-slate-900"
              >
                <Info className="w-4 h-4 mr-2" />
                View Disclosure Changes
              </Button>
              {company.material_change_diff_url && (
                <Link href={company.material_change_diff_url}>
                  <Button
                    variant="outline"
                    size="sm"
                    className="bg-white dark:bg-slate-900"
                  >
                    <ArrowRight className="w-4 h-4 mr-2" />
                    See What Changed
                  </Button>
                </Link>
              )}
            </div>
          </AlertDescription>
        </Alert>
      )}

      {company.is_suspended && (
        <Alert variant="destructive" className="mb-6">
          <ShieldAlert className="h-5 w-5" />
          <AlertTitle>Company Suspended by Platform</AlertTitle>
          <AlertDescription>
            This company has been suspended. New investments are blocked until platform review is
            complete.
          </AlertDescription>
        </Alert>
      )}

      {/* Show Blockers if not eligible */}
      {!company.buy_eligibility.allowed && company.buy_eligibility.blockers.length > 0 && (
        <Alert variant="destructive" className="mb-6">
          <XCircle className="h-5 w-5" />
          <AlertTitle>Investment Blocked</AlertTitle>
          <AlertDescription>
            <p className="mb-2">You cannot invest in this company for the following reasons:</p>
            <ul className="list-disc list-inside space-y-1">
              {company.buy_eligibility.blockers
                .filter((b) => b.severity === "critical")
                .map((blocker, i) => (
                  <li key={i}>{blocker.message}</li>
                ))}
            </ul>
          </AlertDescription>
        </Alert>
      )}

      <div className="grid lg:grid-cols-3 gap-8">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Description */}
          {company.description && (
            <Card>
              <CardHeader>
                <CardTitle>About {company.name}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                  {company.description}
                </p>
              </CardContent>
            </Card>
          )}

          {/* Platform Context */}
          <Card>
            <CardHeader>
              <CardTitle>Platform Context</CardTitle>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Platform-governed company state
              </p>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-gray-500 text-xs">Lifecycle State</span>
                  <div className="flex items-center gap-2 mt-1">
                    <Badge variant="outline" className="font-semibold capitalize">
                      {company.platform_context.lifecycle_state.replace(/_/g, " ")}
                    </Badge>
                  </div>
                </div>
                <div>
                  <span className="text-gray-500 text-xs">Investment Buying</span>
                  <div className="flex items-center gap-2 mt-1">
                    <Badge
                      className={`font-semibold ${
                        company.platform_context.buying_enabled
                          ? "bg-green-600 text-white"
                          : "bg-red-600 text-white"
                      }`}
                    >
                      {company.platform_context.buying_enabled ? "✓ Enabled" : "✗ Disabled"}
                    </Badge>
                  </div>
                </div>
              </div>

              <Separator />

              <div>
                <h4 className="font-semibold mb-2">Tier Approvals</h4>
                <div className="space-y-2 text-sm">
                  {Object.entries(company.platform_context.tier_status).map(([tier, approved]) => {
                    // Clean up tier name - remove "_approved" suffix if present
                    const tierName = tier.replace(/_approved$/, '').replace(/_/g, ' ').toUpperCase();
                    return (
                      <div key={tier} className="flex justify-between items-center">
                        <span>{tierName}</span>
                        <Badge variant={approved ? "default" : "secondary"} className={approved ? "bg-green-600" : ""}>
                          {approved ? "✓ Approved" : "Pending"}
                        </Badge>
                      </div>
                    );
                  })}
                </div>
              </div>

              {company.platform_context.restrictions.is_suspended ||
              company.platform_context.restrictions.is_frozen ||
              company.platform_context.restrictions.is_under_investigation ? (
                <>
                  <Separator />
                  <div>
                    <h4 className="font-semibold mb-2 text-red-600">Active Restrictions</h4>
                    <ul className="space-y-1 text-sm text-red-600">
                      {company.platform_context.restrictions.is_suspended && (
                        <li>• Company Suspended</li>
                      )}
                      {company.platform_context.restrictions.is_frozen && (
                        <li>• Disclosures Frozen</li>
                      )}
                      {company.platform_context.restrictions.is_under_investigation && (
                        <li>• Under Investigation</li>
                      )}
                    </ul>
                    {company.platform_context.restrictions.buying_pause_reason && (
                      <p className="text-sm text-red-600 mt-2">
                        Reason: {company.platform_context.restrictions.buying_pause_reason}
                      </p>
                    )}
                  </div>
                </>
              ) : null}

              {company.platform_context.risk_assessment.risk_flags.length > 0 && (
                <>
                  <Separator />
                  <div>
                    <h4 className="font-semibold mb-2 text-amber-600">Risk Flags</h4>
                    <div className="space-y-2">
                      {company.platform_context.risk_assessment.risk_flags.map((flag: any, i: number) => (
                        <Alert key={i} variant="destructive" className="py-2">
                          <AlertDescription className="text-sm">
                            <span className="font-semibold">{flag.flag_type}:</span> {flag.description}
                          </AlertDescription>
                        </Alert>
                      ))}
                    </div>
                  </div>
                </>
              )}
            </CardContent>
          </Card>

          {/* Approved Disclosures */}
          {company.disclosures && company.disclosures.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle>Company Disclosures</CardTitle>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Platform-approved information
                </p>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {company.disclosures.map((disclosure, i) => (
                    <div key={i} className="border-b border-gray-200 dark:border-slate-800 last:border-0 pb-4 last:pb-0">
                      <div className="flex items-center justify-between mb-2">
                        <h4 className="font-semibold">{disclosure.module_name}</h4>
                        <Badge variant="outline" className="text-green-600 border-green-600">
                          Approved v{disclosure.version_number}
                        </Badge>
                      </div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Approved on: {new Date(disclosure.approved_at).toLocaleDateString()}
                      </p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}

          {/* Investment Form */}
          <Card id="investment">
            <CardHeader>
              <CardTitle>Invest in {company.name}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Allocation Input */}
              <div>
                <Label htmlFor="amount">Investment Amount</Label>
                <Input
                  id="amount"
                  type="text"
                  placeholder="Enter amount"
                  value={allocationAmount}
                  onChange={(e) => handleAllocationChange(e.target.value)}
                  disabled={!company.buy_eligibility.allowed}
                  className="text-lg"
                />
                {allocationAmount && wallet && (
                  <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Remaining: {formatCurrency(wallet.available_balance - getAllocationAmountNumber())}
                  </p>
                )}
                {getAllocationAmountNumber() > (wallet?.available_balance || 0) && (
                  <p className="text-sm text-red-600 mt-1">Insufficient wallet balance</p>
                )}
              </div>

              {/* Risk Acknowledgements */}
              {requiredAcknowledgements.length > 0 && (
                <div className="space-y-3">
                  <Label>Required Acknowledgements</Label>
                  {requiredAcknowledgements.map((ack) => (
                    <div key={ack.type} className="flex items-start space-x-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                      <Checkbox
                        id={ack.type}
                        checked={acknowledgements[ack.type] || false}
                        onCheckedChange={(checked) =>
                          setAcknowledgements({ ...acknowledgements, [ack.type]: !!checked })
                        }
                        disabled={!company.buy_eligibility.allowed}
                      />
                      <Label htmlFor={ack.type} className="text-sm leading-relaxed cursor-pointer">
                        {ack.text}
                      </Label>
                    </div>
                  ))}
                </div>
              )}

              {/* Invest Button */}
              <Button
                className="w-full"
                size="lg"
                onClick={handleInvestClick}
                disabled={
                  !company.buy_eligibility.allowed ||
                  !isAllocationValid() ||
                  !areAllAcknowledgementsChecked()
                }
              >
                Review & Confirm Investment
                <ArrowRight className="w-4 h-4 ml-2" />
              </Button>

              {!company.buy_eligibility.allowed && (
                <p className="text-xs text-red-600 text-center">
                  Investment blocked - see reasons above
                </p>
              )}
            </CardContent>
          </Card>

          {/* Platform Notice */}
          <Alert className="border-blue-300 bg-blue-50 dark:bg-blue-950/30">
            <Info className="h-4 w-4 text-blue-600" />
            <AlertTitle className="text-sm">Snapshot Guarantee</AlertTitle>
            <AlertDescription className="text-xs">
              Your investment will be bound to an immutable snapshot of all information you see
              here, including platform context and acknowledgements.
            </AlertDescription>
          </Alert>
        </div>

        {/* Sidebar - Wallet Only */}
        <div className="space-y-6">
          {/* Wallet Card */}
          {wallet && (
            <Card className="border-purple-200 dark:border-purple-800 bg-gradient-to-br from-purple-50 to-white dark:from-purple-950/30 dark:to-slate-900">
              <CardHeader>
                <CardTitle className="flex items-center">
                  <Wallet className="w-5 h-5 mr-2 text-purple-600" />
                  Your Wallet
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-center mb-4">
                  <p className="text-sm text-gray-600 dark:text-gray-400">Available Balance</p>
                  <p className="text-3xl font-bold text-purple-600 dark:text-purple-400">
                    {formatCurrency(wallet.available_balance)}
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Review Modal */}
      <Dialog open={showReviewModal} onOpenChange={setShowReviewModal}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Review Your Investment</DialogTitle>
            <DialogDescription>
              Please review all details before confirming. This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {/* Investment Summary */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Investment Summary</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Company</span>
                  <span className="font-semibold">{company.name}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Investment Amount</span>
                  <span className="font-semibold text-purple-600 text-xl">
                    {formatCurrency(getAllocationAmountNumber())}
                  </span>
                </div>
                <Separator />
                <div className="flex justify-between items-center">
                  <span className="text-gray-600">Remaining Balance</span>
                  <span className="font-semibold">
                    {wallet && formatCurrency(wallet.available_balance - getAllocationAmountNumber())}
                  </span>
                </div>
              </CardContent>
            </Card>

            {/* Acknowledged Risks */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Acknowledged Risks</CardTitle>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm">
                  {Object.keys(acknowledgements)
                    .filter((type) => acknowledgements[type])
                    .map((type) => (
                      <li key={type} className="flex items-start gap-2">
                        <CheckCircle2 className="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" />
                        <span className="capitalize">{type.replace("_", " ")}</span>
                      </li>
                    ))}
                </ul>
              </CardContent>
            </Card>

            {/* Final Confirmation */}
            <Alert>
              <AlertTriangle className="h-4 w-4" />
              <AlertTitle>Final Confirmation</AlertTitle>
              <AlertDescription className="text-sm">
                By confirming, you acknowledge that:
                <ul className="list-disc list-inside mt-2 space-y-1">
                  <li>You have reviewed all company information and platform warnings</li>
                  <li>You understand and accept all disclosed risks</li>
                  <li>An immutable snapshot will be created capturing your investment context</li>
                  <li>This investment decision is final and cannot be reversed</li>
                </ul>
              </AlertDescription>
            </Alert>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setShowReviewModal(false)} disabled={submitting}>
              Cancel
            </Button>
            <Button onClick={handleConfirmInvestment} disabled={submitting}>
              {submitting ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Confirming...
                </>
              ) : (
                <>
                  Confirm Investment
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
