// V-REMEDIATE-1730-158 | V-AUDIT-FIX-ENHANCEMENT (Enhanced with winner announcements, certificates, videos)
'use client';

import { useState } from "react";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import {
  Trophy,
  Ticket,
  Calendar,
  Download,
  Play,
  Award,
  CheckCircle2,
  Gift,
  TrendingUp,
  Users,
  ExternalLink,
  AlertCircle
} from "lucide-react";
import { cn } from "@/lib/utils";

/**
 * Enhanced Lucky Draws Page
 *
 * New Features:
 * - Winner announcements with user details
 * - Downloadable winner certificates
 * - Draw video display for transparency
 * - Entry tracking with earned date
 * - My winnings history
 * - Better prize structure visualization
 * - Past draws with full winner lists
 *
 * [AUDIT FIX] Addresses gap: "Missing winner announcements, certificates, videos"
 */

interface LuckyDrawEntry {
  id: number;
  entry_code: string;
  earned_date: string;
  subscription_id?: number;
  status: 'active' | 'won' | 'lost';
}

interface PrizeWinner {
  id: number;
  user_name: string; // Masked for privacy (e.g., "John D****")
  entry_code: string;
  prize_amount: number;
  rank: number;
  certificate_url?: string;
}

interface LuckyDraw {
  id: number;
  name: string;
  draw_date: string;
  status: 'upcoming' | 'ongoing' | 'completed';
  prize_structure: Array<{
    rank: number;
    count: number;
    amount: number;
  }>;
  winners?: PrizeWinner[];
  draw_video_url?: string;
  total_participants?: number;
  my_winning?: {
    won: boolean;
    prize_amount?: number;
    rank?: number;
    certificate_url?: string;
  };
}

interface LuckyDrawsData {
  active_draw?: LuckyDraw;
  my_entries: LuckyDrawEntry[];
  past_draws: {
    data: LuckyDraw[];
  };
  my_total_winnings?: number;
  total_entries?: number;
}

