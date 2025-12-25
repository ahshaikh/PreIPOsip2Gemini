// V-PHASE5-1730-121 | V-ENHANCED-REFERRALS
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { useState } from "react";
import {
  Copy, Gift, Users, TrendingUp, Share2, Link2, Trophy,
  Facebook, Twitter, Linkedin, Mail, MessageCircle, QrCode,
  Target, Award, ChevronRight, Sparkles, IndianRupee, Calendar,
  Instagram, Send, Bot, Hash, MessageSquareText
} from "lucide-react";

export default function ReferralsPage() {
  const [activeTab, setActiveTab] = useState('overview');
  const [showQR, setShowQR] = useState(false);
  // PAGINATION STATE
const [referralsPage, setReferralsPage] = useState(1);
const [rewardsPage, setRewardsPage] = useState(1);
const [statusFilter, setStatusFilter] = useState('all');


  const { data, isLoading } = useQuery({
    queryKey: ['referrals'],
    queryFn: async () => {
      const response = await api.get('/user/referrals');
      const responseData = response.data;
      return responseData?.data || responseData || {};
    },
  });

  // Fetch referral rewards/earnings
  const { data: rewards } = useQuery({
    queryKey: ['referralRewards'],
    queryFn: async () => (await api.get('/user/referrals/rewards')).data,
  });
  // PAGINATED REFERRAL LIST
const { data: paginatedReferrals } = useQuery({
  queryKey: ['referralsList', referralsPage, statusFilter],
  queryFn: async () => {
    const params = new URLSearchParams({
      page: referralsPage.toString(),
      status: statusFilter,
    });
    return (await api.get(`/user/referrals/list?${params}`)).data;
  },
  placeholderData: (prev) => prev,
});

// PAGINATED REWARDS HISTORY
const { data: paginatedRewards } = useQuery({
  queryKey: ['referralRewards', rewardsPage],
  queryFn: async () => {
    return (await api.get(`/user/referrals/rewards?page=${rewardsPage}`)).data;
  },
  placeholderData: (prev) => prev,
});

  if (isLoading) return <div className="flex items-center justify-center h-64">Loading referrals...</div>;

  // Use environment variable for the base URL (SSR-safe with fallback)
  const baseUrl = typeof window !== 'undefined'
    ? window.location.origin
    : process.env.NEXT_PUBLIC_SITE_URL || 'https://preiposip.com';
  const referralCode = data?.stats?.referral_code || data?.referral_code || data?.user?.referral_code || '';
  const referralLink = `${baseUrl}/signup?ref=${referralCode}`;

  const copyToClipboard = () => {
    navigator.clipboard.writeText(referralLink);
    toast.success("Link Copied!", { description: "Referral link copied to clipboard" });
  };

  const copyCode = () => {
    if (referralCode) {
      navigator.clipboard.writeText(referralCode);
      toast.success("Code Copied!", { description: "Referral code copied to clipboard" });
    } else {
      toast.error("No referral code available");
    }
  };

  // Share functions
  const shareOnFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`, '_blank');
  };

  const shareOnTwitter = () => {
    const text = `Join me on PreIPO SIP and start investing in pre-IPO companies! Use my referral link:`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(referralLink)}`, '_blank');
  };

  const shareOnLinkedIn = () => {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(referralLink)}`, '_blank');
  };

  const shareOnWhatsApp = () => {
    const text = `Join me on PreIPO SIP! Use my referral code ${referralCode} to get started: ${referralLink}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareViaEmail = () => {
    const subject = 'Join me on PreIPO SIP';
    const body = `Hi,\n\nI've been investing in pre-IPO companies through PreIPO SIP and thought you might be interested too!\n\nUse my referral link to sign up: ${referralLink}\n\nOr use my referral code: ${referralCode}`;
    window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  };

  const shareOnTelegram = () => {
    const text = `Join me on PreIPO SIP! Use my code ${referralCode} to get started: ${referralLink}`;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnReddit = () => {
    const title = 'Invest in Pre-IPO Companies with PreIPO SIP';
    window.open(`https://reddit.com/submit?url=${encodeURIComponent(referralLink)}&title=${encodeURIComponent(title)}`, '_blank');
  };

  const shareOnThreads = () => {
    const text = `Join me on PreIPO SIP and start investing in pre-IPO companies! Use my referral code: ${referralCode}`;
    window.open(`https://threads.net/intent/post?text=${encodeURIComponent(text + ' ' + referralLink)}`, '_blank');
  };

  const shareOnDiscord = () => {
    // Discord doesn't have a direct share URL, so we copy a formatted message
    const message = `🚀 Join me on PreIPO SIP!\n\nInvest in pre-IPO companies with zero fees.\nUse my referral code: **${referralCode}**\n${referralLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in Discord to share" });
  };

  const shareOnSignal = () => {
    // Signal doesn't have a web share URL, copy message for manual sharing
    const message = `Join me on PreIPO SIP! Use my referral code ${referralCode} to get started: ${referralLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in Signal to share" });
  };

  const shareOnLine = () => {
    const text = `Join me on PreIPO SIP! Use code ${referralCode}`;
    window.open(`https://social-plugins.line.me/lineit/share?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnInstagram = () => {
    // Instagram doesn't support direct web sharing, copy to clipboard with instructions
    const message = `📈 Join me on PreIPO SIP!\n\nInvest in pre-IPO companies with zero fees 💰\n\nUse my referral code: ${referralCode}\nLink: ${referralLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in your Instagram Story or Bio" });
  };

  // Calculate next multiplier milestone
  const completedReferrals = data?.stats?.completed_referrals || 0;
  const nextMilestone = completedReferrals < 5 ? 5 : completedReferrals < 10 ? 10 : completedReferrals < 25 ? 25 : 50;
  const progressToNext = (completedReferrals / nextMilestone) * 100;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">My Referrals</h1>
          <p className="text-muted-foreground">Share your link, earn rewards, and boost your multiplier!</p>
        </div>
      </div>

      {/* Referral Link Card */}
      <Card className="bg-gradient-to-r from-primary/10 via-primary/5 to-background border-primary/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Share2 className="h-5 w-5" /> Share Your Referral Link
          </CardTitle>
          <CardDescription>Earn bonuses when your friends sign up and invest.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Referral Link */}
          <div className="flex gap-2">
            <Input value={referralLink} readOnly className="font-mono text-sm" />
            <Button onClick={copyToClipboard}>
              <Copy className="h-4 w-4 mr-2" /> Copy
            </Button>
          </div>

          {/* Referral Code */}
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm text-muted-foreground">Your Code:</span>
              <Badge variant="secondary" className="font-mono text-lg px-3 py-1">
                {referralCode || 'LOADING...'}
              </Badge>
              <Button variant="ghost" size="sm" onClick={copyCode} title="Copy referral code">
                <Copy className="h-4 w-4" />
              </Button>
            </div>
          </div>

          {/* Social Share Buttons */}
          <div className="flex flex-wrap gap-2 pt-2">
            <Button variant="outline" size="sm" onClick={shareOnWhatsApp} className="bg-green-500/10 hover:bg-green-500/20 border-green-500/30">
              <MessageCircle className="h-4 w-4 mr-2 text-green-500" /> WhatsApp
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnTelegram} className="bg-sky-400/10 hover:bg-sky-400/20 border-sky-400/30">
              <Send className="h-4 w-4 mr-2 text-sky-400" /> Telegram
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnFacebook} className="bg-blue-500/10 hover:bg-blue-500/20 border-blue-500/30">
              <Facebook className="h-4 w-4 mr-2 text-blue-500" /> Facebook
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnInstagram} className="bg-pink-500/10 hover:bg-pink-500/20 border-pink-500/30">
              <Instagram className="h-4 w-4 mr-2 text-pink-500" /> Instagram
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnTwitter} className="bg-sky-500/10 hover:bg-sky-500/20 border-sky-500/30">
              <Twitter className="h-4 w-4 mr-2 text-sky-500" /> Twitter
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnThreads} className="bg-black/10 hover:bg-black/20 border-black/30 dark:bg-white/10 dark:hover:bg-white/20 dark:border-white/30">
              <MessageSquareText className="h-4 w-4 mr-2" /> Threads
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnLinkedIn} className="bg-blue-700/10 hover:bg-blue-700/20 border-blue-700/30">
              <Linkedin className="h-4 w-4 mr-2 text-blue-700" /> LinkedIn
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnReddit} className="bg-orange-500/10 hover:bg-orange-500/20 border-orange-500/30">
              <Hash className="h-4 w-4 mr-2 text-orange-500" /> Reddit
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnDiscord} className="bg-indigo-500/10 hover:bg-indigo-500/20 border-indigo-500/30">
              <Bot className="h-4 w-4 mr-2 text-indigo-500" /> Discord
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnSignal} className="bg-blue-600/10 hover:bg-blue-600/20 border-blue-600/30">
              <MessageCircle className="h-4 w-4 mr-2 text-blue-600" /> Signal
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnLine} className="bg-green-600/10 hover:bg-green-600/20 border-green-600/30">
              <MessageCircle className="h-4 w-4 mr-2 text-green-600" /> Line
            </Button>
            <Button variant="outline" size="sm" onClick={shareViaEmail}>
              <Mail className="h-4 w-4 mr-2" /> Email
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
            <Users className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.stats?.total_referrals || 0}</div>
            <p className="text-xs text-muted-foreground">People signed up with your link</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Completed Referrals</CardTitle>
            <Target className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{completedReferrals}</div>
            <p className="text-xs text-muted-foreground">Made their first investment</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-yellow-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Current Multiplier</CardTitle>
            <Sparkles className="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">{data?.stats?.current_multiplier || 1}x</div>
            <p className="text-xs text-muted-foreground">Applies to your bonuses</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-purple-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Earned</CardTitle>
            <IndianRupee className="h-4 w-4 text-purple-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-purple-600">
              ₹{Number(rewards?.total_earned || 0).toLocaleString('en-IN')}
            </div>
            <p className="text-xs text-muted-foreground">From referrals</p>
          </CardContent>
        </Card>
      </div>

      {/* Multiplier Progress */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Multiplier Progress</CardTitle>
              <CardDescription>Increase your multiplier by getting more completed referrals</CardDescription>
            </div>
            <Badge variant="outline" className="text-lg px-3 py-1">
              {data?.stats?.current_multiplier || 1}x
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-center justify-between text-sm">
              <span>{completedReferrals} completed</span>
              <span>{nextMilestone} for next level</span>
            </div>
            <Progress value={progressToNext} className="h-3" />
            <div className="grid grid-cols-4 gap-4 mt-4">
              {[
                { referrals: 5, multiplier: '1.5x' },
                { referrals: 10, multiplier: '2x' },
                { referrals: 25, multiplier: '2.5x' },
                { referrals: 50, multiplier: '3x' },
              ].map((tier) => (
                <div
                  key={tier.referrals}
                  className={`p-3 rounded-lg text-center border ${
                    completedReferrals >= tier.referrals
                      ? 'bg-green-500/10 border-green-500/30'
                      : 'bg-muted/50'
                  }`}
                >
                  <Trophy className={`h-5 w-5 mx-auto mb-1 ${
                    completedReferrals >= tier.referrals ? 'text-green-500' : 'text-muted-foreground'
                  }`} />
                  <p className="text-sm font-bold">{tier.multiplier}</p>
                  <p className="text-xs text-muted-foreground">{tier.referrals} referrals</p>
                </div>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Tabs for Referrals and Rewards */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="overview">
            <Users className="mr-2 h-4 w-4" /> Referral List
          </TabsTrigger>
          <TabsTrigger value="rewards">
            <Gift className="mr-2 h-4 w-4" /> Rewards History
          </TabsTrigger>
        </TabsList>

        {/* Referral List */}
        <TabsContent value="overview">
          <Card>
            <CardHeader>
              <CardTitle>Your Referrals</CardTitle>
              <CardDescription>People who signed up using your referral link</CardDescription>
            </CardHeader>
            <CardContent>
              {(!paginatedReferrals?.data || paginatedReferrals.data.length === 0) ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Users className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Referrals Yet</p>
                  <p className="text-sm">Share your link to start earning rewards!</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>User</TableHead>
                      <TableHead>Referred On</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead className="text-right">Bonus Earned</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {paginatedReferrals.data.map((ref: any) => (
                      <TableRow key={ref.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                              {ref.referred?.username?.charAt(0)?.toUpperCase() || 'U'}
                            </div>
                            <span className="font-medium">{ref.referred?.username}</span>
                          </div>
                        </TableCell>
                        <PaginationControls
                          currentPage={paginatedReferrals.current_page}
                          totalPages={paginatedReferrals.last_page}
                          onPageChange={setReferralsPage}
                          totalItems={paginatedReferrals.total}
                          from={paginatedReferrals.from}
                          to={paginatedReferrals.to}
                        />
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            {new Date(ref.created_at).toLocaleDateString()}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant={ref.status === 'completed' ? 'success' : 'secondary'}>
                            {ref.status === 'completed' ? 'Invested' : 'Pending'}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          {ref.status === 'completed' ? (
                            <span className="text-green-600 font-medium">
                              ₹{Number(ref.bonus_amount || 0).toLocaleString('en-IN')}
                            </span>
                          ) : (
                            <span className="text-muted-foreground">--</span>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Rewards History */}
        <TabsContent value="rewards">
          <Card>
            <CardHeader>
              <CardTitle>Rewards History</CardTitle>
              <CardDescription>All bonuses earned from your referrals</CardDescription>
            </CardHeader>
            <CardContent>
              {(!paginatedRewards?.data || paginatedRewards.data.length === 0) ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Gift className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Rewards Yet</p>
                  <p className="text-sm">Complete referrals to start earning!</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Referred User</TableHead>
                      <TableHead className="text-right">Amount</TableHead>
                      <TableHead>Status</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {paginatedRewards.data.map((reward: any) => (
                      <TableRow key={reward.id}>
                        <TableCell>
                          {new Date(reward.created_at).toLocaleDateString()}
                        </TableCell>
                        <PaginationControls
                          currentPage={paginatedRewards.current_page}
                          totalPages={paginatedRewards.last_page}
                          onPageChange={setRewardsPage}
                          totalItems={paginatedRewards.total}
                          from={paginatedRewards.from}
                          to={paginatedRewards.to}
                        />
                        <TableCell>
                          <Badge variant="outline">
                            {reward.type === 'referral_bonus' ? 'Referral' : reward.type}
                          </Badge>
                        </TableCell>
                        <TableCell>{reward.referred_user || '--'}</TableCell>
                        <TableCell className="text-right font-medium text-green-600">
                          +₹{Number(reward.amount).toLocaleString('en-IN')}
                        </TableCell>
                        <TableCell>
                          <Badge variant={reward.status === 'credited' ? 'success' : 'warning'}>
                            {reward.status}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* How It Works */}
      <Card>
        <CardHeader>
          <CardTitle>How Referrals Work</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-4 gap-4">
            {[
              { step: 1, title: 'Share Your Link', desc: 'Send your unique referral link to friends', icon: Share2 },
              { step: 2, title: 'They Sign Up', desc: 'Your friend creates an account using your link', icon: Users },
              { step: 3, title: 'They Invest', desc: 'When they make their first investment, you both earn', icon: Target },
              { step: 4, title: 'Earn Rewards', desc: 'Get bonuses credited to your wallet automatically', icon: Gift },
            ].map((item) => (
              <div key={item.step} className="flex items-start gap-3 p-4 bg-muted/30 rounded-lg">
                <div className="p-2 bg-primary/10 rounded-full">
                  <item.icon className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="font-medium">Step {item.step}: {item.title}</p>
                  <p className="text-sm text-muted-foreground">{item.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
