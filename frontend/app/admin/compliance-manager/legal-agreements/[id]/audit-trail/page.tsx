'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import { useState } from "react";
import {
  ArrowLeft, History, User, Calendar, Clock, FileText, Edit,
  Trash2, Eye, CheckCircle, XCircle, Archive, Upload, Download,
  Shield, AlertTriangle, Filter, Search, RefreshCw, Share2
} from "lucide-react";
import { Separator } from "@/components/ui/separator";

// Audit Event Types
const EVENT_TYPES = [
  { value: 'created', label: 'Created', icon: FileText, color: 'text-blue-500' },
  { value: 'updated', label: 'Updated', icon: Edit, color: 'text-yellow-500' },
  { value: 'deleted', label: 'Deleted', icon: Trash2, color: 'text-red-500' },
  { value: 'published', label: 'Published', icon: CheckCircle, color: 'text-green-500' },
  { value: 'archived', label: 'Archived', icon: Archive, color: 'text-purple-500' },
  { value: 'viewed', label: 'Viewed', icon: Eye, color: 'text-gray-500' },
  { value: 'shared', label: 'Shared', icon: Share2, color: 'text-indigo-500' },
  { value: 'downloaded', label: 'Downloaded', icon: Download, color: 'text-teal-500' },
  { value: 'version_created', label: 'Version Created', icon: History, color: 'text-orange-500' },
  { value: 'status_changed', label: 'Status Changed', icon: RefreshCw, color: 'text-pink-500' },
  { value: 'accepted', label: 'Accepted', icon: CheckCircle, color: 'text-green-600' },
  { value: 'declined', label: 'Declined', icon: XCircle, color: 'text-red-600' },
];

