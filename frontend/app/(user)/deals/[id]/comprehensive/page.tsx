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

              <TabsContent value="financials" className="space-y-6">
                {/* Financial Health */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <TrendingUp className="w-5 h-5" />
                      5. Financial Health
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-3 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Financials Available
                        </span>
                        <p className="font-semibold">
                          {company.financial_health?.financials_available ? 'Yes' : 'No'}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Years of Data
                        </span>
                        <p className="font-semibold">
                          {company.financial_health?.years_of_data || 0} years
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Auditor
                        </span>
                        <p className="font-semibold text-sm">
                          {company.financial_health?.auditor_credibility}
                        </p>
                      </div>
                    </div>

                    <Separator />

                    {/* Financial Reports Table */}
                    {company.financial_health?.financials && company.financial_health.financials.length > 0 && (
                      <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead className="bg-gray-100 dark:bg-gray-800">
                            <tr>
                              <th className="px-4 py-2 text-left">Year</th>
                              <th className="px-4 py-2 text-right">Revenue</th>
                              <th className="px-4 py-2 text-right">Growth YoY</th>
                              <th className="px-4 py-2 text-right">Operating Margin</th>
                              <th className="px-4 py-2 text-right">Net Profit Margin</th>
                            </tr>
                          </thead>
                          <tbody>
                            {company.financial_health.financials.map((report: any, i: number) => (
                              <tr key={i} className="border-b dark:border-gray-700">
                                <td className="px-4 py-2">{report.year}</td>
                                <td className="px-4 py-2 text-right font-semibold">
                                  {formatCurrency(report.revenue)}
                                </td>
                                <td className="px-4 py-2 text-right">
                                  <Badge className={report.revenue_growth_yoy >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}>
                                    {report.revenue_growth_yoy >= 0 ? '+' : ''}{formatPercent(report.revenue_growth_yoy || 0)}
                                  </Badge>
                                </td>
                                <td className="px-4 py-2 text-right">
                                  {formatPercent(report.operating_margin || 0)}
                                </td>
                                <td className="px-4 py-2 text-right">
                                  {formatPercent(report.net_profit_margin || 0)}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Financial Transparency Score
                      </span>
                      <div className="flex items-center gap-2">
                        <Progress
                          value={(company.financial_health?.financial_transparency_score || 0) * 20}
                          className="flex-1 h-3"
                        />
                        <span className="font-bold">
                          {company.financial_health?.financial_transparency_score}/5
                        </span>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Cash Runway */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Target className="w-5 h-5" />
                      6. Cash Burn & Runway
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Monthly Burn Rate
                        </span>
                        <p className="text-xl font-bold text-red-600">
                          {formatCurrency(company.cash_runway?.monthly_burn_rate || 0)}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Current Cash Balance
                        </span>
                        <p className="text-xl font-bold text-green-600">
                          {formatCurrency(company.cash_runway?.current_cash_balance || 0)}
                        </p>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 mb-2 block">
                        Cash Runway: <strong>{company.cash_runway?.runway_months || 0} months</strong>
                      </span>
                      <Progress
                        value={Math.min((company.cash_runway?.runway_months || 0) / 36 * 100, 100)}
                        className="h-4"
                      />
                      <p className="text-xs text-gray-500 mt-1">
                        36 months = Healthy runway for pre-IPO companies
                      </p>
                    </div>

                    <Separator />

                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Next Funding Round Planned
                        </span>
                        <div className="font-semibold">
                          {company.cash_runway?.next_funding_round_planned ? (
                            <Badge className="bg-blue-100 text-blue-700">
                              Yes - {company.cash_runway?.next_funding_timeline}
                            </Badge>
                          ) : (
                            <Badge variant="secondary">No immediate plans</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Break-Even Timeline
                        </span>
                        <p className="font-semibold">
                          {company.cash_runway?.break_even_timeline || 'Not disclosed'}
                        </p>
                      </div>
                    </div>

                    {company.cash_runway?.runway_months < 12 && (
                      <Alert className="border-red-200 bg-red-50 dark:bg-red-950/30">
                        <AlertTriangle className="h-4 w-4 text-red-600" />
                        <AlertTitle className="text-red-900 dark:text-red-200">
                          Short Runway Alert
                        </AlertTitle>
                        <AlertDescription className="text-red-800 dark:text-red-300 text-sm">
                          Company has less than 12 months runway. Near-term funding requirement may affect valuation.
                        </AlertDescription>
                      </Alert>
                    )}
                  </CardContent>
                </Card>

                {/* Valuation Metrics */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <PieChart className="w-5 h-5" />
                      7. Valuation Discipline
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Pre-Money Valuation
                        </span>
                        <p className="text-xl font-bold">
                          {formatCurrency(company.valuation_metrics?.pre_money_valuation || 0)}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Post-Money Valuation
                        </span>
                        <p className="text-xl font-bold text-purple-600">
                          {formatCurrency(company.valuation_metrics?.post_money_valuation || 0)}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Revenue Multiple
                        </span>
                        <p className="font-semibold">
                          {company.valuation_metrics?.revenue_multiple}x
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Last Round Valuation
                        </span>
                        <p className="font-semibold">
                          {formatCurrency(company.valuation_metrics?.last_round_valuation || 0)}
                        </p>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Pre-IPO Premium
                      </span>
                      <div className="flex items-center gap-2">
                        <Progress
                          value={company.valuation_metrics?.pre_ipo_premium_percentage || 0}
                          className="flex-1 h-3"
                        />
                        <span className="font-bold">
                          {formatPercent(company.valuation_metrics?.pre_ipo_premium_percentage || 0)}
                        </span>
                      </div>
                      <p className="text-xs text-gray-500 mt-1">
                        Premium over last institutional round
                      </p>
                    </div>

                    <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                      <Info className="h-4 w-4 text-blue-600" />
                      <AlertTitle className="text-blue-900 dark:text-blue-200">
                        Valuation Justification
                      </AlertTitle>
                      <AlertDescription className="text-blue-800 dark:text-blue-300 text-sm">
                        {company.valuation_metrics?.valuation_justification}
                      </AlertDescription>
                    </Alert>

                    {/* Comparable Companies */}
                    {company.valuation_metrics?.comparable_companies && company.valuation_metrics.comparable_companies.length > 0 && (
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold mb-2 block">
                          Comparable Companies
                        </span>
                        <div className="space-y-2">
                          {company.valuation_metrics.comparable_companies.map((comp: any, i: number) => (
                            <div key={i} className="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                              <span className="text-sm">{comp.name}</span>
                              <Badge variant="outline">{comp.revenue_multiple}x Revenue Multiple</Badge>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="ipo" className="space-y-6">
                {/* IPO Readiness */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Building2 className="w-5 h-5" />
                      8. IPO Readiness
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <Alert className="border-amber-200 bg-amber-50 dark:bg-amber-950/30">
                      <AlertTriangle className="h-4 w-4 text-amber-600" />
                      <AlertTitle className="text-amber-900 dark:text-amber-200">
                        IPO Timeline
                      </AlertTitle>
                      <AlertDescription className="text-amber-800 dark:text-amber-300">
                        {company.ipo_readiness?.ipo_timeline_indicative}
                      </AlertDescription>
                    </Alert>

                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Merchant Banker Appointed
                        </span>
                        <div className="font-semibold">
                          {company.ipo_readiness?.merchant_banker_appointed ? (
                            <>
                              <Badge className="bg-green-100 text-green-700 border-green-300 mr-2">
                                ✓ Yes
                              </Badge>
                              <span className="text-sm">{company.ipo_readiness?.merchant_banker_name}</span>
                            </>
                          ) : (
                            <Badge variant="secondary">Not yet appointed</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          SEBI Compliance Status
                        </span>
                        <p className="font-semibold">
                          {company.ipo_readiness?.sebi_compliance_status}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Legal Advisors
                        </span>
                        <p className="font-semibold text-sm">
                          {company.ipo_readiness?.legal_advisors || 'To be appointed'}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Governance Upgrades
                        </span>
                        <p className="font-semibold text-sm">
                          {company.ipo_readiness?.governance_upgrades_status}
                        </p>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 mb-2 block">
                        IPO Preparedness Score
                      </span>
                      <div className="flex items-center gap-2">
                        <Progress
                          value={(company.ipo_readiness?.ipo_preparedness_score || 0) * 20}
                          className="flex-1 h-4"
                        />
                        <span className="font-bold text-lg">
                          {company.ipo_readiness?.ipo_preparedness_score}/5
                        </span>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Liquidity & Exit */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Target className="w-5 h-5" />
                      9. Liquidity & Exit Reality
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <Alert variant="destructive">
                      <XCircle className="h-4 w-4" />
                      <AlertTitle>No Guaranteed Returns</AlertTitle>
                      <AlertDescription>
                        This investment has NO guaranteed liquidity or returns. Exit may take 3-5+ years.
                      </AlertDescription>
                    </Alert>

                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Lock-in Period
                        </span>
                        <p className="text-2xl font-bold text-red-600">
                          {company.liquidity_exit?.lock_in_period_months} months
                        </p>
                        <p className="text-xs text-gray-500">
                          Cannot sell shares before this period
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Secondary Market
                        </span>
                        <div className="font-semibold">
                          {company.liquidity_exit?.secondary_market_available ? (
                            <>
                              <Badge className="bg-blue-100 text-blue-700 border-blue-300">
                                Available
                              </Badge>
                              <div className="text-sm mt-1">{company.liquidity_exit?.secondary_platform_name}</div>
                            </>
                          ) : (
                            <Badge variant="secondary">Not Available</Badge>
                          )}
                        </div>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Historical Secondary Transactions
                      </span>
                      <p className="font-semibold">
                        {company.liquidity_exit?.historical_secondary_transactions || 0} transactions
                      </p>
                    </div>

                    <Separator />

                    {/* Exit Scenarios */}
                    <div>
                      <h4 className="font-semibold mb-3">Potential Exit Scenarios</h4>
                      <div className="space-y-2">
                        {company.liquidity_exit?.exit_scenarios?.map((scenario: any, i: number) => (
                          <div key={i} className="p-3 border rounded-lg dark:border-gray-700">
                            <div className="flex justify-between items-start">
                              <span className="font-semibold">{scenario.scenario}</span>
                              <Badge
                                className={
                                  scenario.probability === 'High'
                                    ? 'bg-green-100 text-green-700'
                                    : scenario.probability === 'Medium'
                                    ? 'bg-yellow-100 text-yellow-700'
                                    : 'bg-gray-100 text-gray-700'
                                }
                              >
                                {scenario.probability} Probability
                              </Badge>
                            </div>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                              Timeline: {scenario.timeline}
                            </p>
                          </div>
                        ))}
                      </div>
                    </div>

                    <Alert className="border-red-200 bg-red-50 dark:bg-red-950/30">
                      <AlertTriangle className="h-4 w-4 text-red-600" />
                      <AlertDescription className="text-red-800 dark:text-red-300 text-sm">
                        <strong>Critical:</strong> IPO timelines are indicative only and not guaranteed. Market conditions,
                        regulatory delays, or business performance may significantly delay or prevent IPO.
                      </AlertDescription>
                    </Alert>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="governance" className="space-y-6">
                {/* Promoter & Governance */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Shield className="w-5 h-5" />
                      10. Promoter & Governance Quality
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div className="md:col-span-2">
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Founder / CEO
                        </span>
                        <p className="text-xl font-bold">
                          {company.promoter_governance?.founder_name}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Background
                        </span>
                        <p className="font-semibold text-sm">
                          {company.promoter_governance?.founder_background}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Track Record
                        </span>
                        <p className="font-semibold text-sm">
                          {company.promoter_governance?.founder_track_record}
                        </p>
                      </div>
                    </div>

                    <Separator />

                    {/* Board Composition */}
                    <div className="grid md:grid-cols-3 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Board Size
                        </span>
                        <p className="text-2xl font-bold">
                          {company.promoter_governance?.board_size} members
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Independent Directors
                        </span>
                        <p className="text-2xl font-bold text-green-600">
                          {company.promoter_governance?.independent_directors}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Governance Score
                        </span>
                        <div className="flex items-center gap-2">
                          <Progress
                            value={(company.promoter_governance?.governance_score || 0) * 20}
                            className="flex-1 h-3"
                          />
                          <span className="font-bold">
                            {company.promoter_governance?.governance_score}/5
                          </span>
                        </div>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Related Party Transactions
                      </span>
                      <p className="text-sm mt-1">
                        {company.promoter_governance?.related_party_transactions}
                      </p>
                    </div>

                    {/* Board Committees */}
                    {company.promoter_governance?.board_composition && company.promoter_governance.board_composition.length > 0 && (
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold mb-2 block">
                          Board Committees
                        </span>
                        <div className="flex flex-wrap gap-2">
                          {company.promoter_governance.board_composition.map((committee: any, i: number) => (
                            <Badge key={i} variant="outline">
                              {typeof committee === 'string' ? committee : committee.name} ({committee.members || 0} members)
                            </Badge>
                          ))}
                        </div>
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* Regulatory & Legal */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <FileText className="w-5 h-5" />
                      11. Regulatory & Legal Risk
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          SEBI Registered
                        </span>
                        <div className="font-semibold">
                          {company.regulatory_legal?.sebi_registered ? (
                            <>
                              <Badge className="bg-green-100 text-green-700 border-green-300 mr-2">
                                ✓ Yes
                              </Badge>
                              <span className="text-sm">
                                {company.regulatory_legal?.sebi_registration_number}
                              </span>
                            </>
                          ) : (
                            <Badge variant="secondary">Not Registered</Badge>
                          )}
                        </div>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Pending Litigation
                        </span>
                        <div className="font-semibold">
                          {company.regulatory_legal?.pending_litigation_count === 0 ? (
                            <Badge className="bg-green-100 text-green-700 border-green-300">
                              None
                            </Badge>
                          ) : (
                            <Badge variant="destructive">
                              {company.regulatory_legal?.pending_litigation_count} cases
                            </Badge>
                          )}
                        </div>
                      </div>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Pending Litigation Details
                      </span>
                      <p className="text-sm mt-1">
                        {company.regulatory_legal?.pending_litigation}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Regulatory Investigations
                      </span>
                      <p className="font-semibold">
                        {company.regulatory_legal?.regulatory_investigations}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Compliance History
                      </span>
                      <p className="text-sm mt-1">
                        {company.regulatory_legal?.compliance_history}
                      </p>
                    </div>

                    {/* Sector Approvals */}
                    {company.regulatory_legal?.sector_approvals_status && company.regulatory_legal.sector_approvals_status.length > 0 && (
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold mb-2 block">
                          Sector-Specific Approvals
                        </span>
                        <div className="flex flex-wrap gap-2">
                          {company.regulatory_legal.sector_approvals_status.map((approval: string, i: number) => (
                            <Badge key={i} className="bg-blue-100 text-blue-700 border-blue-300">
                              {approval}
                            </Badge>
                          ))}
                        </div>
                      </div>
                    )}

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 mb-2 block">
                        Legal Risk Score
                      </span>
                      <div className="flex items-center gap-2">
                        <Progress
                          value={(company.regulatory_legal?.legal_risk_score || 0) * 20}
                          className="flex-1 h-4"
                        />
                        <span className="font-bold text-lg">
                          {company.regulatory_legal?.legal_risk_score}/5
                        </span>
                      </div>
                      <p className="text-xs text-gray-500 mt-1">
                        Lower score = Lower legal risk
                      </p>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="risks" className="space-y-6">
                {/* Platform Risk */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Shield className="w-5 h-5" />
                      12. Platform / Intermediary Risk
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                      <Info className="h-4 w-4 text-blue-600" />
                      <AlertTitle className="text-blue-900 dark:text-blue-200">
                        Legal Owner of Shares
                      </AlertTitle>
                      <AlertDescription className="text-blue-800 dark:text-blue-300 text-sm">
                        {company.platform_risk?.legal_owner_of_shares}
                      </AlertDescription>
                    </Alert>

                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Platform Fee
                        </span>
                        <p className="text-xl font-bold">
                          {formatPercent(company.platform_risk?.platform_fee_percentage || 0)}
                        </p>
                      </div>
                      <div>
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          Platform Spread
                        </span>
                        <p className="text-xl font-bold">
                          {formatPercent(company.platform_risk?.platform_spread_percentage || 0)}
                        </p>
                      </div>
                    </div>

                    <Separator />

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                        Contingency Plan
                      </span>
                      <p className="text-sm mt-1">
                        {company.platform_risk?.contingency_plan}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                        Demat Mechanism
                      </span>
                      <p className="text-sm mt-1">
                        {company.platform_risk?.demat_mechanism}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                        Custody Mechanism
                      </span>
                      <p className="text-sm mt-1">
                        {company.platform_risk?.custody_mechanism}
                      </p>
                    </div>

                    <div>
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        Platform Track Record
                      </span>
                      <p className="font-semibold">
                        {company.platform_risk?.platform_track_record}
                      </p>
                    </div>
                  </CardContent>
                </Card>

                {/* Comprehensive Risks */}
                <Card className="border-2 border-red-200 dark:border-red-800">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                      <AlertTriangle className="w-5 h-5" />
                      13. Comprehensive Risk Disclosures
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {/* Critical Warnings */}
                    <Alert variant="destructive">
                      <XCircle className="h-4 w-4" />
                      <AlertTitle>Critical Investment Warnings</AlertTitle>
                      <AlertDescription>
                        <ul className="list-disc list-inside text-sm space-y-1 mt-2">
                          <li><strong>Total Loss Possible:</strong> You may lose 100% of your investment</li>
                          <li><strong>No Guaranteed Returns:</strong> {company.comprehensive_risks?.no_guaranteed_claims}</li>
                          <li><strong>IPO Delay Acknowledged:</strong> IPO may be delayed beyond stated timeline or may never occur</li>
                        </ul>
                      </AlertDescription>
                    </Alert>

                    {/* Risk Levels */}
                    <div className="grid md:grid-cols-3 gap-4">
                      <Card className={`border-2 ${getRiskColor(company.comprehensive_risks?.market_risk_level || 'high')}`}>
                        <CardContent className="pt-4">
                          <div className="text-center">
                            <div className="text-sm font-semibold mb-1">Market Risk</div>
                            <div className="text-2xl font-bold">
                              {company.comprehensive_risks?.market_risk_level}
                            </div>
                          </div>
                        </CardContent>
                      </Card>

                      <Card className={`border-2 ${getRiskColor(company.comprehensive_risks?.liquidity_risk_level || 'high')}`}>
                        <CardContent className="pt-4">
                          <div className="text-center">
                            <div className="text-sm font-semibold mb-1">Liquidity Risk</div>
                            <div className="text-2xl font-bold">
                              {company.comprehensive_risks?.liquidity_risk_level}
                            </div>
                          </div>
                        </CardContent>
                      </Card>

                      <Card className="border-2 border-amber-200 bg-amber-50 dark:bg-amber-950/30">
                        <CardContent className="pt-4">
                          <div className="text-center">
                            <div className="text-sm font-semibold mb-1">Overall Risk</div>
                            <div className="text-2xl font-bold text-red-600">
                              High
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    </div>

                    <Separator />

                    {/* Downside Scenarios */}
                    <div>
                      <h4 className="font-semibold text-red-700 dark:text-red-400 mb-3">
                        Potential Downside Scenarios
                      </h4>
                      <div className="space-y-2">
                        {company.comprehensive_risks?.downside_scenarios?.map((scenario: string, i: number) => (
                          <Alert key={i} className="border-red-200 bg-red-50 dark:bg-red-950/30">
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                            <AlertDescription className="text-red-800 dark:text-red-300 text-sm">
                              {scenario}
                            </AlertDescription>
                          </Alert>
                        ))}
                      </div>
                    </div>

                    <Separator />

                    {/* Dilution Risk */}
                    <Alert className="border-amber-200 bg-amber-50 dark:bg-amber-950/30">
                      <AlertTriangle className="h-4 w-4 text-amber-600" />
                      <AlertTitle className="text-amber-900 dark:text-amber-200">
                        Dilution Risk
                      </AlertTitle>
                      <AlertDescription className="text-amber-800 dark:text-amber-300 text-sm">
                        {company.comprehensive_risks?.dilution_risk_explained}
                      </AlertDescription>
                    </Alert>

                    {/* Company-Specific Risks */}
                    {company.comprehensive_risks?.company_specific_risks && company.comprehensive_risks.company_specific_risks.length > 0 && (
                      <div>
                        <h4 className="font-semibold mb-3">Company-Specific Risks</h4>
                        <ul className="space-y-2">
                          {company.comprehensive_risks.company_specific_risks.map((risk: string, i: number) => (
                            <li key={i} className="flex items-start gap-2">
                              <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                              <span className="text-sm">{risk}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    <Separator />

                    {/* Final Warning */}
                    <Alert variant="destructive" className="border-2">
                      <XCircle className="h-5 w-5" />
                      <AlertTitle className="text-lg">INVESTMENT SUITABILITY WARNING</AlertTitle>
                      <AlertDescription className="text-sm space-y-2">
                        <p className="font-semibold">
                          This investment is ONLY suitable for sophisticated investors who:
                        </p>
                        <ul className="list-disc list-inside space-y-1">
                          <li>Can afford to lose 100% of invested capital</li>
                          <li>Have high risk tolerance and investment experience</li>
                          <li>Can wait 3-5+ years without liquidity</li>
                          <li>Understand Pre-IPO investment mechanics thoroughly</li>
                          <li>Have diversified portfolio with other liquid assets</li>
                        </ul>
                        <p className="font-bold mt-3">
                          DO NOT INVEST IF YOU NEED THIS MONEY FOR NEAR-TERM EXPENSES OR CANNOT AFFORD TO LOSE IT.
                        </p>
                      </AlertDescription>
                    </Alert>
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
