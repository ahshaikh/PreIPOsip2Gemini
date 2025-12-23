// V-REMEDIATE-1730-151 | V-ENHANCED-SUPPORT | V-PAGINATION-PROTOCOL-7
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  PlusCircle, MessageSquare, Clock, CheckCircle, AlertCircle,
  HelpCircle, Search, FileText, ExternalLink, Phone, Mail,
  MessageCircleQuestion, Headphones, Calendar, ChevronRight, Book, Loader2
} from "lucide-react";
import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import SupportQuickLinks from "@/components/shared/SupportQuickLinks";
import AISuggestionsPanel from "@/components/support/AISuggestionsPanel";
import { PaginationControls } from "@/components/shared/PaginationControls"; // [Protocol 7]

// Ticket categories with icons and descriptions
const TICKET_CATEGORIES = [
  { value: 'general', label: 'General Inquiry', icon: HelpCircle, desc: 'General questions about the platform' },
  { value: 'payment', label: 'Payment Issue', icon: AlertCircle, desc: 'Payment, refunds, or billing problems' },
  { value: 'kyc', label: 'KYC/Verification', icon: FileText, desc: 'Identity verification issues' },
  { value: 'technical', label: 'Technical Problem', icon: MessageSquare, desc: 'App bugs or technical issues' },
  { value: 'subscription', label: 'Subscription', icon: CheckCircle, desc: 'Plan changes or subscription queries' },
  { value: 'withdrawal', label: 'Withdrawal', icon: Clock, desc: 'Withdrawal requests or delays' },
];

// FAQ items for quick help
const FAQ_ITEMS = [
  { q: 'How do I complete KYC verification?', a: 'Go to the KYC page in your dashboard and upload your ID documents. Verification usually takes 24-48 hours.' },
  { q: 'How long do withdrawals take?', a: 'Withdrawals are typically processed within 2-3 business days after approval.' },
  { q: 'How do I change my subscription plan?', a: 'Visit the Subscription page and click on "Manage" to upgrade or downgrade your plan.' },
  { q: 'What is the referral program?', a: 'Share your referral link with friends. When they invest, you both earn bonuses. Check the Referrals page for your unique link.' },
  { q: 'How is my portfolio value calculated?', a: 'Your portfolio value is based on the current NAV of your holdings multiplied by the number of units you own.' },
];

