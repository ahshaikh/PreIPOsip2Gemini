'use client';

import { useQuery } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { FileText, FolderOpen, Users, TrendingUp, Newspaper, CheckCircle, AlertCircle, Building2 } from 'lucide-react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';
import Image from 'next/image';

export default function CompanyDashboardPage() {
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['company-dashboard'],
    queryFn: async () => {
      const response = await companyApi.get('/company-profile/dashboard');
      return response.data;
    },
  });

  const stats = dashboardData?.stats;
  const company = dashboardData?.company;

  const statsCards = [
    {
      title: 'Financial Reports',
      value: stats?.financial_reports_count || 0,
      icon: FileText,
      href: '/company/financial-reports',
      color: 'text-blue-600',
    },
    {
      title: 'Documents',
      value: stats?.documents_count || 0,
      icon: FolderOpen,
      href: '/company/documents',
      color: 'text-green-600',
    },
    {
      title: 'Team Members',
      value: stats?.team_members_count || 0,
      icon: Users,
      href: '/company/team',
      color: 'text-purple-600',
    },
    {
      title: 'Funding Rounds',
      value: stats?.funding_rounds_count || 0,
      icon: TrendingUp,
      href: '/company/funding',
      color: 'text-orange-600',
    },
    {
      title: 'Updates',
      value: stats?.updates_count || 0,
      icon: Newspaper,
      href: '/company/updates',
      color: 'text-pink-600',
    },
  ];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-lg">Loading dashboard...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Welcome Section with Company Logo */}
      <div className="flex items-center gap-4">
        {/* Company Logo */}
        <div className="flex-shrink-0">
          {company?.logo ? (
            <div className="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <Image
                src={`/api/storage/${company.logo}`}
                alt={company.name || 'Company logo'}
                width={64}
                height={64}
                className="object-contain"
                onError={(e) => {
                  console.error('Failed to load company logo on dashboard');
                  e.currentTarget.style.display = 'none';
                }}
              />
            </div>
          ) : (
            <div className="w-16 h-16 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <Building2 className="h-8 w-8 text-muted-foreground" />
            </div>
          )}
        </div>

        {/* Welcome Text */}
        <div className="flex-1">
          <h1 className="text-3xl font-bold">
            {company?.name ? `Welcome, ${company.name}` : 'Welcome to Your Company Dashboard'}
          </h1>
          <p className="text-muted-foreground mt-2">
            Manage your company profile and engage with potential investors
          </p>
        </div>
      </div>

      {/* Status Alerts */}
      {stats?.status === 'pending' && (
        <Card className="bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-yellow-600 mt-0.5" />
              <div>
                <h3 className="font-semibold text-yellow-900 dark:text-yellow-100">
                  Account Pending Approval
                </h3>
                <p className="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                  Your account is currently under review. You can complete your profile while waiting for approval.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {stats?.is_verified && (
        <Card className="bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <CheckCircle className="h-5 w-5 text-green-600 mt-0.5" />
              <div>
                <h3 className="font-semibold text-green-900 dark:text-green-100">
                  Account Verified
                </h3>
                <p className="text-sm text-green-800 dark:text-green-200 mt-1">
                  Your company account has been verified and is active on our platform.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Profile Completion */}
      <Card>
        <CardHeader>
          <CardTitle>Profile Completion</CardTitle>
          <CardDescription>
            Complete your profile to increase visibility to investors
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium">
                {stats?.profile_completion || 0}% Complete
              </span>
              <Link href="/company/profile">
                <Button variant="outline" size="sm">
                  Complete Profile
                </Button>
              </Link>
            </div>
            <Progress value={stats?.profile_completion || 0} className="h-2" />
          </div>
          {stats?.profile_completion < 80 && (
            <p className="text-sm text-muted-foreground">
              Add more information to make your profile stand out to investors.
            </p>
          )}
        </CardContent>
      </Card>

      {/* Statistics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        {statsCards.map((stat) => {
          const Icon = stat.icon;
          return (
            <Link key={stat.title} href={stat.href}>
              <Card className="hover:shadow-md transition-shadow cursor-pointer">
                <CardContent className="pt-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">
                        {stat.title}
                      </p>
                      <p className="text-2xl font-bold mt-2">{stat.value}</p>
                    </div>
                    <Icon className={`h-8 w-8 ${stat.color}`} />
                  </div>
                </CardContent>
              </Card>
            </Link>
          );
        })}
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>Common tasks to manage your company profile</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link href="/company/profile">
              <Button variant="outline" className="w-full justify-start">
                <FileText className="mr-2 h-4 w-4" />
                Update Profile
              </Button>
            </Link>
            <Link href="/company/financial-reports">
              <Button variant="outline" className="w-full justify-start">
                <FileText className="mr-2 h-4 w-4" />
                Upload Financial Report
              </Button>
            </Link>
            <Link href="/company/team">
              <Button variant="outline" className="w-full justify-start">
                <Users className="mr-2 h-4 w-4" />
                Add Team Member
              </Button>
            </Link>
            <Link href="/company/updates">
              <Button variant="outline" className="w-full justify-start">
                <Newspaper className="mr-2 h-4 w-4" />
                Post Update
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>

      {/* Company Info Summary */}
      {company && (
        <Card>
          <CardHeader>
            <CardTitle>Company Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <p className="text-sm text-muted-foreground">Company Name</p>
                <p className="font-medium">{company.name}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Sector</p>
                <p className="font-medium">{company.sector}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Founded</p>
                <p className="font-medium">{company.founded_year || 'Not specified'}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Headquarters</p>
                <p className="font-medium">{company.headquarters || 'Not specified'}</p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Website</p>
                <p className="font-medium">
                  {company.website ? (
                    <a href={company.website} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                      Visit Website
                    </a>
                  ) : (
                    'Not specified'
                  )}
                </p>
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Status</p>
                <Badge variant={company.status === 'active' ? 'default' : 'secondary'}>
                  {company.status}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
