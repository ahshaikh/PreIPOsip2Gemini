'use client';

import { useState, useEffect } from 'react';
import { useRouter, useParams } from 'next/navigation';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  AlertCircle,
  CheckCircle2,
  Clock,
  XCircle,
  RefreshCw,
  ArrowLeft,
  AlertTriangle,
  User,
  CreditCard,
  TrendingUp,
  FileText,
  Shield,
  Activity,
} from 'lucide-react';
import { api } from '@/lib/api';

interface SagaDetail {
  id: number;
  saga_id: string;
  status: string;
  steps_completed: number;
  steps_total: number;
  failure_step?: string;
  failure_reason?: string;
  resolution_data?: any;
  resolved_by?: {
    id: number;
    name: string;
    email: string;
  };
  initiated_at: string;
  completed_at?: string;
  failed_at?: string;
  compensated_at?: string;
  resolved_at?: string;
  metadata: {
    payment_id: number;
    user_id: number;
    subscription_id: number;
    amount: number;
    amount_paise: number;
    steps?: {
      credit_wallet?: {
        transaction_id: number;
        amount_paise: number;
        completed_at: string;
      };
      credit_bonus?: {
        bonus_id: number;
        amount: number;
        completed_at: string;
      };
      allocate_shares?: {
        investment_ids: number[];
        total_value_allocated: number;
        completed_at: string;
      };
    };
  };
  payment?: {
    id: number;
    amount: number;
    status: string;
    payment_method: string;
    razorpay_payment_id?: string;
    created_at: string;
  };
  user?: {
    id: number;
    username: string;
    email: string;
    phone?: string;
  };
  duration?: string;
  needs_attention: boolean;
}

