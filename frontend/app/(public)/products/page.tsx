/**
 * PHASE 5 - Public Frontend: Company Listings
 *
 * PURPOSE:
 * - Establish platform legitimacy
 * - Educate users about available companies
 * - Feed the subscription funnel
 * - Avoid investment solicitation or implied advice
 *
 * DEFENSIVE PRINCIPLES:
 * - NO hardcoded company data
 * - NO investment solicitation (prices, returns, "invest now")
 * - NO financial data or buy signals
 * - Show ONLY: company name, logo, sector, high-level description
 * - Mandatory disclaimer banner
 * - Dynamic, platform-driven filtering
 *
 * VISIBILITY RULES:
 * - Shows only companies marked visible_on_public by admin
 * - Automatically updates when visibility changes
 * - No manual page updates required
 */

"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { Building2, Layers, Clock, Zap, ArrowRight, Loader2 } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { PublicDisclaimerBanner } from "@/components/public/PublicDisclaimerBanner";
import {
  fetchPublicCompanies,
  PublicCompany,
} from "@/lib/publicCompanyApi";

export default function ProductsPage() {
  const searchParams = useSearchParams();
  const filter = searchParams.get("filter") as 'all' | 'live' | 'upcoming' | null;
  const sector = searchParams.get("sector");
  const view = searchParams.get("view");

  const [companies, setCompanies] = useState<PublicCompany[]>([]);
  const [sectors, setSectors] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Fetch companies from backend
  useEffect(() => {
    async function loadCompanies() {
      setLoading(true);
      setError(null);

      try {
        const result = await fetchPublicCompanies({
          filter: filter || 'all',
          sector: sector || undefined,
        });

        setCompanies(result.companies);
        setSectors(result.sectors);
      } catch (err) {
        console.error('[PUBLIC PRODUCTS] Failed to load companies:', err);
        setError('Unable to load companies at this time. Please try again later.');
      } finally {
        setLoading(false);
      }
    }

    loadCompanies();
  }, [filter, sector]);

  // Determine page title and description
  let pageTitle = "Pre-IPO Companies";
  let pageDescription = "Explore information about pre-IPO companies";

  if (filter === "live") {
    pageTitle = "Live Pre-IPO Companies";
    pageDescription = "Active pre-IPO companies currently available";
  } else if (filter === "upcoming") {
    pageTitle = "Upcoming Pre-IPO Companies";
    pageDescription = "Pre-IPO companies coming soon";
  } else if (view === "sectors") {
    pageTitle = "Browse by Sector";
    pageDescription = "Explore companies by industry sector";
  } else if (sector) {
    pageTitle = `${sector} Companies`;
    pageDescription = `Explore pre-IPO companies in ${sector}`;
  }

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            {/* PHASE 5: No investment solicitation badges */}
            <div className="inline-flex items-center space-x-2 bg-blue-100 dark:bg-blue-900/30 rounded-full px-6 py-3 mb-8">
              <Building2 className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              <span className="font-semibold text-blue-900 dark:text-blue-300">
                Information Only
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              {pageTitle.split(" ").slice(0, -1).join(" ")}{" "}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                {pageTitle.split(" ").slice(-1)}
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8">{pageDescription}</p>

            {/* PHASE 5: Mandatory Disclaimer Banner */}
            <PublicDisclaimerBanner variant="default" className="mb-8" />

            {/* Filter Tabs */}
            <div className="flex flex-wrap justify-center gap-4 mt-8">
              <Link
                href="/products"
                className={`px-6 py-3 rounded-xl font-semibold transition-all ${
                  !filter && !view && !sector
                    ? "bg-purple-600 text-white shadow-lg"
                    : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                }`}
              >
                All Companies
              </Link>
              <Link
                href="/products?filter=live"
                className={`px-6 py-3 rounded-xl font-semibold transition-all ${
                  filter === "live"
                    ? "bg-green-600 text-white shadow-lg"
                    : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                }`}
              >
                <Zap className="w-4 h-4 inline mr-2" />
                Live
              </Link>
              <Link
                href="/products?filter=upcoming"
                className={`px-6 py-3 rounded-xl font-semibold transition-all ${
                  filter === "upcoming"
                    ? "bg-amber-600 text-white shadow-lg"
                    : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                }`}
              >
                <Clock className="w-4 h-4 inline mr-2" />
                Upcoming
              </Link>
              <Link
                href="/products?view=sectors"
                className={`px-6 py-3 rounded-xl font-semibold transition-all ${
                  view === "sectors"
                    ? "bg-blue-600 text-white shadow-lg"
                    : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                }`}
              >
                <Layers className="w-4 h-4 inline mr-2" />
                By Sector
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Companies Grid or Sectors View */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {loading ? (
            // Loading State
            <div className="flex flex-col items-center justify-center py-20">
              <Loader2 className="w-12 h-12 animate-spin text-purple-600 dark:text-purple-400 mb-4" />
              <p className="text-gray-600 dark:text-gray-400">Loading companies...</p>
            </div>
          ) : error ? (
            // Error State
            <div className="text-center py-20">
              <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
              <Button onClick={() => window.location.reload()} variant="outline">
                Retry
              </Button>
            </div>
          ) : view === "sectors" ? (
            // Sectors View
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {sectors.length > 0 ? (
                sectors.map((sectorName, index) => (
                  <Link
                    key={index}
                    href={`/products?sector=${encodeURIComponent(sectorName)}`}
                    className="group bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 hover:shadow-xl transition-all duration-300 hover:scale-105"
                  >
                    <Building2 className="w-12 h-12 text-purple-600 dark:text-purple-400 mb-4" />
                    <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">{sectorName}</h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Explore companies</p>
                    <ArrowRight className="w-5 h-5 text-purple-600 dark:text-purple-400 mt-4 group-hover:translate-x-2 transition-transform" />
                  </Link>
                ))
              ) : (
                <div className="col-span-full text-center py-12">
                  <p className="text-gray-600 dark:text-gray-400">No sectors available</p>
                </div>
              )}
            </div>
          ) : (
            // Companies Grid
            <>
              {companies.length > 0 ? (
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                  {companies.map((company) => (
                    <Card key={company.id} className="h-full border-gray-200 dark:border-slate-800 hover:shadow-2xl transition-all duration-300 hover:scale-105">
                      <CardContent className="p-8">
                        {/* Company Logo */}
                        {company.logo_url && (
                          <div className="mb-6 flex items-center justify-center">
                            <div className="relative w-24 h-24 rounded-xl overflow-hidden bg-gray-100 dark:bg-slate-800">
                              <Image
                                src={company.logo_url}
                                alt={`${company.name} logo`}
                                fill
                                className="object-contain p-2"
                              />
                            </div>
                          </div>
                        )}

                        {/* Company Name */}
                        <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                          {company.name}
                        </h3>

                        {/* Sector Badge */}
                        {company.sector && (
                          <span className="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium rounded-full mb-4">
                            {company.sector}
                          </span>
                        )}

                        {/* Short Description */}
                        {company.short_description && (
                          <p className="text-sm text-gray-600 dark:text-gray-400 mb-6 line-clamp-3">
                            {company.short_description}
                          </p>
                        )}

                        {/* Headquarters & Founded */}
                        <div className="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-500 mb-6">
                          {company.headquarters && (
                            <span>📍 {company.headquarters}</span>
                          )}
                          {company.founded_year && (
                            <span>📅 Founded {company.founded_year}</span>
                          )}
                        </div>

                        {/* FIX: Redirect to login instead of company detail page */}
                        <Link href="/login">
                          <Button
                            variant="outline"
                            className="w-full hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all"
                          >
                            Login to View Details
                            <ArrowRight className="w-4 h-4 ml-2" />
                          </Button>
                        </Link>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              ) : (
                <div className="text-center py-20">
                  <Building2 className="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                  <h3 className="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    No Companies Found
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400 mb-6">
                    {sector
                      ? `No companies available in ${sector} sector at this time.`
                      : "No companies available at this time."}
                  </p>
                  <Link href="/products">
                    <Button variant="outline">View All Companies</Button>
                  </Link>
                </div>
              )}
            </>
          )}
        </div>
      </section>

      {/* PHASE 5: CTA Section - Emphasizes subscription, NOT direct investment */}
      <section className="py-20 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">Interested in Pre-IPO Investing?</h2>
          <p className="text-xl text-purple-100 mb-8">
            Create an account to access detailed information and investment opportunities
          </p>
          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Create Free Account
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>
          <p className="mt-6 text-sm text-purple-200">
            Account creation does not constitute investment. Platform verification and risk acknowledgements required.
          </p>
        </div>
      </section>
    </div>
  );
}
