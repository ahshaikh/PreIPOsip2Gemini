// V-FINAL-1730-263 (Created) | V-FINAL-1730-580 (Refund Info)
// V-ARCH-2026: Typed with canonical Subscription and Plan types
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
import type { PlanWithRelations } from "@/types/plan";
import type {
  SubscriptionWithRelations,
  ChangeSubscriptionPlanPayload,
  PauseSubscriptionPayload,
  CancelSubscriptionPayload,
  ApiError,
} from "@/types/subscription";

interface ManageSubModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentPlanId: number;
  plans: PlanWithRelations[];
  status: string;
}

export function ManageSubscriptionModal({ isOpen, onClose, currentPlanId, plans, status }: ManageSubModalProps) {
  const queryClient = useQueryClient();
  const [selectedPlan, setSelectedPlan] = useState<string>("");
  const [pauseMonths, setPauseMonths] = useState("1");
  const [cancelReason, setCancelReason] = useState("");

  // Mutations (Strictly Typed)
  const changePlanMutation = useMutation<
    { data: SubscriptionWithRelations; message: string },
    ApiError,
    string
  >({
    mutationFn: async (id: string) => {
      const response = await api.post<{ data: SubscriptionWithRelations; message: string }>(
        '/user/subscription/change-plan',
        { new_plan_id: parseInt(id, 10) } as ChangeSubscriptionPlanPayload
      );
      return response.data;
    },
    onSuccess: (data) => {
      toast.success("Plan Changed", { description: data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: ApiError) => toast.error("Failed", { description: e.message })
  });

  const pauseMutation = useMutation<
    { data: SubscriptionWithRelations; message: string },
    ApiError,
    string
  >({
    mutationFn: async (months: string) => {
      const response = await api.post<{ data: SubscriptionWithRelations; message: string }>(
        '/user/subscription/pause',
        { months } as PauseSubscriptionPayload
      );
      return response.data;
    },
    onSuccess: (data) => {
      toast.success("Subscription Paused", { description: data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: ApiError) => toast.error("Failed", { description: e.message })
  });

  const resumeMutation = useMutation<
    { data: SubscriptionWithRelations; message: string },
    ApiError
  >({
    mutationFn: async () => {
      const response = await api.post<{ data: SubscriptionWithRelations; message: string }>(
        '/user/subscription/resume'
      );
      return response.data;
    },
    onSuccess: () => {
      toast.success("Welcome Back!", { description: "Subscription resumed." });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: ApiError) => toast.error("Failed", { description: e.message })
  });

  const cancelMutation = useMutation<
    { data: SubscriptionWithRelations; message: string },
    ApiError,
    string
  >({
    mutationFn: async (reason: string) => {
      const response = await api.post<{ data: SubscriptionWithRelations; message: string }>(
        '/user/subscription/cancel',
        { reason } as CancelSubscriptionPayload
      );
      return response.data;
    },
    onSuccess: (data) => {
      toast.success("Subscription Cancelled", { description: data.message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: ApiError) => toast.error("Failed", { description: e.message })
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
                {/* ✅ CORRECT: Using plan.name and plan.monthly_amount for display only */}
                <Select value={selectedPlan} onValueChange={setSelectedPlan}>
                    <SelectTrigger><SelectValue placeholder="Choose a plan" /></SelectTrigger>
                    <SelectContent>
                    {plans.filter(p => p.id !== currentPlanId).map((p: PlanWithRelations) => (
                        <SelectItem key={p.id} value={p.id.toString()}>
                        {p.name} (₹{p.monthly_amount}/mo)
                        </SelectItem>
                    ))}
                    </SelectContent>
                </Select>
                </div>
                <Button onClick={() => changePlanMutation.mutate(selectedPlan)} disabled={!selectedPlan || changePlanMutation.isPending} className="w-full">
                <ArrowUpCircle className="mr-2 h-4 w-4" /> 
                {changePlanMutation.isPending ? "Updating..." : "Update Plan"}
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
                <PauseCircle className="mr-2 h-4 w-4" />
                {pauseMutation.isPending ? "Pausing..." : "Pause Subscription"}
                </Button>
            </TabsContent>

            {/* Cancel Tab */}
            <TabsContent value="cancel" className="space-y-4 py-4">
                <div className="bg-destructive/10 p-3 rounded text-sm text-destructive flex items-start gap-2">
                <AlertTriangle className="h-5 w-5 shrink-0" />
                <div>
                    <p className="font-bold">Warning:</p>
                    <p>Cancelling stops all future bonuses. You may be eligible for a pro-rata refund if you are within 7 days of your first payment.</p>
                </div>
                </div>
                <div className="space-y-2">
                <Label>Reason for Cancellation</Label>
                <Textarea value={cancelReason} onChange={e => setCancelReason(e.target.value)} placeholder="Why are you leaving?" />
                </div>
                <Button onClick={() => cancelMutation.mutate(cancelReason)} disabled={!cancelReason || cancelMutation.isPending} variant="destructive" className="w-full">
                <XCircle className="mr-2 h-4 w-4" />
                {cancelMutation.isPending ? "Cancelling..." : "Confirm Cancellation"}
                </Button>
            </TabsContent>
            </Tabs>
        )}
      </DialogContent>
    </Dialog>
  );
}