export default function SagaDetailPage() {
  const router = useRouter();
  const params = useParams();
  const sagaId = params.id as string;

  const [saga, setSaga] = useState<SagaDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [resolving, setResolving] = useState(false);
  const [retrying, setRetrying] = useState(false);
  const [compensating, setCompensating] = useState(false);

  // Resolution form state
  const [resolutionAction, setResolutionAction] = useState('');
  const [resolutionNotes, setResolutionNotes] = useState('');
  const [showResolveDialog, setShowResolveDialog] = useState(false);

  useEffect(() => {
    fetchSagaDetails();
  }, [sagaId]);

  const fetchSagaDetails = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/admin/sagas/${sagaId}`);
      setSaga(response.data.data);
    } catch (error) {
      console.error('Failed to fetch saga details:', error);
      alert('Failed to load saga details');
    } finally {
      setLoading(false);
    }
  };

  const handleRetry = async () => {
    if (!confirm('Are you sure you want to retry this saga? This will create a new saga execution.')) {
      return;
    }

    setRetrying(true);
    try {
      const response = await api.post(`/admin/sagas/${sagaId}/retry`);
      alert(`Saga retry initiated. New saga ID: ${response.data.data.new_saga_id}`);
      router.push(`/admin/sagas/${response.data.data.new_saga_execution_id}`);
    } catch (error: any) {
      alert(`Retry failed: ${error.response?.data?.message || error.message}`);
    } finally {
      setRetrying(false);
    }
  };

  const handleForceCompensate = async () => {
    if (!confirm('WARNING: Force compensation will attempt to rollback all completed steps. This action cannot be undone. Continue?')) {
      return;
    }

    setCompensating(true);
    try {
      await api.post(`/admin/sagas/${sagaId}/force-compensate`);
      alert('Force compensation completed successfully');
      fetchSagaDetails();
    } catch (error: any) {
      alert(`Force compensation failed: ${error.response?.data?.message || error.message}`);
    } finally {
      setCompensating(false);
    }
  };

  const handleManualResolve = async () => {
    if (!resolutionAction || !resolutionNotes) {
      alert('Please provide both action taken and resolution notes');
      return;
    }

    setResolving(true);
    try {
      await api.post(`/admin/sagas/${sagaId}/resolve`, {
        action_taken: resolutionAction,
        resolution_notes: resolutionNotes,
      });
      alert('Saga marked as manually resolved');
      setShowResolveDialog(false);
      fetchSagaDetails();
    } catch (error: any) {
      alert(`Manual resolution failed: ${error.response?.data?.message || error.message}`);
    } finally {
      setResolving(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { color: string; icon: any }> = {
      processing: { color: 'bg-blue-100 text-blue-800', icon: Clock },
      completed: { color: 'bg-green-100 text-green-800', icon: CheckCircle2 },
      failed: { color: 'bg-red-100 text-red-800', icon: XCircle },
      compensated: { color: 'bg-yellow-100 text-yellow-800', icon: RefreshCw },
      compensation_failed: { color: 'bg-orange-100 text-orange-800', icon: AlertCircle },
      requires_manual_resolution: { color: 'bg-purple-100 text-purple-800', icon: AlertTriangle },
      manually_resolved: { color: 'bg-gray-100 text-gray-800', icon: CheckCircle2 },
    };

    const variant = variants[status] || variants.failed;
    const Icon = variant.icon;

    return (
      <Badge className={variant.color}>
        <Icon className="w-3 h-3 mr-1" />
        {status.replace(/_/g, ' ').toUpperCase()}
      </Badge>
    );
  };

  const renderTimeline = () => {
    if (!saga) return null;

    const events = [];

    events.push({
      label: 'Saga Initiated',
      timestamp: saga.initiated_at,
      icon: Activity,
      color: 'text-blue-500',
    });

    // Step completions
    const steps = saga.metadata?.steps || {};
    if (steps.credit_wallet) {
      events.push({
        label: 'Wallet Credited',
        timestamp: steps.credit_wallet.completed_at,
        icon: CheckCircle2,
        color: 'text-green-500',
        details: `Transaction ID: ${steps.credit_wallet.transaction_id}, Amount: ₹${steps.credit_wallet.amount_paise / 100}`,
      });
    }

    if (steps.credit_bonus) {
      events.push({
        label: 'Bonus Credited',
        timestamp: steps.credit_bonus.completed_at,
        icon: CheckCircle2,
        color: 'text-green-500',
        details: `Bonus ID: ${steps.credit_bonus.bonus_id}, Amount: ₹${steps.credit_bonus.amount}`,
      });
    }

    if (steps.allocate_shares) {
      events.push({
        label: 'Shares Allocated',
        timestamp: steps.allocate_shares.completed_at,
        icon: CheckCircle2,
        color: 'text-green-500',
        details: `Investments: ${steps.allocate_shares.investment_ids.length}, Value: ₹${steps.allocate_shares.total_value_allocated}`,
      });
    }

    if (saga.failed_at) {
      events.push({
        label: 'Saga Failed',
        timestamp: saga.failed_at,
        icon: XCircle,
        color: 'text-red-500',
        details: saga.failure_reason,
      });
    }

    if (saga.compensated_at) {
      events.push({
        label: 'Compensation Completed',
        timestamp: saga.compensated_at,
        icon: RefreshCw,
        color: 'text-yellow-500',
      });
    }

    if (saga.completed_at) {
      events.push({
        label: 'Saga Completed',
        timestamp: saga.completed_at,
        icon: CheckCircle2,
        color: 'text-green-500',
      });
    }

    if (saga.resolved_at) {
      events.push({
        label: 'Manually Resolved',
        timestamp: saga.resolved_at,
        icon: Shield,
        color: 'text-purple-500',
        details: saga.resolved_by ? `By: ${saga.resolved_by.name}` : undefined,
      });
    }

    return (
      <div className="space-y-4">
        {events.map((event, index) => {
          const Icon = event.icon;
          return (
            <div key={index} className="flex gap-4">
              <div className="flex flex-col items-center">
                <div className={`rounded-full p-2 bg-gray-100 ${event.color}`}>
                  <Icon className="w-5 h-5" />
                </div>
                {index < events.length - 1 && (
                  <div className="w-0.5 h-full bg-gray-300 mt-2" />
                )}
              </div>
              <div className="flex-1 pb-8">
                <div className="font-medium">{event.label}</div>
                <div className="text-sm text-gray-500">
                  {new Date(event.timestamp).toLocaleString()}
                </div>
                {event.details && (
                  <div className="text-sm text-gray-600 mt-1">{event.details}</div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    );
  };

  if (loading) {
    return (
      <div className="container mx-auto p-6 flex items-center justify-center h-screen">
        <RefreshCw className="w-8 h-8 animate-spin text-gray-500" />
      </div>
    );
  }

  if (!saga) {
    return (
      <div className="container mx-auto p-6">
        <div className="text-center">
          <XCircle className="w-16 h-16 text-red-500 mx-auto mb-4" />
          <h2 className="text-2xl font-bold mb-2">Saga Not Found</h2>
          <p className="text-gray-600 mb-4">The requested saga could not be found.</p>
          <Button onClick={() => router.push('/admin/sagas')}>
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Saga List
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-start">
        <div>
          <Button
            variant="ghost"
            onClick={() => router.push('/admin/sagas')}
            className="mb-2"
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Saga List
          </Button>
          <h1 className="text-3xl font-bold">Saga Details</h1>
          <p className="text-gray-600 font-mono text-sm mt-1">{saga.saga_id}</p>
        </div>
        <div className="flex gap-2">
          {saga.status === 'failed' && (
            <Button
              onClick={handleRetry}
              disabled={retrying}
              variant="outline"
            >
              <RefreshCw className={`w-4 h-4 mr-2 ${retrying ? 'animate-spin' : ''}`} />
              Retry Saga
            </Button>
          )}
          {(saga.status === 'failed' || saga.status === 'compensation_failed') && (
            <Button
              onClick={handleForceCompensate}
              disabled={compensating}
              variant="outline"
              className="text-orange-600"
            >
              <AlertTriangle className={`w-4 h-4 mr-2 ${compensating ? 'animate-spin' : ''}`} />
              Force Compensate
            </Button>
          )}
          {(saga.status === 'compensation_failed' || saga.status === 'requires_manual_resolution') && (
            <Dialog open={showResolveDialog} onOpenChange={setShowResolveDialog}>
              <DialogTrigger asChild>
                <Button variant="default">
                  <Shield className="w-4 h-4 mr-2" />
                  Manual Resolve
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Manual Resolution</DialogTitle>
                  <DialogDescription>
                    Document the manual actions taken to resolve this saga
                  </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                  <div>
                    <Label htmlFor="action">Action Taken</Label>
                    <Input
                      id="action"
                      placeholder="e.g., Manually credited shares, Refunded payment"
                      value={resolutionAction}
                      onChange={(e) => setResolutionAction(e.target.value)}
                    />
                  </div>
                  <div>
                    <Label htmlFor="notes">Resolution Notes</Label>
                    <Textarea
                      id="notes"
                      placeholder="Detailed explanation of what was done and why..."
                      rows={5}
                      value={resolutionNotes}
                      onChange={(e) => setResolutionNotes(e.target.value)}
                    />
                  </div>
                </div>
                <DialogFooter>
                  <Button
                    variant="outline"
                    onClick={() => setShowResolveDialog(false)}
                  >
                    Cancel
                  </Button>
                  <Button
                    onClick={handleManualResolve}
                    disabled={resolving}
                  >
                    {resolving ? 'Resolving...' : 'Mark as Resolved'}
                  </Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>
          )}
        </div>
      </div>

      {/* Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Status
            </CardTitle>
          </CardHeader>
          <CardContent>
            {getStatusBadge(saga.status)}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Progress
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {saga.steps_completed}/{saga.steps_total}
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2 mt-2">
              <div
                className={`h-2 rounded-full ${
                  saga.status === 'completed'
                    ? 'bg-green-500'
                    : saga.status === 'failed'
                    ? 'bg-red-500'
                    : 'bg-blue-500'
                }`}
                style={{
                  width: `${(saga.steps_completed / saga.steps_total) * 100}%`,
                }}
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Duration
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{saga.duration || 'N/A'}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-gray-600">
              Needs Attention
            </CardTitle>
          </CardHeader>
          <CardContent>
            {saga.needs_attention ? (
              <Badge className="bg-red-100 text-red-800">
                <AlertCircle className="w-3 h-3 mr-1" />
                Yes
              </Badge>
            ) : (
              <Badge className="bg-green-100 text-green-800">
                <CheckCircle2 className="w-3 h-3 mr-1" />
                No
              </Badge>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Timeline */}
        <Card>
          <CardHeader>
            <CardTitle>Execution Timeline</CardTitle>
            <CardDescription>Chronological saga execution events</CardDescription>
          </CardHeader>
          <CardContent>{renderTimeline()}</CardContent>
        </Card>

        {/* Related Information */}
        <div className="space-y-6">
          {/* Payment Information */}
          {saga.payment && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <CreditCard className="w-5 h-5 mr-2" />
                  Payment Information
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableBody>
                    <TableRow>
                      <TableCell className="font-medium">Payment ID</TableCell>
                      <TableCell>{saga.payment.id}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium">Amount</TableCell>
                      <TableCell>₹{saga.payment.amount}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium">Status</TableCell>
                      <TableCell>
                        <Badge>{saga.payment.status}</Badge>
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium">Method</TableCell>
                      <TableCell>{saga.payment.payment_method}</TableCell>
                    </TableRow>
                    {saga.payment.razorpay_payment_id && (
                      <TableRow>
                        <TableCell className="font-medium">Razorpay ID</TableCell>
                        <TableCell className="font-mono text-xs">
                          {saga.payment.razorpay_payment_id}
                        </TableCell>
                      </TableRow>
                    )}
                    <TableRow>
                      <TableCell className="font-medium">Created</TableCell>
                      <TableCell>
                        {new Date(saga.payment.created_at).toLocaleString()}
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          )}

          {/* User Information */}
          {saga.user && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center">
                  <User className="w-5 h-5 mr-2" />
                  User Information
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableBody>
                    <TableRow>
                      <TableCell className="font-medium">User ID</TableCell>
                      <TableCell>{saga.user.id}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium">Username</TableCell>
                      <TableCell>{saga.user.username}</TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell className="font-medium">Email</TableCell>
                      <TableCell>{saga.user.email}</TableCell>
                    </TableRow>
                    {saga.user.phone && (
                      <TableRow>
                        <TableCell className="font-medium">Phone</TableCell>
                        <TableCell>{saga.user.phone}</TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
                <Button
                  variant="outline"
                  size="sm"
                  className="mt-4 w-full"
                  onClick={() => router.push(`/admin/users/${saga.user?.id}`)}
                >
                  View User Profile
                </Button>
              </CardContent>
            </Card>
          )}

          {/* Failure Information */}
          {saga.failure_reason && (
            <Card className="border-red-200">
              <CardHeader>
                <CardTitle className="flex items-center text-red-600">
                  <XCircle className="w-5 h-5 mr-2" />
                  Failure Information
                </CardTitle>
              </CardHeader>
              <CardContent>
                {saga.failure_step && (
                  <div className="mb-4">
                    <div className="text-sm font-medium text-gray-600">Failed Step</div>
                    <div className="text-lg">{saga.failure_step}</div>
                  </div>
                )}
                <div>
                  <div className="text-sm font-medium text-gray-600">Failure Reason</div>
                  <div className="text-sm bg-red-50 p-3 rounded-md mt-1">
                    {saga.failure_reason}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Resolution Information */}
          {saga.resolved_at && (
            <Card className="border-purple-200">
              <CardHeader>
                <CardTitle className="flex items-center text-purple-600">
                  <Shield className="w-5 h-5 mr-2" />
                  Manual Resolution
                </CardTitle>
              </CardHeader>
              <CardContent>
                {saga.resolved_by && (
                  <div className="mb-4">
                    <div className="text-sm font-medium text-gray-600">Resolved By</div>
                    <div className="text-lg">
                      {saga.resolved_by.name} ({saga.resolved_by.email})
                    </div>
                  </div>
                )}
                <div className="mb-4">
                  <div className="text-sm font-medium text-gray-600">Resolved At</div>
                  <div>{new Date(saga.resolved_at).toLocaleString()}</div>
                </div>
                {saga.resolution_data && (
                  <div>
                    <div className="text-sm font-medium text-gray-600 mb-2">
                      Resolution Details
                    </div>
                    <div className="bg-purple-50 p-3 rounded-md">
                      {saga.resolution_data.action_taken && (
                        <div className="mb-2">
                          <span className="font-medium">Action: </span>
                          {saga.resolution_data.action_taken}
                        </div>
                      )}
                      {saga.resolution_data.resolution_notes && (
                        <div>
                          <span className="font-medium">Notes: </span>
                          {saga.resolution_data.resolution_notes}
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Raw Metadata */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <FileText className="w-5 h-5 mr-2" />
            Raw Metadata
          </CardTitle>
          <CardDescription>Complete saga execution metadata</CardDescription>
        </CardHeader>
        <CardContent>
          <pre className="bg-gray-50 p-4 rounded-md overflow-auto text-xs">
            {JSON.stringify(saga.metadata, null, 2)}
          </pre>
        </CardContent>
      </Card>
    </div>
  );
}
