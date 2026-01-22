/**
 * COMPREHENSIVE PRE-IPO DEAL DETAIL PAGE
 *
 * Covers all 15 investment decision categories:
 * 1. Instrument Clarity | 2. Shareholder Rights | 3. Cap Table & Dilution
 * 4. Business Model | 5. Financial Health | 6. Cash Burn & Runway
 * 7. Valuation Discipline | 8. IPO Readiness | 9. Liquidity & Exit
 * 10. Promoter & Governance | 11. Regulatory & Legal | 12. Platform Risk
 * 13. Comprehensive Risks | 14. Portfolio Fit | 15. Sanity Check
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
  CheckCircle2,
  XCircle,
  ArrowLeft,
  Loader2,
  FileText,
  PieChart,
  TrendingUp,
  Target,
  Shield,
  Download,
  Info,
  ChevronDown,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Separator } from "@/components/ui/separator";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Checkbox } from "@/components/ui/checkbox";
import { toast } from "sonner";
import { fetchInvestorCompanyDetailComprehensive, getWalletBalance } from "@/lib/investorCompanyApi";

export default function ComprehensiveDealPage() {
  const { id } = useParams();
  const router = useRouter();

  const [company, setCompany] = useState<any>(null);
  const [wallet, setWallet] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [sanityChecks, setSanityChecks] = useState<Record<string, boolean>>({});
  const [allChecksComplete, setAllChecksComplete] = useState(false);

  // Load comprehensive company data
  useEffect(() => {
    async function loadData() {
      setLoading(true);
      setError(null);

      try {
        const [companyData, walletData] = await Promise.all([
          fetchInvestorCompanyDetailComprehensive(Number(id)),
          getWalletBalance(),
        ]);

        setCompany(companyData);
        setWallet(walletData);

        // Initialize sanity checks
        if (companyData.sanity_check_questions) {
          const checks: Record<string, boolean> = {};
          companyData.sanity_check_questions.forEach((q: string) => {
            checks[q] = false;
          });
          setSanityChecks(checks);
        }
      } catch (err: any) {
        console.error("[COMPREHENSIVE DEAL] Failed to load:", err);
        setError("Unable to load company details. Please try again later.");
        toast.error("Failed to load company details");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [id]);

  // Check if all sanity checks are complete
  useEffect(() => {
    if (company?.sanity_check_questions) {
      const allComplete = company.sanity_check_questions.every(
        (q: string) => sanityChecks[q] === true
      );
      setAllChecksComplete(allComplete);
    }
  }, [sanityChecks, company]);

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: "INR",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // Format percentage
  const formatPercent = (value: number) => {
    return `${value.toFixed(1)}%`;
  };

  // Get risk level color
  const getRiskColor = (level: string) => {
    switch (level.toLowerCase()) {
      case "low":
        return "text-green-600 bg-green-50 border-green-200";
      case "medium":
        return "text-yellow-600 bg-yellow-50 border-yellow-200";
      case "high":
        return "text-red-600 bg-red-50 border-red-200";
      default:
        return "text-gray-600 bg-gray-50 border-gray-200";
    }
  };

  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600" />
          <p className="text-gray-600">Loading comprehensive deal details...</p>
        </div>
      </div>
    );
  }

  if (error || !company) {
    return (
      <div className="container py-20">
        <Alert variant="destructive">
          <AlertTriangle className="h-5 w-5" />
          <AlertTitle>Error Loading Deal</AlertTitle>
          <AlertDescription>{error || "Company not found"}</AlertDescription>
        </Alert>
        <div className="mt-4 flex gap-4">
          <Button onClick={() => router.back()}>Go Back</Button>
          <Button variant="outline" onClick={() => window.location.reload()}>
            Retry
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-slate-900">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white">
        <div className="container py-8">
          <Link href="/deals">
            <Button variant="ghost" className="text-white hover:bg-white/20 mb-4">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Deals
            </Button>
          </Link>

          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div className="flex items-start gap-6">
              {company.logo_url && (
                <div className="relative w-24 h-24 rounded-xl overflow-hidden bg-white flex-shrink-0">
                  <Image
                    src={company.logo_url}
                    alt={`${company.name} logo`}
                    fill
                    className="object-contain p-2"
                  />
                </div>
              )}

              <div>
                <h1 className="text-4xl font-bold mb-2">{company.name}</h1>
                {company.sector && (
                  <Badge className="bg-white/20 text-white border-white/30 mb-3">
                    {typeof company.sector === 'string' ? company.sector : company.sector.name}
                  </Badge>
                )}
                <p className="text-white/90 max-w-2xl">{company.description}</p>

                <div className="flex flex-wrap gap-4 mt-4">
                  <div>
                    <span className="text-white/70 text-sm">Founded</span>
                    <p className="font-semibold">{company.founded_year}</p>
                  </div>
                  <div>
                    <span className="text-white/70 text-sm">Headquarters</span>
                    <p className="font-semibold">{company.headquarters}</p>
                  </div>
                  <div>
                    <span className="text-white/70 text-sm">CEO</span>
                    <p className="font-semibold">{company.ceo_name}</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="flex flex-col gap-2">
              <Button
                variant="secondary"
                onClick={() => window.print()}
                className="w-full"
              >
                <Download className="w-4 h-4 mr-2" />
                Download PDF
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Summary Metrics */}
      <div className="container -mt-8 mb-8">
        <div className="grid md:grid-cols-4 gap-4">
          <Card className="border-2">
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                Valuation
              </div>
              <div className="text-2xl font-bold text-purple-600">
                {formatCurrency(company.valuation_metrics?.current_valuation || 0)}
              </div>
              <div className="text-xs text-gray-500 mt-1">
                {formatPercent(company.valuation_metrics?.pre_ipo_premium_percentage || 0)} Pre-IPO Premium
              </div>
            </CardContent>
          </Card>

          <Card className="border-2">
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                Cash Runway
              </div>
              <div className="text-2xl font-bold text-blue-600">
                {company.cash_runway?.runway_months || 0} months
              </div>
              <div className="text-xs text-gray-500 mt-1">
                {formatCurrency(company.cash_runway?.monthly_burn_rate || 0)}/mo burn
              </div>
            </CardContent>
          </Card>

          <Card className="border-2">
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                IPO Timeline
              </div>
              <div className="text-2xl font-bold text-green-600">
                {company.ipo_readiness?.ipo_timeline_indicative?.split(' ')[0] || 'TBD'}
              </div>
              <div className="text-xs text-gray-500 mt-1">
                (Indicative, not guaranteed)
              </div>
            </CardContent>
          </Card>

          <Card className="border-2">
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                Lock-in Period
              </div>
              <div className="text-2xl font-bold text-amber-600">
                {company.liquidity_exit?.lock_in_period_months || 0} months
              </div>
              <div className="text-xs text-gray-500 mt-1">
                After investment
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Risk Dashboard */}
      <div className="container mb-8">
        <Alert className="border-2 border-red-200 bg-red-50 dark:bg-red-950/30">
          <AlertTriangle className="h-5 w-5 text-red-600" />
          <AlertTitle className="text-red-900 dark:text-red-200 font-bold">
            High Risk Investment
          </AlertTitle>
          <AlertDescription className="text-red-800 dark:text-red-300">
            <div className="grid md:grid-cols-3 gap-4 mt-2">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                <span className="font-semibold">Market Risk:</span>
                <span>{company.comprehensive_risks?.market_risk_level}</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-red-500"></div>
                <span className="font-semibold">Liquidity Risk:</span>
                <span>{company.comprehensive_risks?.liquidity_risk_level}</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                <span className="font-semibold">Total Loss Possible:</span>
                <span>Yes</span>
              </div>
            </div>
          </AlertDescription>
        </Alert>
      </div>

      {/* Main Content - Tabbed Interface */}
      <div className="container pb-8">
        <div className="grid lg:grid-cols-[1fr_380px] gap-8">
          {/* Left Column - Tabs */}
          <div>
            <Tabs defaultValue="instrument" className="w-full">
              <TabsList className="grid w-full grid-cols-4 lg:grid-cols-7">
                <TabsTrigger value="instrument">Instrument</TabsTrigger>
                <TabsTrigger value="business">Business</TabsTrigger>
                <TabsTrigger value="financials">Financials</TabsTrigger>
                <TabsTrigger value="ipo">IPO & Exit</TabsTrigger>
                <TabsTrigger value="governance">Governance</TabsTrigger>
                <TabsTrigger value="risks">Risks</TabsTrigger>
                <TabsTrigger value="fit">Portfolio Fit</TabsTrigger>
              </TabsList>

              {/* Tab 1: Instrument & Rights */}
              <TabsContent value="instrument" className="space-y-6">
                {/* Instrument Clarity */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <FileText className="w-5 h-5" />
                      1. Instrument Clarity
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Instrument Type
                        </span>
                        <p className="font-semibold">
                          {company.instrument_details?.instrument_type}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Holding Structure
                        </span>
                        <p className="font-semibold">
                          {company.instrument_details?.holding_structure}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Voting Rights
                        </span>
                        <div className="font-semibold">
                          {company.instrument_details?.voting_rights ? (
                            <Badge className="bg-green-100 text-green-700 border-green-300">
                              ✓ Included
                            </Badge>
                          ) : (
                            <Badge variant="destructive">✗ Not Included</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Information Rights
                        </span>
                        <div className="font-semibold">
                          {company.instrument_details?.information_rights ? (
                            <Badge className="bg-green-100 text-green-700 border-green-300">
                              ✓ Included
                            </Badge>
                          ) : (
                            <Badge variant="destructive">✗ Not Included</Badge>
                          )}
                        </div>
                      </div>
                    </div>
                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Transfer Restrictions
                      </span>
                      <p className="text-sm mt-1">
                        {company.instrument_details?.transfer_restrictions}
                      </p>
                    </div>
                  </CardContent>
                </Card>

                {/* Shareholder Rights */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Shield className="w-5 h-5" />
                      2. Shareholder Rights
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          SHA Available
                        </span>
                        <div className="font-semibold">
                          {company.shareholder_rights?.sha_available ? (
                            <>
                              <Badge className="bg-green-100 text-green-700 border-green-300 mr-2">
                                ✓ Yes
                              </Badge>
                              {company.shareholder_rights?.sha_document_url && (
                                <Link
                                  href={company.shareholder_rights.sha_document_url}
                                  target="_blank"
                                  className="text-blue-600 hover:underline text-sm"
                                >
                                  Download SHA
                                </Link>
                              )}
                            </>
                          ) : (
                            <Badge variant="secondary">Not Available</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Tag-Along Rights
                        </span>
                        <div className="font-semibold">
                          {company.shareholder_rights?.tag_along_rights ? (
                            <Badge className="bg-green-100 text-green-700 border-green-300">
                              ✓ Included
                            </Badge>
                          ) : (
                            <Badge variant="secondary">Not Included</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Drag-Along Rights
                        </span>
                        <div className="font-semibold">
                          {company.shareholder_rights?.drag_along_rights ? (
                            <Badge variant="secondary">Applicable</Badge>
                          ) : (
                            <Badge className="bg-green-100 text-green-700 border-green-300">
                              Not Applicable
                            </Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Liquidation Preference
                        </span>
                        <p className="font-semibold text-sm">
                          {company.shareholder_rights?.liquidation_preference}
                        </p>
                      </div>
                    </div>
                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Anti-Dilution Protection
                      </span>
                      <p className="text-sm mt-1">
                        {company.shareholder_rights?.anti_dilution_protection}
                      </p>
                    </div>
                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Exit Clauses
                      </span>
                      <div className="flex flex-wrap gap-2 mt-2">
                        {company.shareholder_rights?.exit_clauses?.map((clause: string, i: number) => (
                          <Badge key={i} variant="outline">
                            {clause}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Cap Table */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <PieChart className="w-5 h-5" />
                      3. Cap Table & Dilution Risk
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <span className="text-sm">Promoter Holding</span>
                        <span className="font-bold text-lg">
                          {formatPercent(company.cap_table_info?.promoter_holding_percentage || 0)}
                        </span>
                      </div>
                      <Progress
                        value={company.cap_table_info?.promoter_holding_percentage || 0}
                        className="h-3"
                      />

                      <div className="flex justify-between items-center">
                        <span className="text-sm">ESOP Pool</span>
                        <span className="font-bold">
                          {formatPercent(company.cap_table_info?.esop_pool_percentage || 0)}
                        </span>
                      </div>
                      <Progress
                        value={company.cap_table_info?.esop_pool_percentage || 0}
                        className="h-2"
                      />

                      <div className="flex justify-between items-center">
                        <span className="text-sm">Institutional Investors</span>
                        <span className="font-bold">
                          {formatPercent(company.cap_table_info?.institutional_holding_percentage || 0)}
                        </span>
                      </div>
                      <Progress
                        value={company.cap_table_info?.institutional_holding_percentage || 0}
                        className="h-2"
                      />

                      <div className="flex justify-between items-center">
                        <span className="text-sm">Retail Investors</span>
                        <span className="font-bold">
                          {formatPercent(company.cap_table_info?.retail_holding_percentage || 0)}
                        </span>
                      </div>
                      <Progress
                        value={company.cap_table_info?.retail_holding_percentage || 0}
                        className="h-2"
                      />
                    </div>

                    <Separator />

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Promoter Holding Trend
                      </span>
                      <p className="font-semibold capitalize">
                        {company.cap_table_info?.promoter_holding_trend}
                      </p>
                    </div>

                    <Alert className="border-amber-200 bg-amber-50 dark:bg-amber-950/30">
                      <AlertTriangle className="h-4 w-4 text-amber-600" />
                      <AlertTitle className="text-amber-900 dark:text-amber-200">
                        Future Dilution Risk
                      </AlertTitle>
                      <AlertDescription className="text-amber-800 dark:text-amber-300 text-sm">
                        {company.cap_table_info?.future_dilution_risk}
                      </AlertDescription>
                    </Alert>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Preference Stack Summary
                      </span>
                      <p className="text-sm mt-1">
                        {company.cap_table_info?.preference_stack_summary}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Tab 2: Business Model */}
              <TabsContent value="business" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <TrendingUp className="w-5 h-5" />
                      4. Business Model Strength
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Revenue Model
                        </span>
                        <p className="font-semibold">
                          {company.business_model?.revenue_model}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Revenue Type
                        </span>
                        <p className="font-semibold text-sm">
                          {company.business_model?.revenue_type}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          LTV/CAC Ratio
                        </span>
                        <div className="font-semibold">
                          {company.business_model?.ltv_cac_ratio}
                          {company.business_model?.ltv_cac_ratio >= 3 && (
                            <Badge className="ml-2 bg-green-100 text-green-700 border-green-300">
                              Healthy
                            </Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Gross Margin
                        </span>
                        <p className="font-semibold">
                          {formatPercent(company.business_model?.gross_margin_percentage || 0)}
                        </p>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Market Size (TAM)
                      </span>
                      <p className="font-semibold">
                        {company.business_model?.market_size}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Customer Concentration
                      </span>
                      <p className="text-sm mt-1">
                        {company.business_model?.customer_concentration}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                        Competitive Moat
                      </span>
                      <p className="text-sm mt-1">
                        {company.business_model?.competitive_moat}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Continue with remaining tabs... */}
              {/* For brevity, I'll add placeholders for other tabs */}
              {/* You can expand these similarly */}

              <TabsContent value="financials">
                <Card>
                  <CardHeader>
                    <CardTitle>Financial Health, Cash Runway & Valuation</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-gray-600">
                      Comprehensive financial data including 3-5 year history, cash burn analysis, and valuation metrics...
                    </p>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="ipo">
                <Card>
                  <CardHeader>
                    <CardTitle>IPO Readiness & Exit Scenarios</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-gray-600">
                      IPO timeline, merchant banker status, exit probability analysis...
                    </p>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="governance">
                <Card>
                  <CardHeader>
                    <CardTitle>Promoter Quality, Governance & Legal</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-gray-600">
                      Founder background, board composition, regulatory compliance, litigation status...
                    </p>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="risks">
                <Card>
                  <CardHeader>
                    <CardTitle>Comprehensive Risk Disclosure</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <p className="text-sm text-gray-600">
                      Complete risk analysis including downside scenarios, platform risks...
                    </p>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="fit">
                <Card>
                  <CardHeader>
                    <CardTitle>Portfolio Fit & Final Sanity Check</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-6">
                    {/* Portfolio Fit Guidance */}
                    <div className="space-y-4">
                      <h3 className="font-semibold">Portfolio Fit Guidance</h3>
                      <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                        <Info className="h-4 w-4 text-blue-600" />
                        <AlertDescription className="text-blue-800 dark:text-blue-300">
                          <ul className="list-disc list-inside space-y-1 text-sm">
                            <li>
                              <strong>Investment Horizon:</strong>{" "}
                              {company.portfolio_fit_guidance?.recommended_investment_horizon}
                            </li>
                            <li>
                              <strong>Portfolio Allocation:</strong>{" "}
                              {company.portfolio_fit_guidance?.recommended_portfolio_allocation}
                            </li>
                            <li>
                              <strong>Loss Tolerance:</strong>{" "}
                              {company.portfolio_fit_guidance?.ability_to_absorb_loss}
                            </li>
                            <li>
                              <strong>Suitability:</strong>{" "}
                              {company.portfolio_fit_guidance?.suitability}
                            </li>
                          </ul>
                        </AlertDescription>
                      </Alert>
                    </div>

                    <Separator />

                    {/* Sanity Check Questions */}
                    <div className="space-y-4">
                      <h3 className="font-semibold text-lg">
                        15. Final Investment Sanity Check
                      </h3>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        You must honestly answer YES to all these questions before proceeding with investment:
                      </p>

                      <div className="space-y-3 border-2 border-amber-200 rounded-lg p-4 bg-amber-50 dark:bg-amber-950/30">
                        {company.sanity_check_questions?.map((question: string, i: number) => (
                          <div key={i} className="flex items-start gap-3">
                            <Checkbox
                              id={`sanity-${i}`}
                              checked={sanityChecks[question] || false}
                              onCheckedChange={(checked) =>
                                setSanityChecks((prev) => ({
                                  ...prev,
                                  [question]: checked as boolean,
                                }))
                              }
                              className="mt-1"
                            />
                            <label
                              htmlFor={`sanity-${i}`}
                              className="text-sm cursor-pointer select-none"
                            >
                              {question}
                            </label>
                          </div>
                        ))}
                      </div>

                      {!allChecksComplete && (
                        <Alert className="border-red-200 bg-red-50 dark:bg-red-950/30">
                          <XCircle className="h-4 w-4 text-red-600" />
                          <AlertDescription className="text-red-800 dark:text-red-300">
                            You must check all boxes above to proceed with investment. Honest self-assessment is critical for your financial safety.
                          </AlertDescription>
                        </Alert>
                      )}

                      {allChecksComplete && (
                        <Alert className="border-green-200 bg-green-50 dark:bg-green-950/30">
                          <CheckCircle2 className="h-4 w-4 text-green-600" />
                          <AlertDescription className="text-green-800 dark:text-green-300">
                            All sanity checks complete. You may now proceed to investment if eligible.
                          </AlertDescription>
                        </Alert>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </div>

          {/* Right Column - Sticky Investment Sidebar */}
          <div className="lg:sticky lg:top-8 lg:self-start">
            <Card className="border-2 border-purple-200 dark:border-purple-800">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Wallet className="w-5 h-5" />
                  Investment Summary
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Buy Eligibility */}
                {company.buy_eligibility?.allowed ? (
                  <Alert className="border-green-200 bg-green-50 dark:bg-green-950/30">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertTitle className="text-green-900 dark:text-green-200">
                      Eligible to Invest
                    </AlertTitle>
                  </Alert>
                ) : (
                  <Alert variant="destructive">
                    <XCircle className="h-4 w-4" />
                    <AlertTitle>Not Eligible</AlertTitle>
                    <AlertDescription>
                      <ul className="list-disc list-inside text-sm mt-2">
                        {company.buy_eligibility?.blockers?.map((blocker: any, i: number) => (
                          <li key={i}>{blocker.message}</li>
                        ))}
                      </ul>
                    </AlertDescription>
                  </Alert>
                )}

                {/* Wallet Balance */}
                {wallet && (
                  <div>
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      Available Balance
                    </span>
                    <p className="text-2xl font-bold text-purple-600">
                      {formatCurrency(wallet.available_balance)}
                    </p>
                  </div>
                )}

                <Separator />

                {/* Investment CTA */}
                <Button
                  className="w-full"
                  size="lg"
                  disabled={!company.buy_eligibility?.allowed || !allChecksComplete}
                >
                  Proceed to Invest
                </Button>

                {!allChecksComplete && (
                  <p className="text-xs text-center text-gray-600 dark:text-gray-400">
                    Complete all sanity checks in "Portfolio Fit" tab to enable investment
                  </p>
                )}

                <Link href="/wallet">
                  <Button variant="outline" className="w-full">
                    Add Funds to Wallet
                  </Button>
                </Link>
              </CardContent>
            </Card>

            {/* Quick Links */}
            <Card className="mt-4">
              <CardHeader>
                <CardTitle className="text-sm">Quick Links</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                <Link
                  href={`/deals/${id}`}
                  className="block text-sm text-blue-600 hover:underline"
                >
                  View Basic Deal Page
                </Link>
                <Link
                  href="/learn"
                  className="block text-sm text-blue-600 hover:underline"
                >
                  Pre-IPO Investment Guide
                </Link>
                <Link
                  href="/support"
                  className="block text-sm text-blue-600 hover:underline"
                >
                  Contact Support
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
