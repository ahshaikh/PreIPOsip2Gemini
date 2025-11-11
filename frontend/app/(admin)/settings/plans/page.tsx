// V-PHASE6-1730-131
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/components/ui/use-toast";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";

export default function PlansSettingsPage() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  
  // State for a new plan form
  const [newPlan, setNewPlan] = useState({
    name: '',
    monthly_amount: 1000,
    duration_months: 36,
    description: '',
    is_active: true,
    is_featured: false,
  });

  const { data: plans, isLoading } = useQuery({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  const mutation = useMutation({
    mutationFn: (planData: any) => api.post('/admin/plans', planData),
    onSuccess: () => {
      toast({ title: "Plan Created!" });
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
    }
  });

  const handleCreate = () => {
    mutation.mutate(newPlan);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Card className="md:col-span-1">
        <CardHeader>
          <CardTitle>Create New Plan</CardTitle>
          <CardDescription>Create a new investment plan.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>Plan Name</Label>
            <Input value={newPlan.name} onChange={(e) => setNewPlan({...newPlan, name: e.target.value})} />
          </div>
          <div className="space-y-2">
            <Label>Monthly Amount</Label>
            <Input type="number" value={newPlan.monthly_amount} onChange={(e) => setNewPlan({...newPlan, monthly_amount: parseFloat(e.target.value)})} />
          </div>
          <div className="space-y-2">
            <Label>Description</Label>
            <Textarea value={newPlan.description} onChange={(e) => setNewPlan({...newPlan, description: e.target.value})} />
          </div>
          <div className="flex items-center justify-between">
            <Label>Active</Label>
            <Switch checked={newPlan.is_active} onCheckedChange={(c) => setNewPlan({...newPlan, is_active: c})} />
          </div>
          <div className="flex items-center justify-between">
            <Label>Featured</Label>
            <Switch checked={newPlan.is_featured} onCheckedChange={(c) => setNewPlan({...newPlan, is_featured: c})} />
          </div>
          <Button onClick={handleCreate} disabled={mutation.isPending}>
            {mutation.isPending ? "Creating..." : "Create Plan"}
          </Button>
        </CardContent>
      </Card>

      <Card className="md:col-span-2">
        <CardHeader>
          <CardTitle>Existing Plans</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading plans...</p> : (
            <div className="space-y-4">
              {plans?.map((plan: any) => (
                <div key={plan.id} className="border p-4 rounded-lg flex justify-between items-center">
                  <div>
                    <h4 className="font-semibold">{plan.name}</h4>
                    <p className="text-sm text-muted-foreground">₹{plan.monthly_amount}/month</p>
                  </div>
                  <Button variant="outline" size="sm">Edit</Button>
                  {/* TODO: Add logic to edit configs, features, etc. */}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}