export default function AuditTrailPage() {
  const params = useParams();
  const router = useRouter();
  const agreementId = params.id as string;

  const [searchQuery, setSearchQuery] = useState('');
  const [eventTypeFilter, setEventTypeFilter] = useState('all');
  const [dateRange, setDateRange] = useState('all');

  // Fetch agreement details
  const { data: agreement } = useQuery({
    queryKey: ['legalAgreement', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}`)).data,
  });

  // Fetch audit trail
  const { data: auditTrail, isLoading, refetch } = useQuery({
    queryKey: ['auditTrail', agreementId, eventTypeFilter, dateRange],
    queryFn: async () => {
      let url = `/admin/compliance/legal-agreements/${agreementId}/audit-trail?`;
      if (eventTypeFilter !== 'all') url += `event_type=${eventTypeFilter}&`;
      if (dateRange !== 'all') url += `date_range=${dateRange}`;
      return (await api.get(url)).data;
    },
  });

  // Fetch audit statistics
  const { data: stats } = useQuery({
    queryKey: ['auditTrailStats', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}/audit-trail/stats`)).data,
  });

  const getEventIcon = (eventType: string) => {
    const event = EVENT_TYPES.find(e => e.value === eventType);
    const Icon = event?.icon || AlertTriangle;
    return <Icon className={`h-4 w-4 ${event?.color || 'text-gray-500'}`} />;
  };

  const getEventBadge = (eventType: string) => {
    const event = EVENT_TYPES.find(e => e.value === eventType);
    return (
      <Badge variant="outline" className="flex items-center gap-1">
        {getEventIcon(eventType)}
        <span>{event?.label || eventType}</span>
      </Badge>
    );
  };

  // Filter audit entries based on search
  const filteredAuditTrail = auditTrail?.filter((entry: any) =>
    entry.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    entry.user_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    entry.event_type?.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const exportAuditTrail = async () => {
    try {
      const response = await api.get(
        `/admin/compliance/legal-agreements/${agreementId}/audit-trail/export`,
        { responseType: 'blob' }
      );
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `audit-trail-${agreementId}-${Date.now()}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Audit trail exported successfully");
    } catch (error) {
      toast.error("Failed to export audit trail");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="flex items-start gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.push(`/admin/compliance-manager/legal-agreements/${agreementId}`)}
          >
            <ArrowLeft className="mr-2 h-4 w-4" /> Back to Agreement
          </Button>
          <div>
            <h1 className="text-3xl font-bold">Audit Trail</h1>
            <p className="text-muted-foreground mt-1">
              {agreement?.title || 'Loading...'}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => refetch()}>
            <RefreshCw className="mr-2 h-4 w-4" /> Refresh
          </Button>
          <Button onClick={exportAuditTrail}>
            <Download className="mr-2 h-4 w-4" /> Export
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
            <History className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_events || 0}</div>
            <p className="text-xs text-muted-foreground">all time</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">This Week</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.this_week_events || 0}</div>
            <p className="text-xs text-muted-foreground">events logged</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Unique Users</CardTitle>
            <User className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.unique_users || 0}</div>
            <p className="text-xs text-muted-foreground">interacted</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Last Activity</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-sm font-semibold">
              {stats?.last_activity
                ? new Date(stats.last_activity).toLocaleDateString()
                : 'No activity'}
            </div>
            <p className="text-xs text-muted-foreground">
              {stats?.last_activity
                ? new Date(stats.last_activity).toLocaleTimeString()
                : ''}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search audit trail by description, user, or event..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={eventTypeFilter} onValueChange={setEventTypeFilter}>
              <SelectTrigger className="w-full md:w-[200px]">
                <Filter className="mr-2 h-4 w-4" />
                <SelectValue placeholder="Event Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Events</SelectItem>
                {EVENT_TYPES.map(type => (
                  <SelectItem key={type.value} value={type.value}>
                    {type.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={dateRange} onValueChange={setDateRange}>
              <SelectTrigger className="w-full md:w-[200px]">
                <Calendar className="mr-2 h-4 w-4" />
                <SelectValue placeholder="Date Range" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Time</SelectItem>
                <SelectItem value="today">Today</SelectItem>
                <SelectItem value="week">This Week</SelectItem>
                <SelectItem value="month">This Month</SelectItem>
                <SelectItem value="quarter">This Quarter</SelectItem>
                <SelectItem value="year">This Year</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Audit Trail Timeline */}
      <Card>
        <CardHeader>
          <CardTitle>Activity Timeline</CardTitle>
          <CardDescription>
            Complete audit trail of all actions performed on this legal agreement
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                <p className="mt-4 text-muted-foreground">Loading audit trail...</p>
              </div>
            </div>
          ) : filteredAuditTrail?.length === 0 ? (
            <div className="text-center py-12">
              <History className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
              <p className="text-muted-foreground">No audit trail entries found</p>
            </div>
          ) : (
            <div className="space-y-6">
              {filteredAuditTrail?.map((entry: any, index: number) => (
                <div key={entry.id} className="relative">
                  {/* Timeline Line */}
                  {index !== filteredAuditTrail.length - 1 && (
                    <div className="absolute left-[27px] top-12 bottom-0 w-0.5 bg-border" />
                  )}

                  {/* Timeline Entry */}
                  <div className="flex gap-4">
                    {/* Icon */}
                    <div className="flex-shrink-0 mt-1">
                      <div className="h-10 w-10 rounded-full bg-muted flex items-center justify-center border-2 border-background ring-2 ring-border">
                        {getEventIcon(entry.event_type)}
                      </div>
                    </div>

                    {/* Content */}
                    <div className="flex-1 pb-8">
                      <div className="bg-muted/30 rounded-lg border p-4">
                        <div className="flex items-start justify-between mb-3">
                          <div className="flex items-center gap-3">
                            {getEventBadge(entry.event_type)}
                            <Separator orientation="vertical" className="h-4" />
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                              <User className="h-3 w-3" />
                              <span className="font-medium">{entry.user_name || 'System'}</span>
                            </div>
                          </div>
                          <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            <span>{new Date(entry.created_at).toLocaleDateString()}</span>
                            <Clock className="h-3 w-3 ml-2" />
                            <span>{new Date(entry.created_at).toLocaleTimeString()}</span>
                          </div>
                        </div>

                        <p className="text-sm mb-3">{entry.description}</p>

                        {/* Changes Details */}
                        {entry.changes && Object.keys(entry.changes).length > 0 && (
                          <div className="mt-3 p-3 bg-background rounded border">
                            <p className="text-xs font-medium text-muted-foreground mb-2">
                              Changes Made:
                            </p>
                            <div className="space-y-2">
                              {Object.entries(entry.changes).map(([key, value]: [string, any]) => (
                                <div key={key} className="text-xs">
                                  <span className="font-medium capitalize">
                                    {key.replace(/_/g, ' ')}:
                                  </span>
                                  <div className="mt-1 grid grid-cols-2 gap-2">
                                    <div className="flex items-center gap-1">
                                      <XCircle className="h-3 w-3 text-red-500" />
                                      <span className="text-muted-foreground line-through">
                                        {value.old || 'N/A'}
                                      </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                      <CheckCircle className="h-3 w-3 text-green-500" />
                                      <span className="font-medium">
                                        {value.new || 'N/A'}
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Metadata */}
                        <div className="mt-3 flex items-center gap-4 text-xs text-muted-foreground">
                          {entry.ip_address && (
                            <div className="flex items-center gap-1">
                              <Shield className="h-3 w-3" />
                              <span className="font-mono">{entry.ip_address}</span>
                            </div>
                          )}
                          {entry.user_agent && (
                            <div className="flex items-center gap-1 max-w-md truncate">
                              <span>{entry.user_agent}</span>
                            </div>
                          )}
                          {entry.version && (
                            <div className="flex items-center gap-1">
                              <History className="h-3 w-3" />
                              <span className="font-mono">v{entry.version}</span>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Summary Card */}
      <Card>
        <CardHeader>
          <CardTitle>Event Summary</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-3 gap-4">
            {EVENT_TYPES.filter(type =>
              stats?.event_counts?.[type.value] > 0
            ).map(type => {
              const Icon = type.icon;
              return (
                <div key={type.value} className="flex items-center justify-between p-3 border rounded-lg">
                  <div className="flex items-center gap-3">
                    <Icon className={`h-5 w-5 ${type.color}`} />
                    <span className="text-sm font-medium">{type.label}</span>
                  </div>
                  <Badge variant="secondary">
                    {stats?.event_counts?.[type.value] || 0}
                  </Badge>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
