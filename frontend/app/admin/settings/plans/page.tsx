// V-REMEDIATE-1730-131 (Created - Revised) | V-FINAL-1730-484 (Scheduling UI)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, PlusCircle, Edit, Trash2 } from "lucide-react";

// Helper to format date for input
const formatDateForInput = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};

export default function PlanManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<any>(null);

  // Form State
  const [name, setName] = useState('');
  const [monthlyAmount, setMonthlyAmount] = useState('');
  const [duration, setDuration] = useState('36');
  const [description, setDescription] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [isFeatured, setIsFeatured] = useState(false);
  
  // --- NEW: Date States ---
  const [availableFrom, setAvailableFrom] = useState('');
  const [availableUntil, setAvailableUntil] = useState('');

  const { data: plans, isLoading } = useQuery({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  const mutation = useMutation({
    mutationFn: (newPlan: any) => {
      if (editingPlan) {
        return api.put(`/admin/plans/${editingPlan.id}`, newPlan);
      }
      return api.post('/admin/plans', newPlan);
    },
    onSuccess: () => {
      toast.success(editingPlan ? "Plan Updated" : "Plan Created");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const resetForm = () => {
    setName(''); setMonthlyAmount(''); setDuration('36'); setDescription('');
    setIsActive(true); setIsFeatured(false); setEditingPlan(null);
    setAvailableFrom(''); setAvailableUntil('');
  };

  const handleEdit = (plan: any) => {
    setEditingPlan(plan);
    setName(plan.name);
    setMonthlyAmount(plan.monthly_amount);
    setDuration(plan.duration_months);
    setDescription(plan.description);
    setIsActive(plan.is_active);
    setIsFeatured(plan.is_featured);
    // --- NEW: Set Dates ---
    setAvailableFrom(formatDateForInput(plan.available_from));
    setAvailableUntil(formatDateForInput(plan.available_until));
    
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
        name,
        monthly_amount: parseFloat(monthlyAmount),
        duration_months: parseInt(duration),
        description,
        is_active: isActive,
        is_featured: isFeatured,
        available_from: availableFrom || null, // Send null if empty
        available_until: availableUntil || null,
        // We'll edit configs on the Bonus Config page
    };
    mutation.mutate(payload);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Plan Management</h1>
          <p className="text-muted-foreground">Create and manage all investment plans.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create Plan</Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{editingPlan ? 'Edit Plan' : 'Create New Plan'}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Plan Name</Label>
                <Input value={name} onChange={(e) => setName(e.target.value)} required />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Monthly Amount (₹)</Label>
                  <Input type="number" value={monthlyAmount} onChange={(e) => setMonthlyAmount(e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>Duration (Months)</Label>
                  <Input type="number" value={duration} onChange={(e) => setDuration(e.target.value)} required />
                </div>
              </div>
              
              {/* --- NEW: Date Pickers --- */}
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Available From (Optional)</Label>
                  <Input type="date" value={availableFrom} onChange={(e) => setAvailableFrom(e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Available Until (Optional)</Label>
                  <Input type="date" value={availableUntil} onChange={(e) => setAvailableUntil(e.target.value)} />
                </div>
              </div>
              {/* ------------------------- */}

              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea value={description} onChange={(e) => setDescription(e.target.value)} />
              </div>
              <div className="flex items-center gap-6">
                <div className="flex items-center space-x-2">
                  <Switch id="is_active" checked={isActive} onCheckedChange={setIsActive} />
                  <Label htmlFor="is_active">Active</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch id="is_featured" checked={isFeatured} onCheckedChange={setIsFeatured} />
                  <Label htmlFor="is_featured">Featured</Label>
                </div>
              </div>
              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingPlan ? "Save Changes" : "Create Plan")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Amount (Monthly)</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Availability</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {plans?.map((plan: any) => (
                  <TableRow key={plan.id}>
                    <TableCell className="font-medium">{plan.name}</TableCell>
                    <TableCell>₹{plan.monthly_amount}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        plan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                      }`}>
                        {plan.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground">
                      {plan.available_from ? `${formatDateForInput(plan.available_from)} to ${formatDateForInput(plan.available_until) || '...'}` : 'Always'}
                    </TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(plan)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}