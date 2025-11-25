"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { TrendingUp, Building2, Layers, Clock, Zap, ArrowRight } from "lucide-react";

export default function ProductsPage() {
  const searchParams = useSearchParams();
  const filter = searchParams.get("filter"); // 'live' or 'upcoming'
  const view = searchParams.get("view"); // 'companies' or 'sectors'

  // Mock data - In production, fetch from API based on filters
  const allProducts = [
    { id: 1, name: "Swiggy", sector: "Food Tech", price: 100, minInvestment: "₹50,000", expectedReturn: "+340%", status: "live", fundingRound: "Series J" },
    { id: 2, name: "Ola Electric", sector: "EV & Mobility", price: 75, minInvestment: "₹1,00,000", expectedReturn: "+285%", status: "live", fundingRound: "Series E" },
    { id: 3, name: "PharmEasy", sector: "Health Tech", price: 50, minInvestment: "₹75,000", expectedReturn: "+412%", status: "upcoming", fundingRound: "Series F" },
    { id: 4, name: "Byju's", sector: "EdTech", price: 120, minInvestment: "₹2,00,000", expectedReturn: "+198%", status: "live", fundingRound: "Series F" },
    { id: 5, name: "Meesho", sector: "E-Commerce", price: 90, minInvestment: "₹60,000", expectedReturn: "+265%", status: "upcoming", fundingRound: "Series E" },
    { id: 6, name: "Zepto", sector: "Quick Commerce", price: 85, minInvestment: "₹80,000", expectedReturn: "+320%", status: "live", fundingRound: "Series D" },
  ];

  const sectors = ["Food Tech", "EV & Mobility", "Health Tech", "EdTech", "E-Commerce", "Quick Commerce", "FinTech", "SaaS"];

  // Filter products based on query params
  let filteredProducts = allProducts;
  if (filter === "live") {
    filteredProducts = allProducts.filter(p => p.status === "live");
  } else if (filter === "upcoming") {
    filteredProducts = allProducts.filter(p => p.status === "upcoming");
  }

  // Determine page title and description
  let pageTitle = "Pre-IPO Investment Opportunities";
  let pageDescription = "Invest in tomorrow's market leaders today";

  if (filter === "live") {
    pageTitle = "Live Pre-IPO Deals";
    pageDescription = "Active investment opportunities available now";
  } else if (filter === "upcoming") {
    pageTitle = "Upcoming Pre-IPO Deals";
    pageDescription = "Coming soon - register your interest";
  } else if (view === "companies") {
    pageTitle = "Browse by Company";
    pageDescription = "Explore all pre-IPO companies";
  } else if (view === "sectors") {
    pageTitle = "Browse by Sector";
    pageDescription = "Explore investment opportunities by industry";
  }

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-green-100 dark:bg-green-900/30 rounded-full px-6 py-3 mb-8">
              <TrendingUp className="w-5 h-5 text-green-600 dark:text-green-400" />
              <span className="font-semibold text-green-900 dark:text-green-300">
                {filter === "live" ? `${filteredProducts.length} Live Deals` : filter === "upcoming" ? `${filteredProducts.length} Upcoming` : "50+ Opportunities"}
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              {pageTitle.split(" ").slice(0, -1).join(" ")}{" "}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                {pageTitle.split(" ").slice(-1)}
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8">{pageDescription}</p>

            {/* Filter Tabs */}
            <div className="flex flex-wrap justify-center gap-4">
              <Link
                href="/products"
                className={`px-6 py-3 rounded-xl font-semibold transition-all ${
                  !filter && !view
                    ? "bg-purple-600 text-white shadow-lg"
                    : "bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                }`}
              >
                All Deals
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
                Live Deals
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

      {/* Products Grid or Sectors View */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {view === "sectors" ? (
            // Sectors View
            <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
              {sectors.map((sector, index) => (
                <Link
                  key={index}
                  href={`/products?sector=${sector.toLowerCase().replace(/\s+/g, "-")}`}
                  className="group bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 hover:shadow-xl transition-all duration-300 hover:scale-105"
                >
                  <Building2 className="w-12 h-12 text-purple-600 dark:text-purple-400 mb-4" />
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">{sector}</h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Explore opportunities</p>
                  <ArrowRight className="w-5 h-5 text-purple-600 dark:text-purple-400 mt-4 group-hover:translate-x-2 transition-transform" />
                </Link>
              ))}
            </div>
          ) : (
            // Products Grid
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {filteredProducts.map((product) => (
                <div
                  key={product.id}
                  className="group bg-white dark:bg-slate-900 rounded-3xl border border-gray-200 dark:border-slate-800 overflow-hidden hover:shadow-2xl transition-all duration-300 hover:scale-105"
                >
                  <div className="p-8">
                    <div className="flex items-start justify-between mb-4">
                      <div>
                        <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                          {product.name}
                        </h3>
                        <span className="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-sm font-medium rounded-full">
                          {product.sector}
                        </span>
                      </div>
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-bold ${
                          product.status === "live"
                            ? "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
                            : "bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400"
                        }`}
                      >
                        {product.status === "live" ? "LIVE" : "UPCOMING"}
                      </span>
                    </div>

                    <div className="space-y-4 mb-6">
                      <div className="flex justify-between items-center">
                        <span className="text-sm text-gray-600 dark:text-gray-400">Min. Investment</span>
                        <span className="text-lg font-bold text-gray-900 dark:text-white">
                          {product.minInvestment}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-sm text-gray-600 dark:text-gray-400">Expected Returns</span>
                        <span className="text-lg font-bold text-green-600 dark:text-green-400">
                          {product.expectedReturn}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-sm text-gray-600 dark:text-gray-400">Funding Round</span>
                        <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                          {product.fundingRound}
                        </span>
                      </div>
                    </div>

                    <Button
                      className="w-full"
                      asChild
                      disabled={product.status !== "live"}
                    >
                      <Link
                        href={product.status === "live" ? "/signup" : "#"}
                        className={`block text-center py-4 rounded-xl font-bold transition-all ${
                          product.status === "live"
                            ? "bg-gradient-to-r from-purple-600 to-blue-600 text-white hover:shadow-xl"
                            : "bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                        }`}
                      >
                        {product.status === "live" ? "Invest Now →" : "Coming Soon"}
                      </Link>
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">Ready to Start Investing?</h2>
          <p className="text-xl text-purple-100 mb-8">
            Join 50,000+ investors building wealth with pre-IPO investments
          </p>
          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Open Free Account
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>
        </div>
      </section>
    </div>
  );
}