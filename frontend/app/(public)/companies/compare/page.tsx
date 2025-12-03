'use client';

import { useState, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Building2,
  MapPin,
  TrendingUp,
  Users,
  Calendar,
  FileText,
  DollarSign,
  CheckCircle2,
  XCircle,
  Plus,
  X,
  ArrowLeft
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";

interface Company {
  id: number;
  name: string;
  slug: string;
  logo?: string;
  description: string;
  sector: string;
  founded_year?: number;
  city?: string;
  state?: string;
  country?: string;
  website?: string;
  email?: string;
  phone?: string;
  latest_valuation?: number;
  employees_count?: number;
  is_verified: boolean;
  financial_reports_count?: number;
  documents_count?: number;
  team_members_count?: number;
  funding_rounds_count?: number;
  total_funding?: number;
}

export default function CompanyComparePage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const [selectedCompanyIds, setSelectedCompanyIds] = useState<number[]>([]);
  const [availableCompany, setAvailableCompany] = useState<string>('');

  // Parse company IDs from URL
  useEffect(() => {
    const ids = searchParams.get('companies')?.split(',').map(id => parseInt(id)).filter(id => !isNaN(id)) || [];
    setSelectedCompanyIds(ids);
  }, [searchParams]);

  // Fetch all companies for selection
  const { data: companiesData } = useQuery({
    queryKey: ['allCompanies'],
    queryFn: async () => {
      const { data } = await api.get('/companies?per_page=100');
      return data;
    },
  });

  const allCompanies: Company[] = companiesData?.data || [];

  // Fetch selected companies for comparison
  const { data: compareData, isLoading } = useQuery({
    queryKey: ['compareCompanies', selectedCompanyIds],
    queryFn: async () => {
      if (selectedCompanyIds.length === 0) return { companies: [] };

      const promises = selectedCompanyIds.map(id =>
        api.get(`/companies/${id}`).then(res => res.data.company)
      );

      const companies = await Promise.all(promises);
      return { companies };
    },
    enabled: selectedCompanyIds.length > 0,
  });

  const companies: Company[] = compareData?.companies || [];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      notation: 'compact',
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const handleAddCompany = () => {
    if (availableCompany && !selectedCompanyIds.includes(parseInt(availableCompany))) {
      const newIds = [...selectedCompanyIds, parseInt(availableCompany)];
      updateUrl(newIds);
      setAvailableCompany('');
    }
  };

  const handleRemoveCompany = (companyId: number) => {
    const newIds = selectedCompanyIds.filter(id => id !== companyId);
    updateUrl(newIds);
  };

  const updateUrl = (ids: number[]) => {
    if (ids.length > 0) {
      router.push(`/companies/compare?companies=${ids.join(',')}`);
    } else {
      router.push('/companies/compare');
    }
  };

  const ComparisonRow = ({ label, icon: Icon, values }: { label: string; icon: any; values: (string | number | null | undefined)[] }) => (
    <div className="grid grid-cols-[200px_1fr] border-b">
      <div className="p-4 bg-gray-50 border-r flex items-center gap-2 font-medium">
        <Icon className="w-4 h-4 text-muted-foreground" />
        {label}
      </div>
      <div className={`grid grid-cols-${Math.min(companies.length, 4)} divide-x`}>
        {values.map((value, index) => (
          <div key={index} className="p-4 flex items-center justify-center text-center">
            {value !== null && value !== undefined ? (
              typeof value === 'boolean' ? (
                value ? (
                  <CheckCircle2 className="w-5 h-5 text-green-500" />
                ) : (
                  <XCircle className="w-5 h-5 text-red-500" />
                )
              ) : (
                <span>{value}</span>
              )
            ) : (
              <span className="text-muted-foreground">—</span>
            )}
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div className="container py-12">
          <Link href="/companies">
            <Button variant="ghost" className="text-white hover:bg-white/10 mb-4">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back to Companies
            </Button>
          </Link>
          <h1 className="text-5xl font-bold mb-4">Compare Companies</h1>
          <p className="text-xl text-white/90">
            Compare multiple pre-IPO companies side by side to make informed investment decisions
          </p>
        </div>
      </div>

      <div className="container py-8">
        {/* Company Selection */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Select Companies to Compare</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex gap-3">
              <Select value={availableCompany} onValueChange={setAvailableCompany}>
                <SelectTrigger className="flex-1">
                  <SelectValue placeholder="Choose a company" />
                </SelectTrigger>
                <SelectContent>
                  {allCompanies
                    .filter(c => !selectedCompanyIds.includes(c.id))
                    .map(company => (
                      <SelectItem key={company.id} value={company.id.toString()}>
                        <div className="flex items-center gap-2">
                          {company.logo && (
                            <Image
                              src={company.logo}
                              alt={company.name}
                              width={20}
                              height={20}
                              className="rounded"
                            />
                          )}
                          {company.name}
                        </div>
                      </SelectItem>
                    ))}
                </SelectContent>
              </Select>
              <Button
                onClick={handleAddCompany}
                disabled={!availableCompany || selectedCompanyIds.length >= 4}
              >
                <Plus className="w-4 h-4 mr-2" />
                Add Company
              </Button>
            </div>
            {selectedCompanyIds.length >= 4 && (
              <p className="text-sm text-muted-foreground mt-2">
                Maximum 4 companies can be compared at once
              </p>
            )}
          </CardContent>
        </Card>

        {/* Selected Companies Pills */}
        {companies.length > 0 && (
          <div className="flex flex-wrap gap-2 mb-6">
            {companies.map(company => (
              <Badge key={company.id} variant="secondary" className="px-3 py-2 text-sm">
                {company.name}
                <button
                  onClick={() => handleRemoveCompany(company.id)}
                  className="ml-2 hover:text-red-500"
                >
                  <X className="w-3 h-3" />
                </button>
              </Badge>
            ))}
          </div>
        )}

        {/* Comparison Table */}
        {isLoading ? (
          <Card>
            <CardContent className="py-12 text-center">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
              <p className="text-muted-foreground">Loading comparison...</p>
            </CardContent>
          </Card>
        ) : companies.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-2xl font-semibold mb-2">No Companies Selected</h3>
              <p className="text-muted-foreground mb-6">
                Select at least 2 companies to start comparing them
              </p>
            </CardContent>
          </Card>
        ) : companies.length === 1 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-2xl font-semibold mb-2">Add More Companies</h3>
              <p className="text-muted-foreground mb-6">
                Select at least one more company to start comparing
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-6">
            {/* Company Headers */}
            <Card>
              <CardContent className="p-0">
                <div className="grid grid-cols-[200px_1fr]">
                  <div className="p-4 bg-gray-50 border-r border-b"></div>
                  <div className={`grid grid-cols-${Math.min(companies.length, 4)} divide-x border-b`}>
                    {companies.map(company => (
                      <div key={company.id} className="p-6 text-center">
                        {company.logo ? (
                          <div className="mb-4 flex justify-center">
                            <Image
                              src={company.logo}
                              alt={company.name}
                              width={80}
                              height={80}
                              className="object-contain"
                            />
                          </div>
                        ) : (
                          <div className="mb-4 flex justify-center">
                            <div className="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                              <Building2 className="w-10 h-10 text-gray-400" />
                            </div>
                          </div>
                        )}
                        <h3 className="font-bold text-lg mb-2">{company.name}</h3>
                        {company.is_verified && (
                          <Badge variant="secondary" className="bg-green-100 text-green-800">
                            Verified
                          </Badge>
                        )}
                        <Link href={`/companies/${company.slug}`}>
                          <Button variant="link" size="sm" className="mt-2">
                            View Full Profile
                          </Button>
                        </Link>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Basic Information */}
                <div className="border-t-2 border-primary/20">
                  <div className="p-3 bg-primary/5 font-semibold">Basic Information</div>
                  <ComparisonRow
                    label="Sector"
                    icon={Building2}
                    values={companies.map(c => c.sector)}
                  />
                  <ComparisonRow
                    label="Location"
                    icon={MapPin}
                    values={companies.map(c => c.city && c.state ? `${c.city}, ${c.state}` : null)}
                  />
                  <ComparisonRow
                    label="Founded Year"
                    icon={Calendar}
                    values={companies.map(c => c.founded_year)}
                  />
                  <ComparisonRow
                    label="Employees"
                    icon={Users}
                    values={companies.map(c => c.employees_count)}
                  />
                  <ComparisonRow
                    label="Verified"
                    icon={CheckCircle2}
                    values={companies.map(c => c.is_verified)}
                  />
                </div>

                {/* Financials */}
                <div className="border-t-2 border-primary/20">
                  <div className="p-3 bg-primary/5 font-semibold">Financial Metrics</div>
                  <ComparisonRow
                    label="Latest Valuation"
                    icon={DollarSign}
                    values={companies.map(c => c.latest_valuation ? formatCurrency(c.latest_valuation) : null)}
                  />
                  <ComparisonRow
                    label="Total Funding"
                    icon={TrendingUp}
                    values={companies.map(c => c.total_funding ? formatCurrency(c.total_funding) : null)}
                  />
                  <ComparisonRow
                    label="Funding Rounds"
                    icon={TrendingUp}
                    values={companies.map(c => c.funding_rounds_count)}
                  />
                </div>

                {/* Data Availability */}
                <div className="border-t-2 border-primary/20">
                  <div className="p-3 bg-primary/5 font-semibold">Available Information</div>
                  <ComparisonRow
                    label="Financial Reports"
                    icon={FileText}
                    values={companies.map(c => c.financial_reports_count || 0)}
                  />
                  <ComparisonRow
                    label="Documents"
                    icon={FileText}
                    values={companies.map(c => c.documents_count || 0)}
                  />
                  <ComparisonRow
                    label="Team Members"
                    icon={Users}
                    values={companies.map(c => c.team_members_count || 0)}
                  />
                </div>
              </CardContent>
            </Card>

            {/* Description Comparison */}
            <Card>
              <CardHeader>
                <CardTitle>Company Descriptions</CardTitle>
              </CardHeader>
              <CardContent>
                <div className={`grid grid-cols-1 md:grid-cols-${Math.min(companies.length, 2)} gap-6`}>
                  {companies.map(company => (
                    <div key={company.id} className="border rounded-lg p-4">
                      <h4 className="font-semibold mb-2">{company.name}</h4>
                      <p className="text-sm text-muted-foreground">
                        {company.description || 'No description available'}
                      </p>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </div>
    </div>
  );
}
