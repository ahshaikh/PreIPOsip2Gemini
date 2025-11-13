// V-FINAL-1730-263
'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { AlertTriangle, PauseCircle, XCircle, ArrowUpCircle } from "lucide-react";

interface ManageSubModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentPlanId: number;
  plans: any[];
  status: string;
}

export function ManageSubscriptionModal({ isOpen, onClose, currentPlanId, plans, status }: ManageSubModalProps) {
  const queryClient = useQueryClient();
  const [selectedPlan, setSelectedPlan] = useState<string>("");
  const [pauseMonths, setPauseMonths] = useState("1");
  const [cancelReason, setCancelReason] = useState("");

  // Mutations
  const changePlanMutation = useMutation({
    mutationFn: (id: string) => api.post('/user/subscription/change-plan', { new_plan_id: id }),
    onSuccess: (data) => {
      toast.success("Plan Changed", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: any) => toast.error("Failed", { description: e.response?.data?.message })
  });

  const pauseMutation = useMutation({
    mutationFn: (months: string) => api.post('/user/subscription/pause', { months }),
    onSuccess: (data) => {
      toast.success("Subscription Paused", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: any) => toast.error("Failed", { description: e.response?.data?.message })
  });

  const resumeMutation = useMutation({
    mutationFn: () => api.post('/user/subscription/resume'),
    onSuccess: () => {
      toast.success("Welcome Back!", { description: "Subscription resumed." });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: any) => toast.error("Failed", { description: e.response?.data?.message })
  });

  const cancelMutation = useMutation({
    mutationFn: (reason: string) => api.post('/user/subscription/cancel', { reason }),
    onSuccess: (data) => {
      toast.success("Subscription Cancelled", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: any) => toast.error("Failed", { description: e.response?.data?.message })
  });

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Manage Subscription</DialogTitle>
          <DialogDescription>Modify your plan, pause payments, or cancel.</DialogDescription>
        </DialogHeader>

        {status === 'paused' ? (
           <div className="py-4 text-center space-y-4">
             <div className="bg-yellow-50 p-4 rounded-lg border border-yellow-200 text-yellow-800">
               <p className="font-medium">Your subscription is currently paused.</p>
               <p className="text-sm">Resume to continue earning bonuses.</p>
             </div>
             <Button onClick={() => resumeMutation.mutate()} disabled={resumeMutation.isPending} className="w-full">
               {resumeMutation.isPending ? "Resuming..." : "Resume Subscription"}
             </Button>
           </div>
        ) : (
            <Tabs defaultValue="change" className="w-full">
            <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="change">Change Plan</TabsTrigger>
                <TabsTrigger value="pause">Pause</TabsTrigger>
                <TabsTrigger value="cancel">Cancel</TabsTrigger>
            </TabsList>

            {/* Change Plan Tab */}
            <TabsContent value="change" className="space-y-4 py-4">
                <div className="space-y-2">
                <Label>Select New Plan</Label>
                <Select value={selectedPlan} onValueChange={setSelectedPlan}>
                    <SelectTrigger><SelectValue placeholder="Choose a plan" /></SelectTrigger>
                    <SelectContent>
                    {plans.filter(p => p.id !== currentPlanId).map((p: any) => (
                        <SelectItem key={p.id} value={p.id.toString()}>
                        {p.name} (₹{p.monthly_amount}/mo)
                        </SelectItem>
                    ))}
                    </SelectContent>
                </Select>
                </div>
                <Button onClick={() => changePlanMutation.mutate(selectedPlan)} disabled={!selectedPlan || changePlanMutation.isPending} className="w-full">
                <ArrowUpCircle className="mr-2 h-4 w-4" /> Update Plan
                </Button>
            </TabsContent>

            {/* Pause Tab */}
            <TabsContent value="pause" className="space-y-4 py-4">
                <div className="bg-blue-50 p-3 rounded text-sm text-blue-800">
                Pausing stops payments for a selected duration. You won't earn bonuses while paused.
                </div>
                <div className="space-y-2">
                <Label>Pause Duration</Label>
                <Select value={pauseMonths} onValueChange={setPauseMonths}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                    <SelectItem value="1">1 Month</SelectItem>
                    <SelectItem value="2">2 Months</SelectItem>
                    <SelectItem value="3">3 Months</SelectItem>
                    </SelectContent>
                </Select>
                </div>
                <Button onClick={() => pauseMutation.mutate(pauseMonths)} disabled={pauseMutation.isPending} variant="secondary" className="w-full">
                <PauseCircle className="mr-2 h-4 w-4" /> Pause Subscription
                </Button>
            </TabsContent>

            {/* Cancel Tab */}
            <TabsContent value="cancel" className="space-y-4 py-4">
                <div className="bg-destructive/10 p-3 rounded text-sm text-destructive flex items-start gap-2">
                <AlertTriangle className="h-5 w-5 shrink-0" />
                <div>
                    <p className="font-bold">Warning:</p>
                    <p>Cancelling stops all future bonuses. Your existing portfolio will remain yours, but you forfeit future milestone rewards.</p>
                </div>
                </div>
                <div className="space-y-2">
                <Label>Reason for Cancellation</Label>
                <Textarea value={cancelReason} onChange={e => setCancelReason(e.target.value)} placeholder="Why are you leaving?" />
                </div>
                <Button onClick={() => cancelMutation.mutate(cancelReason)} disabled={!cancelReason || cancelMutation.isPending} variant="destructive" className="w-full">
                <XCircle className="mr-2 h-4 w-4" /> Confirm Cancellation
                </Button>
            </TabsContent>
            </Tabs>
        )}
      </DialogContent>
    </Dialog>
  );
}