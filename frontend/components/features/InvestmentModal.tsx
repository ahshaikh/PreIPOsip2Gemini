'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { TrendingUp, AlertTriangle, Loader2, CheckCircle2, Tag, Gift } from "lucide-react";

interface InvestmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  deal: any;
}

export function InvestmentModal({ isOpen, onClose, deal }: InvestmentModalProps) {
  const queryClient = useQueryClient();
  const [shares, setShares] = useState(1);
  const [selectedSubscription, setSelectedSubscription] = useState<string>("");
  const [campaignCode, setCampaignCode] = useState("");
  const [validatedCampaign, setValidatedCampaign] = useState<any>(null);
  const [isValidating, setIsValidating] = useState(false);
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [disclaimerAcknowledged, setDisclaimerAcknowledged] = useState(false);

  // Fetch deal details with user subscriptions
  const { data: dealResponse, isLoading: isDealLoading } = useQuery({
    queryKey: ['dealDetails', deal.id],
    queryFn: async () => (await api.get(`/user/deals/${deal.id}`)).data,
    enabled: isOpen,
  });

  const subscriptions = dealResponse?.user_subscriptions || [];
  const minShares = Math.ceil(deal.min_investment / deal.share_price);
  const totalAmount = shares * deal.share_price;
  const discount = validatedCampaign?.discount || 0;
  const finalAmount = totalAmount - discount;

  // Auto-select first subscription if only one available
  useEffect(() => {
    if (subscriptions.length === 1 && !selectedSubscription) {
      setSelectedSubscription(subscriptions[0].id.toString());
    }
  }, [subscriptions, selectedSubscription]);

  // Set minimum shares on load
  useEffect(() => {
    setShares(minShares);
  }, [minShares]);

  // Reset campaign validation when code changes
  useEffect(() => {
    setValidatedCampaign(null);
    setTermsAccepted(false);
    setDisclaimerAcknowledged(false);
  }, [campaignCode]);

  // Validate campaign code
  const validateCampaign = async () => {
    if (!campaignCode.trim()) {
      setValidatedCampaign(null);
      return;
    }

    setIsValidating(true);
    try {
      const response = await api.post('/campaigns/validate', {
        code: campaignCode.trim(),
        amount: totalAmount,
      });

      if (response.data.valid) {
        setValidatedCampaign(response.data);
        toast.success("Campaign Applied!", {
          description: `You'll save ₹${response.data.discount.toLocaleString('en-IN')}`,
        });
      } else {
        setValidatedCampaign(null);
        toast.error("Invalid Campaign", {
          description: response.data.message || "This campaign code is not valid",
        });
      }
    } catch (error: any) {
      setValidatedCampaign(null);
      toast.error("Campaign Validation Failed", {
        description: error.response?.data?.message || "Please check the code and try again",
      });
    } finally {
      setIsValidating(false);
    }
  };

  const createInvestmentMutation = useMutation({
    mutationFn: (data: any) => api.post('/user/investments', data),
    onSuccess: (response) => {
      const savedAmount = response.data.discount_applied || 0;
      toast.success("Investment Created!", {
        description: savedAmount > 0
          ? `You've invested ₹${finalAmount.toLocaleString('en-IN')} in ${deal.company_name} (Saved ₹${savedAmount.toLocaleString('en-IN')})`
          : `You've invested ₹${totalAmount.toLocaleString('en-IN')} in ${deal.company_name}`,
        duration: 5000,
      });
      queryClient.invalidateQueries({ queryKey: ['userDeals'] });
      queryClient.invalidateQueries({ queryKey: ['userInvestments'] });
      queryClient.invalidateQueries({ queryKey: ['portfolio'] });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      onClose();
    },
    onError: (e: any) => {
      toast.error("Investment Failed", {
        description: e.response?.data?.message || "Please try again",
      });
    },
  });

  const handleConfirm = () => {
    if (!selectedSubscription) {
      toast.error("Please select a subscription plan");
      return;
    }

    if (shares < minShares) {
      toast.error(`Minimum ${minShares} shares required (₹${deal.min_investment.toLocaleString('en-IN')})`);
      return;
    }

    // Enforce terms acceptance for campaigns
    if (validatedCampaign && (!termsAccepted || !disclaimerAcknowledged)) {
      toast.error("Please accept campaign terms", {
        description: "You must accept the terms and acknowledge disclaimers to use this campaign",
      });
      return;
    }

    const payload: any = {
      deal_id: deal.id,
      subscription_id: selectedSubscription,
      shares_allocated: shares,
    };

    // Add campaign fields only if campaign is validated and terms accepted
    if (validatedCampaign && termsAccepted && disclaimerAcknowledged) {
      payload.campaign_code = campaignCode.trim();
      payload.campaign_terms_accepted = true;
      payload.campaign_disclaimer_acknowledged = true;
    }

    createInvestmentMutation.mutate(payload);
  };

  const selectedSub = subscriptions.find((s: any) => s.id.toString() === selectedSubscription);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const isConfirmDisabled =
    !selectedSubscription ||
    shares < minShares ||
    finalAmount > (selectedSub?.available_balance || 0) ||
    createInvestmentMutation.isPending ||
    (validatedCampaign && (!termsAccepted || !disclaimerAcknowledged));

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl">Invest in {deal.company_name}</DialogTitle>
          <DialogDescription>{deal.title}</DialogDescription>
        </DialogHeader>

        {isDealLoading ? (
          <div className="py-8 text-center">
            <Loader2 className="w-8 h-8 animate-spin mx-auto text-primary" />
            <p className="text-sm text-muted-foreground mt-2">Loading investment details...</p>
          </div>
        ) : (
          <div className="space-y-6 py-4">
            {/* No Subscriptions Warning */}
            {subscriptions.length === 0 ? (
              <div className="bg-destructive/10 p-4 rounded-lg border border-destructive/20">
                <div className="flex items-start gap-2">
                  <AlertTriangle className="w-5 h-5 text-destructive mt-0.5" />
                  <div>
                    <p className="font-semibold text-destructive">No Active Subscription</p>
                    <p className="text-sm text-muted-foreground mt-1">
                      You need an active subscription plan to invest. Please subscribe first.
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <>
                {/* Subscription Selection */}
                <div className="space-y-2">
                  <Label>Select Subscription Plan</Label>
                  <Select value={selectedSubscription} onValueChange={setSelectedSubscription}>
                    <SelectTrigger>
                      <SelectValue placeholder="Choose a subscription" />
                    </SelectTrigger>
                    <SelectContent>
                      {subscriptions.map((sub: any) => (
                        <SelectItem key={sub.id} value={sub.id.toString()}>
                          <div className="flex items-center justify-between w-full">
                            <span>{sub.plan.name}</span>
                            <span className="text-xs text-muted-foreground ml-2">
                              Available: {formatCurrency(sub.available_balance)}
                            </span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Shares Input */}
                <div className="space-y-2">
                  <Label>Number of Shares</Label>
                  <Input
                    type="number"
                    min={minShares}
                    max={deal.remaining_shares || deal.available_shares}
                    value={shares}
                    onChange={(e) => setShares(parseInt(e.target.value) || minShares)}
                  />
                  <p className="text-xs text-muted-foreground">
                    Minimum: {minShares} shares ({formatCurrency(deal.min_investment)}) |
                    Available: {(deal.remaining_shares || deal.available_shares).toLocaleString('en-IN')} shares
                  </p>
                </div>

                {/* Campaign Code Input */}
                <div className="space-y-2">
                  <Label htmlFor="campaign-code">Campaign Code (Optional)</Label>
                  <div className="flex gap-2">
                    <div className="relative flex-1">
                      <Tag className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="campaign-code"
                        placeholder="Enter campaign code"
                        className="pl-9"
                        value={campaignCode}
                        onChange={(e) => setCampaignCode(e.target.value.toUpperCase())}
                        onKeyPress={(e) => {
                          if (e.key === 'Enter') {
                            e.preventDefault();
                            validateCampaign();
                          }
                        }}
                      />
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      onClick={validateCampaign}
                      disabled={!campaignCode.trim() || isValidating}
                    >
                      {isValidating ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        "Apply"
                      )}
                    </Button>
                  </div>
                  {validatedCampaign && (
                    <div className="flex items-start gap-2 bg-green-50 dark:bg-green-900/20 p-3 rounded text-sm text-green-700 dark:text-green-400">
                      <Gift className="w-4 h-4 mt-0.5" />
                      <p>Campaign "{validatedCampaign.campaign.title}" applied! Save ₹{validatedCampaign.discount.toLocaleString('en-IN')}</p>
                    </div>
                  )}
                </div>

                {/* Campaign Terms Acceptance */}
                {validatedCampaign && (
                  <div className="space-y-3 border border-primary/20 bg-primary/5 p-4 rounded-lg">
                    <p className="text-sm font-semibold">Campaign Terms & Compliance</p>
                    <div className="flex items-start gap-2">
                      <Checkbox
                        id="terms-accepted"
                        checked={termsAccepted}
                        onCheckedChange={(checked) => setTermsAccepted(checked as boolean)}
                      />
                      <label htmlFor="terms-accepted" className="text-sm cursor-pointer">
                        I accept the campaign terms and conditions
                      </label>
                    </div>
                    <div className="flex items-start gap-2">
                      <Checkbox
                        id="disclaimer-acknowledged"
                        checked={disclaimerAcknowledged}
                        onCheckedChange={(checked) => setDisclaimerAcknowledged(checked as boolean)}
                      />
                      <label htmlFor="disclaimer-acknowledged" className="text-sm cursor-pointer">
                        I acknowledge the regulatory disclaimers (SEBI/RBI compliance)
                      </label>
                    </div>
                    {(!termsAccepted || !disclaimerAcknowledged) && (
                      <p className="text-xs text-destructive">
                        Both checkboxes must be checked to proceed with campaign discount
                      </p>
                    )}
                  </div>
                )}

                {/* Investment Summary */}
                <div className="bg-muted p-4 rounded-lg space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Share Price</span>
                    <span className="font-semibold">{formatCurrency(deal.share_price)}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span>Shares</span>
                    <span className="font-semibold">{shares}</span>
                  </div>
                  {validatedCampaign && (
                    <>
                      <div className="h-px bg-border my-2" />
                      <div className="flex justify-between text-sm">
                        <span>Subtotal</span>
                        <span>{formatCurrency(totalAmount)}</span>
                      </div>
                      <div className="flex justify-between text-sm text-green-600 dark:text-green-400">
                        <span>Campaign Discount</span>
                        <span>- {formatCurrency(discount)}</span>
                      </div>
                    </>
                  )}
                  <div className="h-px bg-border my-2" />
                  <div className="flex justify-between text-lg">
                    <span className="font-bold">Total Investment</span>
                    <span className="font-bold text-primary">{formatCurrency(finalAmount)}</span>
                  </div>
                  {selectedSub && (
                    <>
                      <div className="flex justify-between text-sm text-muted-foreground">
                        <span>Available Balance</span>
                        <span>{formatCurrency(selectedSub.available_balance)}</span>
                      </div>
                      {finalAmount > selectedSub.available_balance && (
                        <div className="flex items-center gap-2 bg-destructive/10 p-2 rounded text-sm text-destructive mt-2">
                          <AlertTriangle className="w-4 h-4" />
                          <span>Insufficient balance in selected subscription</span>
                        </div>
                      )}
                    </>
                  )}
                </div>

                {/* Validation Warnings */}
                {shares < minShares && (
                  <div className="flex items-start gap-2 bg-amber-50 dark:bg-amber-900/20 p-3 rounded text-sm text-amber-800 dark:text-amber-400">
                    <AlertTriangle className="w-4 h-4 mt-0.5" />
                    <p>Minimum investment of {formatCurrency(deal.min_investment)} required ({minShares} shares)</p>
                  </div>
                )}

                {finalAmount <= (selectedSub?.available_balance || 0) && shares >= minShares && !isConfirmDisabled && (
                  <div className="flex items-start gap-2 bg-green-50 dark:bg-green-900/20 p-3 rounded text-sm text-green-700 dark:text-green-400">
                    <CheckCircle2 className="w-4 h-4 mt-0.5" />
                    <p>Investment ready to be confirmed{validatedCampaign ? ' with campaign discount' : ''}</p>
                  </div>
                )}

                <Button
                  onClick={handleConfirm}
                  disabled={isConfirmDisabled}
                  className="w-full"
                  size="lg"
                >
                  {createInvestmentMutation.isPending ? (
                    <>
                      <Loader2 className="w-4 w-4 mr-2 animate-spin" />
                      Confirming Investment...
                    </>
                  ) : (
                    `Confirm Investment ${validatedCampaign ? `(Save ₹${discount.toLocaleString('en-IN')})` : ''}`
                  )}
                </Button>
              </>
            )}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
