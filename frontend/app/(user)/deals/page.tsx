/**
 * PHASE 5 - Subscriber/Investor Frontend: Deals Listing with Wallet Allocation
 *
 * PURPOSE:
 * - Browse invest-able companies
 * - Surface platform context, risk flags, buy eligibility
 * - Wallet-based allocation system
 * - Pre-buy validation with guards
 * - Risk acknowledgements enforcement
 * - Investment review and confirmation with snapshot binding
 *
 * DEFENSIVE PRINCIPLES:
 * - No hidden blocking - all blockers shown explicitly
 * - Buy eligibility checked via BuyEnablementGuardService
 * - Material changes prominently surfaced
 * - Risk acknowledgements required and explicit
 * - Wallet over-allocation prevented
 * - Complete investment review before confirmation
 */

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import {
  Building2,
  Wallet,
  AlertTriangle,
  ShieldAlert,
  CheckCircle2,
  XCircle,
  Info,
  ArrowRight,
  Loader2,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import {
  fetchInvestorCompanies,
  InvestorCompany,
  WalletBalance,
} from "@/lib/investorCompanyApi";

export default function DealsPage() {
  const router = useRouter();

  const [companies, setCompanies] = useState<InvestorCompany[]>([]);
  const [wallet, setWallet] = useState<WalletBalance | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Load companies and wallet
  useEffect(() => {
    async function loadDeals() {
      setLoading(true);
      setError(null);

      try {
        const result = await fetchInvestorCompanies();

        setCompanies(result.companies);
        setWallet(result.wallet);
      } catch (err: any) {
        console.error("[INVESTOR DEALS] Failed to load companies:", err);
        setError("Unable to load deals at this time. Please try again later.");
        toast.error("Failed to load deals");
      } finally {
        setLoading(false);
      }
    }

    loadDeals();
  }, []);

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: wallet?.currency || "INR",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // Get buy eligibility badge
  const getBuyEligibilityBadge = (company: InvestorCompany) => {
    if (company.buy_eligibility.allowed) {
      return (
        <Badge className="bg-green-100 text-green-700 border-green-300">
          <CheckCircle2 className="w-3 h-3 mr-1" />
          Eligible to Buy
        </Badge>
      );
    }

    const criticalBlockers = company.buy_eligibility.blockers.filter(
      (b) => b.severity === "critical"
    );

    return (
      <Badge className="bg-red-100 text-red-700 border-red-300">
        <XCircle className="w-3 h-3 mr-1" />
        Blocked ({criticalBlockers.length} issues)
      </Badge>
    );
  };

  // Loading state
  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600" />
          <p className="text-gray-600">Loading investment opportunities...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="container py-20">
        <Alert variant="destructive">
          <AlertTriangle className="h-5 w-5" />
          <AlertTitle>Error Loading Deals</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
        <div className="mt-4">
          <Button onClick={() => window.location.reload()}>Retry</Button>
        </div>
      </div>
    );
  }

  return (
    <div className="container py-8">
      {/* Header with Wallet */}
      <div className="mb-8">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <div>
            <h1 className="text-4xl font-bold mb-2">Investment Opportunities</h1>
            <p className="text-gray-600 dark:text-gray-400">
              Browse companies, allocate your wallet, and invest
            </p>
          </div>

          {/* Wallet Card */}
          {wallet && (
            <Card className="lg:w-96 border-purple-200 dark:border-purple-800 bg-gradient-to-br from-purple-50 to-white dark:from-purple-950/30 dark:to-slate-900">
              <CardHeader className="pb-3">
                <CardTitle className="flex items-center text-lg">
                  <Wallet className="w-5 h-5 mr-2 text-purple-600" />
                  Your Wallet
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">
                    Available Balance
                  </span>
                  <span className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {formatCurrency(wallet.available_balance)}
                  </span>
                </div>

                {wallet.allocated_balance > 0 && (
                  <div className="flex justify-between items-center text-sm">
                    <span className="text-gray-600 dark:text-gray-400">Allocated</span>
                    <span className="font-semibold text-gray-700 dark:text-gray-300">
                      {formatCurrency(wallet.allocated_balance)}
                    </span>
                  </div>
                )}

                {wallet.pending_balance > 0 && (
                  <div className="flex justify-between items-center text-sm">
                    <span className="text-gray-600 dark:text-gray-400">Pending</span>
                    <span className="font-semibold text-amber-600 dark:text-amber-400">
                      {formatCurrency(wallet.pending_balance)}
                    </span>
                  </div>
                )}

                {/* Wallet usage progress */}
                {wallet.total_balance > 0 && (
                  <div className="pt-3 border-t border-purple-200 dark:border-purple-800">
                    <div className="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-2">
                      <span>Wallet Usage</span>
                      <span>
                        {Math.round(
                          ((wallet.total_balance - wallet.available_balance) /
                            wallet.total_balance) *
                            100
                        )}
                        %
                      </span>
                    </div>
                    <Progress
                      value={
                        ((wallet.total_balance - wallet.available_balance) /
                          wallet.total_balance) *
                        100
                      }
                      className="h-2"
                    />
                  </div>
                )}

                <Link href="/wallet">
                  <Button variant="outline" size="sm" className="w-full mt-2">
                    Manage Wallet
                    <ArrowRight className="w-4 h-4 ml-2" />
                  </Button>
                </Link>
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Platform Notice */}
      <Alert className="mb-8 border-blue-300 bg-blue-50 dark:bg-blue-950/30">
        <Info className="h-5 w-5 text-blue-600" />
        <AlertTitle className="text-blue-900 dark:text-blue-200">
          How Investment Works
        </AlertTitle>
        <AlertDescription className="text-sm text-blue-800 dark:text-blue-300">
          <ul className="list-disc list-inside space-y-1 mt-2">
            <li>Browse companies and check eligibility status</li>
            <li>Allocate funds from your wallet to one or more companies</li>
            <li>Review and acknowledge all required risks</li>
            <li>Confirm investment - your view will be captured in an immutable snapshot</li>
          </ul>
        </AlertDescription>
      </Alert>

      {/* Companies Grid */}
      {companies.length > 0 ? (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {companies.map((company) => (
            <Card
              key={company.id}
              className={`hover:shadow-xl transition-all duration-300 ${
                !company.buy_eligibility.allowed
                  ? "border-red-200 dark:border-red-900"
                  : company.has_material_changes
                  ? "border-amber-200 dark:border-amber-900"
                  : ""
              }`}
            >
              <CardHeader>
                {/* Company Logo & Name */}
                <div className="flex items-start gap-4 mb-3">
                  {company.logo_url && (
                    <div className="relative w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-slate-800 flex-shrink-0">
                      <Image
                        src={company.logo_url}
                        alt={`${company.name} logo`}
                        fill
                        className="object-contain p-2"
                      />
                    </div>
                  )}

                  <div className="flex-1 min-w-0">
                    <CardTitle className="text-xl mb-2 truncate">{company.name}</CardTitle>
                    {company.sector && (
                      <Badge variant="outline" className="text-xs">
                        {typeof company.sector === 'string' ? company.sector : company.sector.name}
                      </Badge>
                    )}
                  </div>
                </div>

                {/* Buy Eligibility */}
                <div className="mb-3">{getBuyEligibilityBadge(company)}</div>

                {/* Warnings */}
                {company.has_material_changes && (
                  <Alert variant="destructive" className="mb-3">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertTitle className="text-sm">Material Changes Detected</AlertTitle>
                    <AlertDescription className="text-xs">
                      Platform context has changed. Review required.
                    </AlertDescription>
                  </Alert>
                )}

                {company.is_suspended && (
                  <Alert variant="destructive" className="mb-3">
                    <ShieldAlert className="h-4 w-4" />
                    <AlertTitle className="text-sm">Company Suspended</AlertTitle>
                    <AlertDescription className="text-xs">
                      Platform has suspended this company. New investments blocked.
                    </AlertDescription>
                  </Alert>
                )}
              </CardHeader>

              <CardContent>
                {/* Short Description */}
                {company.short_description && (
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                    {company.short_description}
                  </p>
                )}

                {/* Platform Context Summary */}
                <div className="space-y-2 mb-4 text-xs">
                  <div className="flex justify-between items-center">
                    <span className="text-gray-500">Lifecycle:</span>
                    <Badge variant="outline" className="text-xs capitalize">
                      {company.lifecycle_state?.replace("_", " ") || "Active"}
                    </Badge>
                  </div>

                  {company.risk_flags && company.risk_flags.length > 0 && (
                    <div className="flex justify-between items-center">
                      <span className="text-gray-500">Risk Flags:</span>
                      <Badge variant="destructive" className="text-xs">
                        {company.risk_flags.length} active
                      </Badge>
                    </div>
                  )}
                </div>

                {/* Blockers (if not eligible) */}
                {!company.buy_eligibility.allowed &&
                  company.buy_eligibility.blockers.length > 0 && (
                    <div className="mb-4 p-3 bg-red-50 dark:bg-red-950/30 rounded-lg border border-red-200 dark:border-red-900">
                      <p className="text-xs font-semibold text-red-700 dark:text-red-300 mb-2">
                        Why you cannot invest:
                      </p>
                      <ul className="text-xs text-red-600 dark:text-red-400 space-y-1">
                        {company.buy_eligibility.blockers
                          .filter((b) => b.severity === "critical")
                          .slice(0, 2)
                          .map((blocker, index) => (
                            <li key={index} className="flex items-start gap-1">
                              <XCircle className="w-3 h-3 mt-0.5 flex-shrink-0" />
                              <span>{blocker.message}</span>
                            </li>
                          ))}
                      </ul>
                      {company.buy_eligibility.blockers.length > 2 && (
                        <p className="text-xs text-red-500 dark:text-red-400 mt-1">
                          +{company.buy_eligibility.blockers.length - 2} more issues
                        </p>
                      )}
                    </div>
                  )}

                {/* Action Button */}
                <Link href={`/deals/${company.id}`}>
                  <Button
                    variant={company.buy_eligibility.allowed ? "default" : "outline"}
                    className="w-full"
                    disabled={!company.buy_eligibility.allowed}
                  >
                    {company.buy_eligibility.allowed ? "View & Invest" : "View Details"}
                    <ArrowRight className="w-4 h-4 ml-2" />
                  </Button>
                </Link>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-20">
          <Building2 className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
            No Investment Opportunities Available
          </h3>
          <p className="text-gray-600 dark:text-gray-400 mb-6">
            There are currently no companies available for investment. Check back soon!
          </p>
        </div>
      )}
    </div>
  );
}