export default function SupportPage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const [activeTab, setActiveTab] = useState('tickets');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  
  // [PROTOCOL 7] State for pagination
  const [page, setPage] = useState(1);

  // State for new ticket
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('general');
  const [priority, setPriority] = useState('medium');
  const [message, setMessage] = useState('');

  // [PROTOCOL 7] Fetch tickets with server-side pagination
  const { data, isLoading } = useQuery({
    queryKey: ['userTickets', page, filterStatus, searchQuery],
    queryFn: async () => {
        const params = new URLSearchParams({
            page: page.toString(),
        });
        if (filterStatus !== 'all') params.append('status', filterStatus);
        if (searchQuery) params.append('search', searchQuery);

        const res = await api.get(`/user/support/tickets?${params.toString()}`);
        return res.data;
    },
    placeholderData: (previousData) => previousData,
  });

  const tickets = data?.data || [];

  // Fetch FAQs from backend (or use static)
  const { data: faqs } = useQuery({
    queryKey: ['publicFaqs'],
    queryFn: async () => (await api.get('/faqs')).data,
    enabled: activeTab === 'help',
  });

  // Mutation to create a new ticket
  const mutation = useMutation({
    mutationFn: (newTicket: any) => api.post('/user/support-tickets', newTicket),
    onSuccess: (data) => {
      toast.success("Ticket Created!", { description: `Your ticket ID is ${data.data.ticket_code}` });
      queryClient.invalidateQueries({ queryKey: ['userTickets'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error("Failed to Create Ticket", { description: error.response?.data?.message });
    }
  });

  const resetForm = () => {
    setSubject('');
    setCategory('general');
    setPriority('medium');
    setMessage('');
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({ subject, category, priority, message });
  };

  // [PROTOCOL 7] Handlers
  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
      setSearchQuery(e.target.value);
      setPage(1);
  };

  const handleStatusChange = (val: string) => {
      setFilterStatus(val);
      setPage(1);
  };

  // Count tickets by status - NOTE: This only works perfectly if backend sends stats, 
  // otherwise it only counts current page. Ideally backend should send 'overview' stats.
  // For now, we fallback to 0 or what's visible, or you can implement a separate stats endpoint.
  const openCount = 0; // Placeholder until backend supports stats endpoint
  const waitingCount = 0; 
  const closedCount = 0;

  // Get status badge variant
  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'open': return { variant: 'destructive' as const, label: 'Open' };
      case 'waiting_for_user': return { variant: 'warning' as const, label: 'Awaiting Reply' };
      case 'in_progress': return { variant: 'default' as const, label: 'In Progress' };
      case 'closed': return { variant: 'success' as const, label: 'Closed' };
      default: return { variant: 'secondary' as const, label: status };
    }
  };

  // Get priority badge
  const getPriorityBadge = (priority: string) => {
    switch (priority) {
      case 'high': return { variant: 'destructive' as const, label: 'High' };
      case 'medium': return { variant: 'warning' as const, label: 'Medium' };
      case 'low': return { variant: 'secondary' as const, label: 'Low' };
      default: return { variant: 'secondary' as const, label: priority };
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Support Center</h1>
          <p className="text-muted-foreground">Get help, create tickets, and find answers to common questions.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> New Ticket</Button>
          </DialogTrigger>
          <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Create a Support Ticket</DialogTitle>
              <DialogDescription>
                Describe your issue and we'll suggest relevant articles. AI will help classify and prioritize your ticket.
              </DialogDescription>
            </DialogHeader>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Left Column: Form */}
              <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="category">Category</Label>
                <Select value={category} onValueChange={setCategory}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {TICKET_CATEGORIES.map(cat => (
                      <SelectItem key={cat.value} value={cat.value}>
                        <div className="flex items-center gap-2">
                          <cat.icon className="h-4 w-4" />
                          {cat.label}
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                  {TICKET_CATEGORIES.find(c => c.value === category)?.desc}
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="subject">Subject</Label>
                <Input
                  id="subject"
                  value={subject}
                  onChange={(e) => setSubject(e.target.value)}
                  placeholder="Brief description of your issue"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="priority">Priority</Label>
                <Select value={priority} onValueChange={setPriority}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low - General inquiry</SelectItem>
                    <SelectItem value="medium">Medium - Need help soon</SelectItem>
                    <SelectItem value="high">High - Urgent issue</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="message">Message</Label>
                <Textarea
                  id="message"
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  placeholder="Describe your issue in detail. Include any relevant information like error messages, screenshots, etc."
                  required
                  minLength={20}
                  rows={5}
                />
                <p className="text-xs text-muted-foreground">Minimum 20 characters</p>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
                <Button type="submit" disabled={mutation.isPending}>
                  {mutation.isPending ? "Submitting..." : "Submit Ticket"}
                </Button>
              </DialogFooter>
            </form>

            {/* Right Column: AI Suggestions */}
            <div className="lg:border-l lg:pl-6">
              <AISuggestionsPanel
                subject={subject}
                description={message}
                onCategoryChange={setCategory}
                onPriorityChange={setPriority}
              />
            </div>
          </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Cross-links to Other Support Channels */}
      <div>
        <h2 className="text-xl font-semibold mb-4">Other Ways to Get Help</h2>
        <SupportQuickLinks currentPage="support" />
      </div>

      {/* Stats Cards - Note: Static until backend endpoint exists */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Tickets</CardTitle>
            <MessageSquare className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.total || 0}</div>
          </CardContent>
        </Card>
        {/* ... (Other cards would require separate stats API to be accurate) ... */}
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="tickets">
            <MessageSquare className="mr-2 h-4 w-4" /> My Tickets
          </TabsTrigger>
          <TabsTrigger value="help">
            <HelpCircle className="mr-2 h-4 w-4" /> Quick Help
          </TabsTrigger>
          <TabsTrigger value="contact">
            <Headphones className="mr-2 h-4 w-4" /> Contact Us
          </TabsTrigger>
        </TabsList>

        {/* Tickets Tab */}
        <TabsContent value="tickets">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>My Support Tickets</CardTitle>
                  <CardDescription>Track and manage your support requests</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search tickets..."
                      value={searchQuery}
                      onChange={handleSearch}
                      className="pl-10 w-48"
                    />
                  </div>
                  <Select value={filterStatus} onValueChange={handleStatusChange}>
                    <SelectTrigger className="w-36">
                      <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Status</SelectItem>
                      <SelectItem value="open">Open</SelectItem>
                      <SelectItem value="waiting_for_user">Awaiting Reply</SelectItem>
                      <SelectItem value="in_progress">In Progress</SelectItem>
                      <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="text-center py-8 text-muted-foreground">Loading tickets...</div>
              ) : tickets.length === 0 ? (
                <div className="text-center py-12 text-muted-foreground">
                  <MessageSquare className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Tickets Found</p>
                  <p className="text-sm">Create a new ticket if you need help.</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Ticket ID</TableHead>
                      <TableHead>Subject</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Priority</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Last Updated</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {tickets.map((ticket: any) => {
                      const status = getStatusBadge(ticket.status);
                      const priorityBadge = getPriorityBadge(ticket.priority);
                      return (
                        <TableRow key={ticket.id}>
                          <TableCell className="font-mono font-medium">{ticket.ticket_code}</TableCell>
                          <TableCell className="max-w-xs truncate">{ticket.subject}</TableCell>
                          <TableCell>
                            <Badge variant="outline" className="capitalize">
                              {ticket.category}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Badge variant={priorityBadge.variant}>{priorityBadge.label}</Badge>
                          </TableCell>
                          <TableCell>
                            <Badge variant={status.variant}>{status.label}</Badge>
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Calendar className="h-4 w-4 text-muted-foreground" />
                              {new Date(ticket.updated_at).toLocaleDateString()}
                            </div>
                          </TableCell>
                          <TableCell>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => router.push(`/user/support/${ticket.id}`)}
                            >
                              View <ChevronRight className="ml-1 h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              )}

              {/* [PROTOCOL 7] Dynamic Pagination */}
              {data && (
                <PaginationControls
                  currentPage={data.current_page}
                  totalPages={data.last_page}
                  onPageChange={setPage}
                  totalItems={data.total}
                  from={data.from}
                  to={data.to}
                />
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Quick Help Tab */}
        <TabsContent value="help">
          <div className="grid gap-6 md:grid-cols-2">
            {/* FAQs */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <MessageCircleQuestion className="h-5 w-5" />
                  Frequently Asked Questions
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Accordion type="single" collapsible className="w-full">
                  {(faqs?.length > 0 ? faqs : FAQ_ITEMS).slice(0, 5).map((faq: any, idx: number) => (
                    <AccordionItem key={idx} value={`faq-${idx}`}>
                      <AccordionTrigger className="text-left">
                        {faq.question || faq.q}
                      </AccordionTrigger>
                      <AccordionContent className="text-muted-foreground">
                        {faq.answer || faq.a}
                      </AccordionContent>
                    </AccordionItem>
                  ))}
                </Accordion>
              </CardContent>
            </Card>

            {/* Quick Links */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Book className="h-5 w-5" />
                  Quick Links
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {[
                  { href: '/user/kyc', label: 'Complete KYC Verification', icon: FileText },
                  { href: '/user/subscription', label: 'Manage Subscription', icon: CheckCircle },
                  { href: '/user/wallet', label: 'Wallet & Withdrawals', icon: Clock },
                  { href: '/user/referrals', label: 'Referral Program', icon: HelpCircle },
                  { href: '/user/settings', label: 'Account Settings', icon: MessageSquare },
                ].map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    className="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <link.icon className="h-4 w-4 text-muted-foreground" />
                      <span>{link.label}</span>
                    </div>
                    <ExternalLink className="h-4 w-4 text-muted-foreground" />
                  </Link>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Contact Tab */}
        <TabsContent value="contact">
          <div className="grid gap-6 md:grid-cols-3">
            <Card className="text-center">
              <CardHeader>
                <div className="mx-auto p-4 bg-blue-500/10 rounded-full w-fit">
                  <Mail className="h-8 w-8 text-blue-500" />
                </div>
                <CardTitle>Email Support</CardTitle>
                <CardDescription>Get help via email</CardDescription>
              </CardHeader>
              <CardContent>
                <a href="mailto:support@preiposip.com" className="text-primary hover:underline">
                  support@preiposip.com
                </a>
                <p className="text-sm text-muted-foreground mt-2">Response within 24 hours</p>
              </CardContent>
            </Card>

            <Card className="text-center">
              <CardHeader>
                <div className="mx-auto p-4 bg-green-500/10 rounded-full w-fit">
                  <Phone className="h-8 w-8 text-green-500" />
                </div>
                <CardTitle>Phone Support</CardTitle>
                <CardDescription>Talk to our team</CardDescription>
              </CardHeader>
              <CardContent>
                <a href="tel:+919876543210" className="text-primary hover:underline">
                  +91 98765 43210
                </a>
                <p className="text-sm text-muted-foreground mt-2">Mon-Sat, 9 AM - 6 PM</p>
              </CardContent>
            </Card>

            <Card className="text-center">
              <CardHeader>
                <div className="mx-auto p-4 bg-purple-500/10 rounded-full w-fit">
                  <Headphones className="h-8 w-8 text-purple-500" />
                </div>
                <CardTitle>Live Chat</CardTitle>
                <CardDescription>Instant support</CardDescription>
              </CardHeader>
              <CardContent>
                <Button className="w-full">
                  Start Chat
                </Button>
                <p className="text-sm text-muted-foreground mt-2">Available 24/7</p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}