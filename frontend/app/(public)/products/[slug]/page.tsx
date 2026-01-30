/**
 * EPIC 5 Story 5.1 - Public Frontend: Company Detail Page
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
 * - Mandatory disclaimer and platform relationship banners
 *
 * VISIBILITY RULES:
 * - Shows only companies with disclosure_tier >= tier_2_live
 * - Data sanitized via publicDataSanitizer at API boundary
 * - Returns 404 if company not found or not publicly visible
 *
 * STORY 5.1 COMPLIANCE:
 * - A1: Read-only, no backend investor/admin services
 * - A2: Only display whitelisted fields (name, logo, sector, description, etc.)
 * - A3: PlatformRelationshipBanner shows "Listed for informational purposes"
 * - A4: No financial data, no disclosures, no tier status, no buy CTAs
 * - A5: Links to sign-up/learn-more only
 * - A6: 404 for non-public tier companies (enforced at API layer)
 */

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
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
import { PlatformRelationshipBanner } from "@/components/public/PlatformRelationshipBanner";
import {
  fetchPublicCompanyDetail,
  PublicCompanyDetail,
} from "@/lib/publicCompanyApi";

export default function ProductDetailPage() {
  const { slug } = useParams();

  const [company, setCompany] = useState<PublicCompanyDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Fetch company detail from backend (sanitized at API layer)
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
        // fetchPublicCompanyDetail applies sanitization and tier gate
        // Returns null if company is not publicly visible (tier < 2)
        const result = await fetchPublicCompanyDetail(slug);

        if (!result) {
          // Company not found or not publicly visible
          // This triggers 404 display per Story 5.1 A6
          setError("Company not found or not publicly available");
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
  // Per Story 5.1 A6: Companies below tier_2_live show 404
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
            {/* Company Logo - ALLOWED per Story 5.1 A2 */}
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

            {/* Company Header Info - ALLOWED fields only */}
            <div className="flex-1">
              {/* Company Name - ALLOWED */}
              <h1 className="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white mb-4">
                {company.name}
              </h1>

              {/* Sector Badge - ALLOWED */}
              {company.sector && (
                <Badge className="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-base px-4 py-2 mb-4">
                  {company.sector}
                </Badge>
              )}

              {/* Company Meta Info - ALLOWED fields only */}
              <div className="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-400 mt-4">
                {/* Headquarters - ALLOWED */}
                {company.headquarters && (
                  <div className="flex items-center gap-2">
                    <MapPin className="w-4 h-4" />
                    <span>{company.headquarters}</span>
                  </div>
                )}
                {/* Founded Year - ALLOWED */}
                {company.founded_year && (
                  <div className="flex items-center gap-2">
                    <Calendar className="w-4 h-4" />
                    <span>Founded {company.founded_year}</span>
                  </div>
                )}
                {/* Website URL - ALLOWED */}
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

      {/* Disclaimer Banner - REQUIRED per Story 5.1 */}
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
              {/* About Section - ALLOWED (description is whitelisted) */}
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

              {/*
               * STORY 5.1 COMPLIANCE: Disclosures section REMOVED
               *
               * Per Story 5.1 "Must NEVER Display":
               * - Risk flags, disclosure completeness
               *
               * Disclosures are investor-only content and must not appear
               * on public pages. The API layer now drops this data, but
               * even if it were present, we do not render it here.
               */}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/*
               * STORY 5.1 COMPLIANCE: Platform Status card REPLACED
               *
               * Per Story 5.1 requirements:
               * - Must display "Listed for informational purposes"
               * - Must NOT display tier_status, lifecycle_state (investor-only)
               *
               * PlatformRelationshipBanner provides the required statement
               * without investor-oriented tier information.
               */}
              <PlatformRelationshipBanner variant="prominent" />

              {/* CTA Card - Updated for Story 5.1 (no investment solicitation) */}
              <Card>
                <CardContent className="pt-6">
                  <h3 className="font-bold text-lg mb-3 text-gray-900 dark:text-white">
                    Want to Learn More?
                  </h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Create an account to access detailed company information and platform features
                  </p>
                  <Link href="/signup">
                    <Button className="w-full" size="lg">
                      Sign Up to Learn More
                      <ArrowRight className="w-4 h-4 ml-2" />
                    </Button>
                  </Link>
                  <p className="text-xs text-gray-500 dark:text-gray-500 mt-3 text-center">
                    Requires platform verification for full access
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
