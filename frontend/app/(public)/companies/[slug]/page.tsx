'use client';

import { notFound, useParams } from "next/navigation";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Building2,
  MapPin,
  Globe,
  Mail,
  Phone,
  Calendar,
  Users,
  TrendingUp,
  FileText,
  Download,
  ExternalLink
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";

interface Company {
  id: number;
  name: string;
  slug: string;
  logo?: string;
  description: string;
  sector: string;
  founded_year?: number;
  website?: string;
  email?: string;
  phone?: string;
  address?: string;
  city?: string;
  state?: string;
  country?: string;
  latest_valuation?: number;
  employees_count?: number;
  is_verified: boolean;
  status: string;
  financial_reports: any[];
  documents: any[];
  team_members: any[];
  funding_rounds: any[];
  updates: any[];
}

export default function PublicCompanyProfile() {
  const params = useParams();
  const slug = params.slug as string;
  const [showInterestModal, setShowInterestModal] = useState(false);

  const { data: response, isLoading, error } = useQuery({
    queryKey: ['publicCompany', slug],
    queryFn: async () => {
      const { data } = await api.get(`/companies/${slug}`);
      return data;
    },
    enabled: !!slug,
    retry: false,
  });

  if (isLoading) {
    return (
      <div className="container py-20">
        <div className="animate-pulse space-y-4">
          <div className="h-48 bg-gray-200 rounded-lg"></div>
          <div className="h-8 bg-gray-200 rounded w-1/3"></div>
          <div className="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
      </div>
    );
  }

  if (error || !response?.success) {
    return notFound();
  }

  const company: Company = response.company;
  const deals = response.deals || [];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div className="container py-12">
          <div className="flex items-start gap-6">
            {company.logo && (
              <div className="bg-white p-4 rounded-lg shadow-lg">
                <Image
                  src={company.logo}
                  alt={company.name}
                  width={120}
                  height={120}
                  className="object-contain"
                />
              </div>
            )}
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <h1 className="text-4xl font-bold">{company.name}</h1>
                {company.is_verified && (
                  <Badge variant="secondary" className="bg-green-500 text-white">
                    Verified
                  </Badge>
                )}
              </div>
              <div className="flex items-center gap-4 text-white/90 mb-4">
                <span className="flex items-center gap-1">
                  <Building2 className="w-4 h-4" />
                  {company.sector}
                </span>
                {company.city && (
                  <span className="flex items-center gap-1">
                    <MapPin className="w-4 h-4" />
                    {company.city}, {company.state}
                  </span>
                )}
                {company.founded_year && (
                  <span className="flex items-center gap-1">
                    <Calendar className="w-4 h-4" />
                    Founded {company.founded_year}
                  </span>
                )}
              </div>
              <p className="text-white/90 text-lg mb-4 max-w-3xl">
                {company.description?.substring(0, 200)}
                {company.description?.length > 200 && '...'}
              </p>
              <div className="flex gap-3">
                <Button size="lg" className="bg-white text-blue-600 hover:bg-gray-100">
                  Express Interest
                </Button>
                {company.website && (
                  <Button variant="outline" size="lg" className="border-white text-white hover:bg-white/10">
                    <ExternalLink className="w-4 h-4 mr-2" />
                    Visit Website
                  </Button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="container py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Sidebar */}
          <div className="space-y-6">
            {/* Key Metrics */}
            <Card>
              <CardHeader>
                <CardTitle>Key Metrics</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {company.latest_valuation && (
                  <div>
                    <p className="text-sm text-muted-foreground">Valuation</p>
                    <p className="text-2xl font-bold text-blue-600">
                      {formatCurrency(company.latest_valuation)}
                    </p>
                  </div>
                )}
                {company.employees_count && (
                  <div>
                    <p className="text-sm text-muted-foreground">Employees</p>
                    <p className="text-xl font-semibold flex items-center gap-2">
                      <Users className="w-5 h-5" />
                      {company.employees_count}
                    </p>
                  </div>
                )}
                {company.funding_rounds?.length > 0 && (
                  <div>
                    <p className="text-sm text-muted-foreground">Funding Rounds</p>
                    <p className="text-xl font-semibold">{company.funding_rounds.length}</p>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Contact Information */}
            <Card>
              <CardHeader>
                <CardTitle>Contact Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {company.email && (
                  <a href={`mailto:${company.email}`} className="flex items-center gap-2 text-blue-600 hover:underline">
                    <Mail className="w-4 h-4" />
                    {company.email}
                  </a>
                )}
                {company.phone && (
                  <a href={`tel:${company.phone}`} className="flex items-center gap-2 text-blue-600 hover:underline">
                    <Phone className="w-4 h-4" />
                    {company.phone}
                  </a>
                )}
                {company.website && (
                  <a href={company.website} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 text-blue-600 hover:underline">
                    <Globe className="w-4 h-4" />
                    Website
                  </a>
                )}
                {company.address && (
                  <p className="flex items-start gap-2 text-sm text-muted-foreground">
                    <MapPin className="w-4 h-4 mt-0.5" />
                    {company.address}
                  </p>
                )}
              </CardContent>
            </Card>

            {/* Active Deals */}
            {deals.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle>Active Offerings</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  {deals.map((deal: any) => (
                    <div key={deal.id} className="border-l-4 border-green-500 pl-3">
                      <p className="font-semibold">{deal.deal_type}</p>
                      <p className="text-sm text-muted-foreground">
                        {formatCurrency(deal.price_per_share)} per share
                      </p>
                      <Link href={`/products/${deal.slug}`}>
                        <Button variant="link" size="sm" className="p-0 h-auto">
                          View Details →
                        </Button>
                      </Link>
                    </div>
                  ))}
                </CardContent>
              </Card>
            )}
          </div>

          {/* Main Content Area */}
          <div className="lg:col-span-2">
            <Tabs defaultValue="overview" className="space-y-6">
              <TabsList className="grid grid-cols-5 w-full">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="team">Team</TabsTrigger>
                <TabsTrigger value="financials">Financials</TabsTrigger>
                <TabsTrigger value="documents">Documents</TabsTrigger>
                <TabsTrigger value="updates">Updates</TabsTrigger>
              </TabsList>

              <TabsContent value="overview" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>About {company.name}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <p className="text-muted-foreground whitespace-pre-wrap">
                      {company.description}
                    </p>
                  </CardContent>
                </Card>

                {company.funding_rounds?.length > 0 && (
                  <Card>
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <TrendingUp className="w-5 h-5" />
                        Funding History
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-4">
                        {company.funding_rounds.map((round: any) => (
                          <div key={round.id} className="border-l-4 border-blue-500 pl-4">
                            <div className="flex justify-between items-start mb-1">
                              <p className="font-semibold">{round.round_name}</p>
                              <Badge>{formatDate(round.date)}</Badge>
                            </div>
                            {round.amount_raised && (
                              <p className="text-lg font-bold text-blue-600">
                                {formatCurrency(round.amount_raised)}
                              </p>
                            )}
                            {round.lead_investors && (
                              <p className="text-sm text-muted-foreground">
                                Led by: {round.lead_investors}
                              </p>
                            )}
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}
              </TabsContent>

              <TabsContent value="team">
                <Card>
                  <CardHeader>
                    <CardTitle>Leadership Team</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {company.team_members?.length > 0 ? (
                      <div className="grid gap-6 md:grid-cols-2">
                        {company.team_members.map((member: any) => (
                          <div key={member.id} className="flex gap-4">
                            {member.photo && (
                              <Image
                                src={member.photo}
                                alt={member.name}
                                width={80}
                                height={80}
                                className="rounded-full object-cover"
                              />
                            )}
                            <div>
                              <p className="font-semibold text-lg">{member.name}</p>
                              <p className="text-blue-600">{member.designation}</p>
                              {member.bio && (
                                <p className="text-sm text-muted-foreground mt-2">
                                  {member.bio}
                                </p>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-muted-foreground">No team members information available.</p>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="financials">
                <Card>
                  <CardHeader>
                    <CardTitle>Financial Reports</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {company.financial_reports?.length > 0 ? (
                      <div className="space-y-3">
                        {company.financial_reports.map((report: any) => (
                          <div key={report.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                            <div className="flex items-center gap-3">
                              <FileText className="w-10 h-10 text-blue-600" />
                              <div>
                                <p className="font-semibold">{report.report_type} - {report.year}</p>
                                {report.quarter && (
                                  <p className="text-sm text-muted-foreground">Quarter {report.quarter}</p>
                                )}
                              </div>
                            </div>
                            <Button variant="outline" size="sm">
                              <Download className="w-4 h-4 mr-2" />
                              Download
                            </Button>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-muted-foreground">No financial reports available.</p>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="documents">
                <Card>
                  <CardHeader>
                    <CardTitle>Company Documents</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {company.documents?.length > 0 ? (
                      <div className="space-y-3">
                        {company.documents.map((doc: any) => (
                          <div key={doc.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                            <div className="flex items-center gap-3">
                              <FileText className="w-10 h-10 text-green-600" />
                              <div>
                                <p className="font-semibold">{doc.title}</p>
                                {doc.description && (
                                  <p className="text-sm text-muted-foreground">{doc.description}</p>
                                )}
                              </div>
                            </div>
                            <Button variant="outline" size="sm">
                              <Download className="w-4 h-4 mr-2" />
                              Download
                            </Button>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <p className="text-muted-foreground">No documents available.</p>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="updates">
                <div className="space-y-4">
                  {company.updates?.length > 0 ? (
                    company.updates.map((update: any) => (
                      <Card key={update.id}>
                        <CardHeader>
                          <div className="flex justify-between items-start">
                            <CardTitle className="text-xl">{update.title}</CardTitle>
                            <Badge variant="outline">{formatDate(update.published_at)}</Badge>
                          </div>
                        </CardHeader>
                        <CardContent>
                          <p className="text-muted-foreground whitespace-pre-wrap">
                            {update.content}
                          </p>
                        </CardContent>
                      </Card>
                    ))
                  ) : (
                    <Card>
                      <CardContent className="py-8 text-center">
                        <p className="text-muted-foreground">No updates available.</p>
                      </CardContent>
                    </Card>
                  )}
                </div>
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>
    </div>
  );
}