export default function LuckyDrawsPage() {
  const [selectedDraw, setSelectedDraw] = useState<LuckyDraw | null>(null);
  // PAGINATION: Past draws
  const [pastDrawsPage, setPastDrawsPage] = useState(1);

  const { data, isLoading } = useQuery<LuckyDrawsData>({
    queryKey: ['luckyDraws'],
    
    queryFn: async () => {
      const response = await api.get('/user/lucky-draws');
      const responseData = response.data;
      // Handle nested data structures
      return responseData?.data || responseData || {};
    },
  });
    const { data: paginatedPastDraws } = useQuery<{
      data: LuckyDraw[];
      current_page: number;
      last_page: number;
      total: number;
      from: number;
      to: number;
    }>({

    // PAGINATION: Past draws
      queryKey: ['pastDraws', pastDrawsPage],
    queryFn: async () => {
      return (await api.get(`/user/lucky-draws/past-draws?page=${pastDrawsPage}`)).data;
    },
    placeholderData: (prev) => prev,
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-lg">Loading lucky draw information...</div>
      </div>
    );
  }

  const { active_draw, my_entries = [], past_draws = { data: [] }, my_total_winnings = 0, total_entries = 0 } = data || {};
  const totalPrizePool = active_draw?.prize_structure.reduce((acc, tier) => acc + (tier.count * tier.amount), 0) || 0;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Lucky Draws</h1>
        <p className="text-muted-foreground mt-1">
          Win exciting prizes through our monthly lucky draw program
        </p>
      </div>

      {/* Statistics Grid */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Winnings</CardTitle>
            <Trophy className="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{my_total_winnings.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Lifetime lucky draw earnings
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Entries</CardTitle>
            <Ticket className="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{my_entries.length}</div>
            <p className="text-xs text-muted-foreground mt-1">
              In current draw
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Entries Ever</CardTitle>
            <TrendingUp className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{total_entries || my_entries.length}</div>
            <p className="text-xs text-muted-foreground mt-1">
              All-time participation
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Active Draw Section */}
      {active_draw ? (
        <Card className="border-2 border-blue-200">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <Gift className="h-5 w-5 text-blue-600" />
                  <CardTitle>{active_draw.name}</CardTitle>
                </div>
                <CardDescription className="mt-1">
                  <Calendar className="inline h-3 w-3 mr-1" />
                  Draw date: {new Date(active_draw.draw_date).toLocaleDateString()}
                  {active_draw.status === 'completed' && active_draw.draw_video_url && (
                    <span className="ml-4 text-green-600 font-medium">
                      <CheckCircle2 className="inline h-3 w-3 mr-1" />
                      Draw Completed
                    </span>
                  )}
                </CardDescription>
              </div>
              <Badge variant={active_draw.status === 'completed' ? 'default' : 'secondary'} className="capitalize">
                {active_draw.status}
              </Badge>
            </div>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Prize Pool & Your Entries */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Card className="bg-gradient-to-br from-yellow-50 to-orange-50">
                <CardHeader>
                  <CardTitle className="text-sm font-medium flex items-center gap-2">
                    <Trophy className="h-4 w-4 text-yellow-600" />
                    Total Prize Pool
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold text-yellow-700">
                    ₹{totalPrizePool.toLocaleString()}
                  </div>
                  <p className="text-xs text-muted-foreground mt-1">
                    {active_draw.total_participants || 0} participants
                  </p>
                </CardContent>
              </Card>

              <Card className="bg-gradient-to-br from-blue-50 to-purple-50">
                <CardHeader>
                  <CardTitle className="text-sm font-medium flex items-center gap-2">
                    <Ticket className="h-4 w-4 text-blue-600" />
                    Your Entries
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold text-blue-700">{my_entries.length}</div>
                  <p className="text-xs text-muted-foreground mt-1">
                    {my_entries.length > 0 ? 'Good luck!' : 'Make payments to earn entries'}
                  </p>
                </CardContent>
              </Card>
            </div>

            {/* Prize Structure Table */}
            <div>
              <h4 className="font-semibold mb-3 flex items-center gap-2">
                <Award className="h-4 w-4 text-purple-600" />
                Prize Structure
              </h4>
              <div className="border rounded-lg overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Rank</TableHead>
                      <TableHead>Winners</TableHead>
                      <TableHead>Prize Amount</TableHead>
                      <TableHead className="text-right">Total Pool</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {active_draw.prize_structure.map((tier) => (
                      <TableRow key={tier.rank}>
                        <TableCell className="font-medium">
                          <Badge variant="outline">Rank {tier.rank}</Badge>
                        </TableCell>
                        <TableCell>{tier.count} winner{tier.count > 1 ? 's' : ''}</TableCell>
                        <TableCell className="font-semibold text-green-600">
                          ₹{tier.amount.toLocaleString()}
                        </TableCell>
                        <TableCell className="text-right">
                          ₹{(tier.count * tier.amount).toLocaleString()}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </div>

            {/* Your Entry Codes with Tracking */}
            <div>
              <h4 className="font-semibold mb-3">Your Entry Codes</h4>
              {my_entries.length > 0 ? (
                <div className="border rounded-lg p-4">
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                    {my_entries.map((entry) => (
                      <div
                        key={entry.id}
                        className={cn(
                          "px-3 py-2 rounded-md border text-center",
                          entry.status === 'won' && "bg-green-100 border-green-300",
                          entry.status === 'lost' && "bg-gray-100 border-gray-300",
                          entry.status === 'active' && "bg-blue-50 border-blue-200"
                        )}
                      >
                        <div className="font-mono text-sm font-semibold">{entry.entry_code}</div>
                        <div className="text-xs text-muted-foreground mt-1">
                          {new Date(entry.earned_date).toLocaleDateString()}
                        </div>
                        {entry.status === 'won' && (
                          <Badge variant="default" className="mt-1 text-xs">Winner!</Badge>
                        )}
                      </div>
                    ))}
                  </div>
                  <p className="text-xs text-muted-foreground mt-3">
                    <AlertCircle className="inline h-3 w-3 mr-1" />
                    Earn more entries by making on-time SIP payments
                  </p>
                </div>
              ) : (
                <div className="text-center py-8 border rounded-lg bg-gray-50">
                  <Ticket className="w-12 h-12 mx-auto text-muted-foreground mb-3" />
                  <p className="text-sm text-muted-foreground">
                    You don't have any entries yet. Make on-time payments to earn lucky draw entries!
                  </p>
                </div>
              )}
            </div>

            {/* My Winning Status for This Draw */}
            {active_draw.status === 'completed' && active_draw.my_winning && (
              <Card className={cn(
                "border-2",
                active_draw.my_winning.won ? "border-green-300 bg-green-50" : "border-gray-300 bg-gray-50"
              )}>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    {active_draw.my_winning.won ? (
                      <>
                        <Trophy className="h-5 w-5 text-yellow-600" />
                        Congratulations! You Won!
                      </>
                    ) : (
                      <>
                        <AlertCircle className="h-5 w-5 text-gray-600" />
                        Better Luck Next Time
                      </>
                    )}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {active_draw.my_winning.won ? (
                    <div className="space-y-3">
                      <p className="text-lg">
                        You won <strong className="text-green-600">₹{active_draw.my_winning.prize_amount?.toLocaleString()}</strong> (Rank {active_draw.my_winning.rank})
                      </p>
                      {active_draw.my_winning.certificate_url && (
                        <Button variant="outline" asChild>
                          <a href={active_draw.my_winning.certificate_url} target="_blank" rel="noopener noreferrer">
                            <Download className="h-4 w-4 mr-2" />
                            Download Winner Certificate
                          </a>
                        </Button>
                      )}
                    </div>
                  ) : (
                    <p className="text-muted-foreground">
                      You didn't win this time, but keep making on-time payments to increase your chances in the next draw!
                    </p>
                  )}
                </CardContent>
              </Card>
            )}

            {/* Draw Video (for transparency) */}
            {active_draw.status === 'completed' && active_draw.draw_video_url && (
              <div>
                <h4 className="font-semibold mb-3 flex items-center gap-2">
                  <Play className="h-4 w-4 text-red-600" />
                  Draw Video (Transparency)
                </h4>
                <Card>
                  <CardContent className="p-4">
                    <div className="aspect-video bg-black rounded-lg overflow-hidden">
                      <video
                        controls
                        className="w-full h-full"
                        poster="/images/draw-video-thumbnail.jpg"
                      >
                        <source src={active_draw.draw_video_url} type="video/mp4" />
                        Your browser does not support the video tag.
                      </video>
                    </div>
                    <p className="text-sm text-muted-foreground mt-2">
                      Watch the live draw to verify the fairness and transparency of the selection process.
                    </p>
                  </CardContent>
                </Card>
              </div>
            )}

            {/* Winner Announcements */}
            {active_draw.status === 'completed' && active_draw.winners && active_draw.winners.length > 0 && (
              <div>
                <h4 className="font-semibold mb-3 flex items-center gap-2">
                  <Users className="h-4 w-4 text-purple-600" />
                  Winner Announcements
                </h4>
                <Card>
                  <CardContent className="p-0">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Rank</TableHead>
                          <TableHead>Winner</TableHead>
                          <TableHead>Entry Code</TableHead>
                          <TableHead>Prize Amount</TableHead>
                          <TableHead>Certificate</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {active_draw.winners.map((winner) => (
                          <TableRow key={winner.id}>
                            <TableCell>
                              <Badge variant="outline">#{winner.rank}</Badge>
                            </TableCell>
                            <TableCell className="font-medium">{winner.user_name}</TableCell>
                            <TableCell>
                              <code className="bg-muted px-2 py-1 rounded text-xs">
                                {winner.entry_code}
                              </code>
                            </TableCell>
                            <TableCell className="font-semibold text-green-600">
                              ₹{winner.prize_amount.toLocaleString()}
                            </TableCell>
                            <TableCell>
                              {winner.certificate_url ? (
                                <Button variant="ghost" size="sm" asChild>
                                  <a href={winner.certificate_url} target="_blank" rel="noopener noreferrer">
                                    <ExternalLink className="h-3 w-3" />
                                  </a>
                                </Button>
                              ) : (
                                <span className="text-xs text-muted-foreground">Pending</span>
                              )}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>
              </div>
            )}
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardHeader>
            <CardTitle>No Active Draw</CardTitle>
            <CardDescription>The next lucky draw has not been announced yet. Check back soon!</CardDescription>
          </CardHeader>
        </Card>
      )}

      {/* Past Draws with Tabs */}
      <Card>
        <CardHeader>
          <CardTitle>Past Draws</CardTitle>
          <CardDescription>View previous draw results and winner lists</CardDescription>
        </CardHeader>
        <CardContent>
          {(!paginatedPastDraws?.data || paginatedPastDraws.data.length === 0) ? (
            <div className="text-center py-12">
              <Calendar className="w-12 h-12 mx-auto text-muted-foreground mb-4" />
              <p className="text-muted-foreground">No past draws yet</p>
            </div>
          ) : (
            <Tabs defaultValue="list">
              <TabsList>
                <TabsTrigger value="list">Draw List</TabsTrigger>
                <TabsTrigger value="details">Draw Details</TabsTrigger>
              </TabsList>

              <TabsContent value="list" className="mt-4">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Draw Name</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Winners</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {(paginatedPastDraws?.data || []).map((draw) => (
                      <TableRow key={draw.id}>
                        <TableCell className="font-medium">{draw.name}</TableCell>
                        <TableCell>{new Date(draw.draw_date).toLocaleDateString()}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className="capitalize">{draw.status}</Badge>
                        </TableCell>
                        <TableCell>{draw.winners?.length || 0} winners</TableCell>
                        <TableCell>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedDraw(draw)}
                          >
                            View Details
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
                <PaginationControls
                  currentPage={paginatedPastDraws?.current_page}
                  totalPages={paginatedPastDraws?.last_page}
                  onPageChange={setPastDrawsPage}
                  totalItems={paginatedPastDraws?.total}
                  from={paginatedPastDraws?.from}
                  to={paginatedPastDraws?.to}
                />


              </TabsContent>

              <TabsContent value="details" className="mt-4">
                {selectedDraw ? (
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <h3 className="text-lg font-semibold">{selectedDraw.name}</h3>
                      <Button variant="outline" onClick={() => setSelectedDraw(null)}>
                        Back to List
                      </Button>
                    </div>

                    {selectedDraw.winners && selectedDraw.winners.length > 0 ? (
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Rank</TableHead>
                            <TableHead>Winner</TableHead>
                            <TableHead>Entry Code</TableHead>
                            <TableHead>Prize</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {selectedDraw.winners.map((winner) => (
                            <TableRow key={winner.id}>
                              <TableCell><Badge>#{winner.rank}</Badge></TableCell>
                              <TableCell>{winner.user_name}</TableCell>
                              <TableCell><code className="text-xs">{winner.entry_code}</code></TableCell>
                              <TableCell className="text-green-600 font-semibold">
                                ₹{winner.prize_amount.toLocaleString()}
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    ) : (
                      <p className="text-center text-muted-foreground py-8">
                        Winner information not available for this draw
                      </p>
                    )}
                  </div>
                ) : (
                  <p className="text-center text-muted-foreground py-8">
                    Select a draw from the list to view details
                  </p>
                )}
              </TabsContent>
            </Tabs>
          )}
        </CardContent>
      </Card>
    </div>
  );
}