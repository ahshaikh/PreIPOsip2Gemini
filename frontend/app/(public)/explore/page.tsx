'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import {
  Search, TrendingUp, Building2, Star, Calendar, Users,
  Filter, ArrowUpRight, DollarSign, BarChart3, Award
} from "lucide-react";

const preIPOListings = [
  {
    id: 1,
    company: "TechCorp India",
    sector: "Technology",
    minInvestment: "₹10,000",
    expectedReturn: "35-45%",
    listingDate: "Q2 2025",
    valuation: "₹5,000 Cr",
    status: "Open",
    rating: 4.5,
    subscribers: 2847,
    description: "Leading AI and cloud services provider in India",
  },
  {
    id: 2,
    company: "FinNext Solutions",
    sector: "Fintech",
    minInvestment: "₹15,000",
    expectedReturn: "40-50%",
    listingDate: "Q3 2025",
    valuation: "₹3,200 Cr",
    status: "Open",
    rating: 4.7,
    subscribers: 3521,
    description: "Digital banking and payment solutions platform",
  },
  {
    id: 3,
    company: "GreenEnergy Co",
    sector: "Renewable Energy",
    minInvestment: "₹20,000",
    expectedReturn: "30-40%",
    listingDate: "Q4 2025",
    valuation: "₹7,500 Cr",
    status: "Open",
    rating: 4.3,
    subscribers: 1932,
    description: "Solar and wind energy infrastructure developer",
  },
  {
    id: 4,
    company: "HealthTech Plus",
    sector: "Healthcare",
    minInvestment: "₹12,000",
    expectedReturn: "38-48%",
    listingDate: "Q1 2026",
    valuation: "₹4,100 Cr",
    status: "Coming Soon",
    rating: 4.6,
    subscribers: 2156,
    description: "Telemedicine and healthcare technology solutions",
  },
  {
    id: 5,
    company: "EduVerse",
    sector: "EdTech",
    minInvestment: "₹8,000",
    expectedReturn: "32-42%",
    listingDate: "Q2 2025",
    valuation: "₹2,800 Cr",
    status: "Open",
    rating: 4.4,
    subscribers: 2689,
    description: "Online education and skill development platform",
  },
  {
    id: 6,
    company: "LogiChain Pro",
    sector: "Logistics",
    minInvestment: "₹25,000",
    expectedReturn: "28-38%",
    listingDate: "Q3 2025",
    valuation: "₹6,200 Cr",
    status: "Open",
    rating: 4.2,
    subscribers: 1543,
    description: "Supply chain and logistics management solutions",
  },
];

const sectors = ["All", "Technology", "Fintech", "Healthcare", "EdTech", "Renewable Energy", "Logistics"];
const statusFilters = ["All", "Open", "Coming Soon", "Closed"];

