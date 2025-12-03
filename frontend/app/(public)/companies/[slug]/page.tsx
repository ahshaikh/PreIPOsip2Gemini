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
  ExternalLink,
  BarChart3,
  Youtube
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

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
  isin?: string;
  pan?: string;
  tan?: string;
  gst?: string;
  financial_reports: any[];
  documents: any[];
  team_members: any[];
  funding_rounds: any[];
  updates: any[];
  youtube_videos?: string[];
}

// Mock data for Key Indicators (in real app, this would come from API)
const keyIndicators = {
  faceValue: 10.0,
  bookValue: 7210.0,
  priceToEarning: 18.9,
  priceToSales: 1.2,
  priceToBook: 3.6,
  outstandingShares: 0.5,
  marketCap: 13125.0,
  debtToEquity: 0.08,
  dividendPerShare: 200.0,
  dividendPercent: 0.8,
  returnOnAssets: 9.5,
  returnOnEquity: 12.2,
  rowc: null,
};

// Mock data for Shareholders
const shareholders = [
  { name: 'Ajit Thomas', percentage: 49.4 },
  { name: 'Dilip thomas', percentage: 34.2 },
  { name: 'The Highland Produce Co. Ltd', percentage: 0.8 },
  { name: 'The Rajagiri Rubber and Produce Co. Ltd', percentage: 0.5 },
  { name: 'Priyalatha Thomas', percentage: 0.1 },
  { name: 'Ashwin Thomas', percentage: 0.1 },
  { name: 'Divesh Thomas', percentage: 0.1 },
  { name: 'All Others', percentage: 14.8 },
];

// Mock chart data (in real app, this would come from API)
const revenueGrowthData = [
  { year: '2020', value: 12.5 },
  { year: '2021', value: 18.3 },
  { year: '2022', value: 24.7 },
  { year: '2023', value: 31.2 },
  { year: '2024', value: 35.8 },
];

const ebitdaMarginData = [
  { year: '2020', value: 15.2 },
  { year: '2021', value: 17.8 },
  { year: '2022', value: 19.5 },
  { year: '2023', value: 22.1 },
  { year: '2024', value: 24.6 },
];

const epsGrowthData = [
  { year: '2020', value: 8.3 },
  { year: '2021', value: 12.7 },
  { year: '2022', value: 16.4 },
  { year: '2023', value: 21.8 },
  { year: '2024', value: 28.5 },
];

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
          {/* Left Sidebar */}
          <div className="space-y-6">
            {/* Key Indicators */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg font-bold">KEY INDICATORS</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">FACE VALUE/SHARE</span>
                    <span className="font-semibold">{keyIndicators.faceValue}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">BOOK VALUE/SHARE</span>
                    <span className="font-semibold">{keyIndicators.bookValue.toFixed(1)}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">PRICE TO EARNING (PE)</span>
                    <span className="font-semibold">{keyIndicators.priceToEarning}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">PRICE/SALES</span>
                    <span className="font-semibold">{keyIndicators.priceToSales}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">PRICE/BOOK</span>
                    <span className="font-semibold">{keyIndicators.priceToBook}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">OUTSTANDING SHARES (Million)</span>
                    <span className="font-semibold">{keyIndicators.outstandingShares}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">MARKET CAP (Rs. Million)</span>
                    <span className="font-semibold">{keyIndicators.marketCap.toFixed(1)}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">DEBT/EQUITY</span>
                    <span className="font-semibold">{keyIndicators.debtToEquity}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">DIVIDEND/SHARE</span>
                    <span className="font-semibold">{keyIndicators.dividendPerShare.toFixed(1)}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">DIVIDEND % (ON CMP)</span>
                    <span className="font-semibold">{keyIndicators.dividendPercent}%</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">RETURN ON TOTAL ASSETS</span>
                    <span className="font-semibold">{keyIndicators.returnOnAssets}%</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">RETURN ON EQUITY</span>
                    <span className="font-semibold">{keyIndicators.returnOnEquity}%</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b">
                    <span className="text-sm font-medium text-gray-600">ROWC</span>
                    <span className="font-semibold">{keyIndicators.rowc || '-'}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
            <p className="text-xs text-gray-500 px-2">* Ratio is calculated based on latest financial & current share price.</p>

            {/* Contact Information - Enhanced */}
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
                <div className="pt-4 mt-4 border-t space-y-2">
                  {company.isin && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-gray-600">ISIN:</span>
                      <span className="text-sm font-mono">{company.isin || 'INE944K01010'}</span>
                    </div>
                  )}
                  {company.pan && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-gray-600">PAN:</span>
                      <span className="text-sm font-mono">{company.pan || 'AABCA8810G'}</span>
                    </div>
                  )}
                  {company.tan && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-gray-600">TAN:</span>
                      <span className="text-sm font-mono">{company.tan || 'ABCDE2589G'}</span>
                    </div>
                  )}
                  {company.gst && (
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-gray-600">GST:</span>
                      <span className="text-sm font-mono">{company.gst || 'ABCABCDE2589G1YZ'}</span>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Shareholders */}
            <Card>
              <CardHeader>
                <CardTitle>Shareholders</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  {shareholders.map((shareholder, index) => (
                    <div key={index} className="flex justify-between items-center py-2 border-b last:border-0">
                      <span className="text-sm text-gray-700">{shareholder.name}</span>
                      <span className="font-semibold text-blue-600">{shareholder.percentage}%</span>
                    </div>
                  ))}
                </div>
                <p className="mt-4 text-sm text-red-600 font-medium">Shareholders Report: Not Filed</p>
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
            {/* Financial Charts - 3 Panel Dashboard */}
            <div className="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Revenue Growth Chart */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-semibold flex items-center gap-2">
                    <BarChart3 className="w-4 h-4" />
                    Revenue Growth %
                  </CardTitle>
                </CardHeader>
                <CardContent className="pt-2">
                  <ResponsiveContainer width="100%" height={180}>
                    <BarChart data={revenueGrowthData}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="year" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="value" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* EBITDA Margin Chart */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-semibold flex items-center gap-2">
                    <BarChart3 className="w-4 h-4" />
                    EBITDA Margin %
                  </CardTitle>
                </CardHeader>
                <CardContent className="pt-2">
                  <ResponsiveContainer width="100%" height={180}>
                    <BarChart data={ebitdaMarginData}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="year" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="value" fill="#10b981" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>

              {/* EPS Growth Chart */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-semibold flex items-center gap-2">
                    <BarChart3 className="w-4 h-4" />
                    EPS Growth %
                  </CardTitle>
                </CardHeader>
                <CardContent className="pt-2">
                  <ResponsiveContainer width="100%" height={180}>
                    <BarChart data={epsGrowthData}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="year" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="value" fill="#8b5cf6" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </CardContent>
              </Card>
            </div>

            {/* YouTube Videos Section */}
            {company.youtube_videos && company.youtube_videos.length > 0 && (
              <Card className="mb-6">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Youtube className="w-5 h-5 text-red-600" />
                    Company Videos
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {company.youtube_videos.map((videoUrl, index) => {
                      // Extract video ID from YouTube URL
                      const videoId = videoUrl.split('v=')[1]?.split('&')[0] || videoUrl.split('/').pop();
                      return (
                        <div key={index} className="aspect-video">
                          <iframe
                            width="100%"
                            height="100%"
                            src={`https://www.youtube.com/embed/${videoId}`}
                            title={`Company Video ${index + 1}`}
                            frameBorder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                            className="rounded-lg"
                          />
                        </div>
                      );
                    })}
                  </div>
                </CardContent>
              </Card>
            )}

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
