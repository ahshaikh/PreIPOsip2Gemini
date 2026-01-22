'use client';

import { useState } from "react";
import api from "@/lib/api";
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  Building2,
  MapPin,
  TrendingUp,
  Search,
  ChevronLeft,
  ChevronRight,
  CheckCircle2
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";

// FIX: Get backend URL for logo display
const getBackendURL = () => {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
  return apiUrl.endsWith('/api/v1') ? apiUrl.slice(0, -7) : apiUrl.replace('/api/v1', '');
};
const BACKEND_URL = getBackendURL();

interface Company {
  id: number;
  name: string;
  slug: string;
  logo?: string;
  description: string;
  sector: string;
  city?: string;
  state?: string;
  latest_valuation?: number;
  is_verified: boolean;
}

export default function CompaniesPage() {
  const [currentPage, setCurrentPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedSector, setSelectedSector] = useState('');
  const [sortBy, setSortBy] = useState('latest');

  // Fetch sectors
  const { data: sectorsData } = useQuery({
    queryKey: ['companySectors'],
    queryFn: async () => {
      const { data } = await api.get('/companies/sectors');
      return data;
    },
  });

  // Fetch companies
  const { data: response, isLoading } = useQuery({
    queryKey: ['companies', currentPage, searchQuery, selectedSector, sortBy],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        ...(searchQuery && { search: searchQuery }),
        ...(selectedSector && { sector: selectedSector }),
        ...(sortBy && { sort_by: sortBy }),
      });
      const { data } = await api.get(`/companies?${params}`);
      return data;
    },
    placeholderData: keepPreviousData,
  });

  const companies: Company[] = response?.data || [];
  const pagination = response?.pagination || {
    total: 0,
    per_page: 12,
    current_page: 1,
    last_page: 1,
  };

  const sectors = sectorsData?.sectors || [];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      notation: 'compact',
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const handleSearch = (value: string) => {
    setSearchQuery(value);
    setCurrentPage(1);
  };

  const handleSectorChange = (value: string) => {
    setSelectedSector(value === 'all' ? '' : value);
    setCurrentPage(1);
  };

  const handleSortChange = (value: string) => {
    setSortBy(value);
    setCurrentPage(1);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div className="container py-16">
          <div className="max-w-3xl">
            <h1 className="text-5xl font-bold mb-4">Explore Pre-IPO Companies</h1>
            <p className="text-xl text-white/90">
              Discover promising companies preparing for their IPO. Get insights into financials,
              leadership teams, and growth trajectories.
            </p>
          </div>
        </div>
      </div>

      {/* Filters Section */}
      <div className="bg-white border-b sticky top-0 z-10 shadow-sm">
        <div className="container py-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <div className="md:col-span-2 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                type="text"
                placeholder="Search companies..."
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                className="pl-10"
              />
            </div>

            {/* Sector Filter */}
            <Select value={selectedSector || 'all'} onValueChange={handleSectorChange}>
              <SelectTrigger>
                <SelectValue placeholder="All Sectors" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Sectors</SelectItem>
                {sectors.map((sector: string) => (
                  <SelectItem key={sector} value={sector}>
                    {sector}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Sort By */}
            <Select value={sortBy} onValueChange={handleSortChange}>
              <SelectTrigger>
                <SelectValue placeholder="Sort By" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="latest">Latest First</SelectItem>
                <SelectItem value="name">Name (A-Z)</SelectItem>
                <SelectItem value="valuation_high">Valuation: High to Low</SelectItem>
                <SelectItem value="valuation_low">Valuation: Low to High</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>

      {/* Companies Grid */}
      <div className="container py-8">
        <div className="mb-6 flex justify-between items-center">
          <p className="text-muted-foreground">
            {pagination.total} {pagination.total === 1 ? 'company' : 'companies'} found
          </p>
          {(searchQuery || selectedSector) && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => {
                setSearchQuery('');
                setSelectedSector('');
                setCurrentPage(1);
              }}
            >
              Clear Filters
            </Button>
          )}
        </div>

        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[...Array(6)].map((_, i) => (
              <Card key={i} className="animate-pulse">
                <CardHeader>
                  <div className="h-6 bg-gray-200 rounded w-3/4 mb-2"></div>
                  <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                </CardHeader>
                <CardContent>
                  <div className="h-20 bg-gray-200 rounded"></div>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : companies.length > 0 ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {companies.map((company) => (
                <Link key={company.id} href={`/companies/${company.slug}`}>
                  <Card className="h-full hover:shadow-lg transition-shadow cursor-pointer">
                    <CardHeader>
                      <div className="flex items-start gap-4 mb-4">
                        {company.logo ? (
                          <div className="border rounded-lg p-2 bg-white dark:bg-gray-900">
                            <Image
                              src={`${BACKEND_URL}/storage/${company.logo}`}
                              alt={company.name}
                              width={60}
                              height={60}
                              className="object-contain"
                              onError={(e) => {
                                console.error('Failed to load company logo:', company.logo);
                                e.currentTarget.style.display = 'none';
                              }}
                            />
                          </div>
                        ) : (
                          <div className="border rounded-lg p-4 bg-gray-100 dark:bg-gray-800">
                            <Building2 className="w-8 h-8 text-gray-400" />
                          </div>
                        )}
                        <div className="flex-1">
                          <div className="flex items-center gap-2 mb-1">
                            <CardTitle className="text-xl">{company.name}</CardTitle>
                            {company.is_verified && (
                              <CheckCircle2 className="w-5 h-5 text-green-500" />
                            )}
                          </div>
                          <Badge variant="secondary">{typeof company.sector === 'string' ? company.sector : company.sector.name}</Badge>
                        </div>
                      </div>
                      <CardDescription className="line-clamp-3">
                        {company.description}
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-2">
                        {company.city && company.state && (
                          <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin className="w-4 h-4" />
                            {company.city}, {company.state}
                          </div>
                        )}
                        {company.latest_valuation && (
                          <div className="flex items-center gap-2 text-sm">
                            <TrendingUp className="w-4 h-4 text-blue-600" />
                            <span className="font-semibold text-blue-600">
                              {formatCurrency(company.latest_valuation)}
                            </span>
                          </div>
                        )}
                      </div>
                      <Button className="w-full mt-4" variant="outline">
                        View Profile
                      </Button>
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>

            {/* Pagination */}
            {pagination.last_page > 1 && (
              <div className="mt-8 flex justify-center items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                  disabled={pagination.current_page === 1}
                >
                  <ChevronLeft className="w-4 h-4 mr-1" />
                  Previous
                </Button>

                <div className="flex items-center gap-2">
                  {[...Array(pagination.last_page)].map((_, i) => {
                    const page = i + 1;
                    // Show first 2, last 2, and current page with neighbors
                    if (
                      page === 1 ||
                      page === pagination.last_page ||
                      (page >= pagination.current_page - 1 && page <= pagination.current_page + 1)
                    ) {
                      return (
                        <Button
                          key={page}
                          variant={pagination.current_page === page ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => setCurrentPage(page)}
                        >
                          {page}
                        </Button>
                      );
                    } else if (
                      page === pagination.current_page - 2 ||
                      page === pagination.current_page + 2
                    ) {
                      return <span key={page} className="px-2">...</span>;
                    }
                    return null;
                  })}
                </div>

                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setCurrentPage(p => Math.min(pagination.last_page, p + 1))}
                  disabled={pagination.current_page === pagination.last_page}
                >
                  Next
                  <ChevronRight className="w-4 h-4 ml-1" />
                </Button>
              </div>
            )}
          </>
        ) : (
          <Card>
            <CardContent className="py-16 text-center">
              <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-2xl font-semibold mb-2">No companies found</h3>
              <p className="text-muted-foreground mb-4">
                Try adjusting your search or filters to find what you're looking for.
              </p>
              <Button onClick={() => {
                setSearchQuery('');
                setSelectedSector('');
                setCurrentPage(1);
              }}>
                Clear All Filters
              </Button>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
