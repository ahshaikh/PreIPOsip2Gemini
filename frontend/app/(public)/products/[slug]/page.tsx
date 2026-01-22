/**
 * PHASE 5 - Public Frontend: Company Profile (Detail Page)
 *
 * PURPOSE:
 * - Show company information to unauthenticated users
 * - Establish legitimacy and educate
 * - NO investment solicitation
 *
 * DEFENSIVE PRINCIPLES:
 * - NO hardcoded company data
 * - NO financial data, pricing, or valuations
 * - NO buy signals or "invest now" buttons
 * - Show ONLY: identity, branding, sector, description, headquarters, website
 * - Mandatory disclaimer banner
 *
 * VISIBILITY RULES:
 * - Shows only companies marked visible_on_public by platform
 * - Returns 404 if company not found or not publicly visible
 */

"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import {
  Building2,
  Globe,
  MapPin,
  Calendar,
  ArrowRight,
  Loader2,
  ExternalLink,
  ArrowLeft,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { PublicDisclaimerBanner } from "@/components/public/PublicDisclaimerBanner";
import {
  fetchPublicCompanyDetail,
  PublicCompanyDetail,
} from "@/lib/publicCompanyApi";

export default function ProductDetailPage() {
  const { slug } = useParams();
  const router = useRouter();

  const [company, setCompany] = useState<PublicCompanyDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Fetch company detail from backend
  useEffect(() => {
    async function loadCompany() {
      if (!slug || typeof slug !== "string") {
        setError("Invalid company");
        setLoading(false);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const result = await fetchPublicCompanyDetail(slug);

        if (!result) {
          setError("Company not found or not publicly visible");
          setLoading(false);
          return;
        }

        setCompany(result);
      } catch (err: any) {
        console.error("[PUBLIC COMPANY DETAIL] Failed to load company:", err);

        if (err?.response?.status === 404) {
          setError("Company not found");
        } else {
          setError("Unable to load company information. Please try again later.");
        }
      } finally {
        setLoading(false);
      }
    }

    loadCompany();
  }, [slug]);

  // Loading state
  if (loading) {
    return (
      <div className="min-h-screen bg-white dark:bg-slate-950 flex items-center justify-center">
        <div className="flex flex-col items-center space-y-4">
          <Loader2 className="w-12 h-12 animate-spin text-purple-600 dark:text-purple-400" />
          <p className="text-gray-600 dark:text-gray-400">Loading company information...</p>
        </div>
      </div>
    );
  }

  // Error state (404 or not visible)
  if (error || !company) {
    return (
      <div className="min-h-screen bg-white dark:bg-slate-950 flex items-center justify-center">
        <div className="text-center max-w-md px-4">
          <Building2 className="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            {error || "Company Not Found"}
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mb-6">
            This company may not be publicly listed or does not exist.
          </p>
          <Link href="/products">
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to All Companies
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-12 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Back Button */}
          <Link
            href="/products"
            className="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 mb-6 transition-colors"
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to All Companies
          </Link>

          <div className="flex flex-col md:flex-row items-start gap-8">
            {/* Company Logo */}
            {company.logo_url && (
              <div className="relative w-32 h-32 rounded-2xl overflow-hidden bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex-shrink-0 shadow-lg">
                <Image
                  src={company.logo_url}
                  alt={`${company.name} logo`}
                  fill
                  className="object-contain p-4"
                />
              </div>
            )}

            {/* Company Header Info */}
            <div className="flex-1">
              <h1 className="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white mb-4">
                {company.name}
              </h1>

              {/* Sector Badge */}
              {company.sector && (
                <Badge className="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-base px-4 py-2 mb-4">
                  {typeof company.sector === 'string' ? company.sector : company.sector.name}
                </Badge>
              )}

              {/* Company Meta Info */}
              <div className="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-400 mt-4">
                {company.headquarters && (
                  <div className="flex items-center gap-2">
                    <MapPin className="w-4 h-4" />
                    <span>{company.headquarters}</span>
                  </div>
                )}
                {company.founded_year && (
                  <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4" />
                    <span>Founded {company.founded_year}</span>
                  </div>
                )}
                {company.website_url && (
                  <a
                    href={company.website_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 text-purple-600 dark:text-purple-400 hover:underline"
                  >
                    <Globe className="w-4 h-4" />
                    <span>Visit Website</span>
                    <ExternalLink className="w-3 h-3" />
                  </a>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Disclaimer Banner */}
      <section className="py-8 bg-amber-50 dark:bg-amber-950/20 border-y border-amber-200 dark:border-amber-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <PublicDisclaimerBanner variant="prominent" />
        </div>
      </section>

      {/* Company Information */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="md:col-span-2 space-y-8">
              {/* About Section */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-2xl">About {company.name}</CardTitle>
                </CardHeader>
                <CardContent>
                  {company.description ? (
                    <div className="prose dark:prose-invert max-w-none">
                      <p className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {company.description}
                      </p>
                    </div>
                  ) : company.short_description ? (
                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                      {company.short_description}
                    </p>
                  ) : (
                    <p className="text-gray-500 dark:text-gray-400 italic">
                      Description not available
                    </p>
                  )}
                </CardContent>
              </Card>

              {/* PHASE 5: Show only approved disclosures (informational only) */}
              {company.disclosures && company.disclosures.length > 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle className="text-2xl">Company Information</CardTitle>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Platform-reviewed information (informational purposes only)
                    </p>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {company.disclosures.map((disclosure, index) => (
                        <div
                          key={index}
                          className="border-b border-gray-200 dark:border-slate-800 last:border-0 pb-4 last:pb-0"
                        >
                          <div className="flex items-center justify-between mb-2">
                            <h4 className="font-semibold text-gray-900 dark:text-white">
                              {disclosure.module_name}
                            </h4>
                            {disclosure.status === "approved" ? (
                              <Badge variant="outline" className="text-green-600 border-green-600">
                                Platform Reviewed
                              </Badge>
                            ) : disclosure.status === "under_review" ? (
                              <Badge variant="outline" className="text-amber-600 border-amber-600">
                                Under Review
                              </Badge>
                            ) : (
                              <Badge variant="outline" className="text-gray-500 border-gray-500">
                                Not Available
                              </Badge>
                            )}
                          </div>

                          {disclosure.status === "approved" && disclosure.data ? (
                            <div className="text-sm text-gray-700 dark:text-gray-300">
                              {/* Show sanitized disclosure data */}
                              <p className="text-gray-600 dark:text-gray-400 italic">
                                Detailed information available to subscribers
                              </p>
                            </div>
                          ) : (
                            <p className="text-sm text-gray-500 dark:text-gray-400 italic">
                              {disclosure.message || "This information is not yet available"}
                            </p>
                          )}
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* PHASE 5: No investment details, just platform info */}
              <Card className="border-purple-200 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-950/20">
                <CardHeader>
                  <CardTitle className="text-lg">Platform Status</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  {company.platform_context?.lifecycle_state && (
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 dark:text-gray-400">Lifecycle State:</span>
                      <Badge variant="outline" className="capitalize">
                        {company.platform_context.lifecycle_state.replace("_", " ")}
                      </Badge>
                    </div>
                  )}

                  {company.platform_context?.tier_status && (
                    <>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600 dark:text-gray-400">Tier 1:</span>
                        <span
                          className={
                            company.platform_context.tier_status.tier_1_approved
                              ? "text-green-600 dark:text-green-400"
                              : "text-gray-400"
                          }
                        >
                          {company.platform_context.tier_status.tier_1_approved
                            ? "✓ Approved"
                            : "Pending"}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-gray-600 dark:text-gray-400">Tier 2:</span>
                        <span
                          className={
                            company.platform_context.tier_status.tier_2_approved
                              ? "text-green-600 dark:text-green-400"
                              : "text-gray-400"
                          }
                        >
                          {company.platform_context.tier_status.tier_2_approved
                            ? "✓ Approved"
                            : "Pending"}
                        </span>
                      </div>
                    </>
                  )}

                  <div className="pt-3 border-t border-purple-200 dark:border-purple-800">
                    <p className="text-xs text-gray-600 dark:text-gray-400">
                      Platform status is informational only and does not constitute investment advice
                      or recommendation.
                    </p>
                  </div>
                </CardContent>
              </Card>

              {/* CTA Card */}
              <Card>
                <CardContent className="pt-6">
                  <h3 className="font-bold text-lg mb-3 text-gray-900 dark:text-white">
                    Interested in Investing?
                  </h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Create an account to access detailed information and investment opportunities
                  </p>
                  <Link href="/signup">
                    <Button className="w-full" size="lg">
                      Create Account
                      <ArrowRight className="w-4 h-4 ml-2" />
                    </Button>
                  </Link>
                  <p className="text-xs text-gray-500 dark:text-gray-500 mt-3 text-center">
                    Requires platform verification
                  </p>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