export default function ExploreListingsPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedSector, setSelectedSector] = useState('All');
  const [selectedStatus, setSelectedStatus] = useState('All');

  const filteredListings = preIPOListings.filter(listing => {
    const matchesSearch = listing.company.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         listing.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesSector = selectedSector === 'All' || listing.sector === selectedSector;
    const matchesStatus = selectedStatus === 'All' || listing.status === selectedStatus;
    return matchesSearch && matchesSector && matchesStatus;
  });

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-16">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center mb-8">
            <Badge variant="secondary" className="mb-4">
              <TrendingUp className="h-3 w-3 mr-1" />
              Live Pre-IPO Opportunities
            </Badge>
            <h1 className="text-4xl md:text-5xl font-bold mb-4">Explore Pre-IPO Listings</h1>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
              Discover handpicked pre-IPO companies across sectors. Start investing with as low as ₹5,000.
            </p>
          </div>

          {/* Search and Filter */}
          <div className="flex flex-col md:flex-row gap-4 items-center justify-center">
            <div className="relative w-full md:w-96">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search companies..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 py-12">
        {/* Filters */}
        <div className="mb-8">
          <div className="flex items-center gap-2 mb-4">
            <Filter className="h-4 w-4" />
            <h3 className="font-semibold">Filters</h3>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            <div>
              <label className="text-sm font-medium mb-2 block">Sector</label>
              <div className="flex flex-wrap gap-2">
                {sectors.map((sector) => (
                  <Button
                    key={sector}
                    variant={selectedSector === sector ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedSector(sector)}
                  >
                    {sector}
                  </Button>
                ))}
              </div>
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Status</label>
              <div className="flex flex-wrap gap-2">
                {statusFilters.map((status) => (
                  <Button
                    key={status}
                    variant={selectedStatus === status ? "default" : "outline"}
                    size="sm"
                    onClick={() => setSelectedStatus(status)}
                  >
                    {status}
                  </Button>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid md:grid-cols-4 gap-4 mb-8">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Building2 className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Total Listings</p>
                  <p className="text-2xl font-bold">{preIPOListings.length}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-green-500/10 rounded-lg">
                  <TrendingUp className="h-5 w-5 text-green-500" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Open Now</p>
                  <p className="text-2xl font-bold">
                    {preIPOListings.filter(l => l.status === 'Open').length}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-blue-500/10 rounded-lg">
                  <Users className="h-5 w-5 text-blue-500" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Total Investors</p>
                  <p className="text-2xl font-bold">15K+</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-orange-500/10 rounded-lg">
                  <Award className="h-5 w-5 text-orange-500" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Avg. Return</p>
                  <p className="text-2xl font-bold">35%</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Listings Grid */}
        <div className="mb-6">
          <h2 className="text-2xl font-bold mb-2">
            {filteredListings.length} {filteredListings.length === 1 ? 'Opportunity' : 'Opportunities'} Available
          </h2>
          <p className="text-muted-foreground">
            {selectedSector !== 'All' && `in ${selectedSector} sector`}
            {selectedStatus !== 'All' && ` • ${selectedStatus}`}
          </p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredListings.map((listing) => (
            <Card key={listing.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-start justify-between mb-2">
                  <Badge variant={listing.status === 'Open' ? 'default' : 'secondary'}>
                    {listing.status}
                  </Badge>
                  <div className="flex items-center gap-1">
                    <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                    <span className="text-sm font-medium">{listing.rating}</span>
                  </div>
                </div>
                <CardTitle className="text-xl">{listing.company}</CardTitle>
                <CardDescription>{listing.description}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3 mb-4">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Sector</span>
                    <Badge variant="outline">{listing.sector}</Badge>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Min. Investment</span>
                    <span className="font-semibold">{listing.minInvestment}</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Expected Return</span>
                    <span className="font-semibold text-green-600">{listing.expectedReturn}</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Listing Date</span>
                    <div className="flex items-center gap-1">
                      <Calendar className="h-3 w-3" />
                      <span>{listing.listingDate}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Valuation</span>
                    <span className="font-semibold">{listing.valuation}</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Investors</span>
                    <div className="flex items-center gap-1">
                      <Users className="h-3 w-3" />
                      <span>{listing.subscribers.toLocaleString()}</span>
                    </div>
                  </div>
                </div>

                <div className="flex gap-2">
                  <Button className="flex-1" disabled={listing.status !== 'Open'}>
                    {listing.status === 'Open' ? 'Invest Now' : 'Coming Soon'}
                    <ArrowUpRight className="h-4 w-4 ml-1" />
                  </Button>
                  <Button variant="outline">
                    <BarChart3 className="h-4 w-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {filteredListings.length === 0 && (
          <Card className="py-12">
            <CardContent className="text-center">
              <Building2 className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
              <h3 className="text-xl font-semibold mb-2">No listings found</h3>
              <p className="text-muted-foreground mb-4">
                Try adjusting your filters or search terms
              </p>
              <Button
                variant="outline"
                onClick={() => {
                  setSearchTerm('');
                  setSelectedSector('All');
                  setSelectedStatus('All');
                }}
              >
                Clear Filters
              </Button>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}
