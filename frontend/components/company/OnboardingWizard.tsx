'use client';

import { useState, useEffect } from "react";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  CheckCircle2,
  Circle,
  ChevronRight,
  X,
  Lightbulb,
  ArrowRight,
  Building2,
  Upload,
  Users,
  FileText,
  DollarSign,
  TrendingUp,
  MessageSquare,
  Shield
} from "lucide-react";
import Link from "next/link";
import { toast } from "sonner";

interface OnboardingStep {
  id: string;
  title: string;
  description: string;
  status: boolean;
  action: string;
  order: number;
}

interface OnboardingProgress {
  id: number;
  company_id: number;
  completed_steps: string[];
  current_step: number;
  total_steps: number;
  completion_percentage: number;
  is_completed: boolean;
  started_at: string;
  completed_at: string | null;
}

interface Recommendation {
  type: string;
  priority: 'high' | 'medium' | 'low';
  title: string;
  description: string;
  action: string;
}

interface OnboardingWizardProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  autoShow?: boolean;
}

const stepIcons: Record<string, any> = {
  profile_basic: Building2,
  profile_branding: Upload,
  team_members: Users,
  financial_reports: FileText,
  documents: FileText,
  funding_rounds: DollarSign,
  company_updates: MessageSquare,
  verification: Shield,
};

const priorityColors = {
  high: 'bg-red-100 text-red-800 border-red-200',
  medium: 'bg-orange-100 text-orange-800 border-orange-200',
  low: 'bg-blue-100 text-blue-800 border-blue-200',
};

export default function OnboardingWizard({ open, onOpenChange, autoShow = false }: OnboardingWizardProps) {
  const [showRecommendations, setShowRecommendations] = useState(false);
  const queryClient = useQueryClient();

  // Fetch onboarding progress
  const { data: progressData, isLoading } = useQuery({
    queryKey: ['onboardingProgress'],
    queryFn: async () => {
      const { data } = await api.get('/company/onboarding/progress');
      return data;
    },
    enabled: open,
  });

  const progress: OnboardingProgress = progressData?.progress;
  const steps: OnboardingStep[] = progressData?.steps || [];

  // Fetch recommendations
  const { data: recommendationsData } = useQuery({
    queryKey: ['onboardingRecommendations'],
    queryFn: async () => {
      const { data } = await api.get('/company/onboarding/recommendations');
      return data;
    },
    enabled: showRecommendations,
  });

  const recommendations: Recommendation[] = recommendationsData?.recommendations || [];

  // Complete step mutation
  const completeStepMutation = useMutation({
    mutationFn: async (stepId: string) => {
      const { data } = await api.post('/company/onboarding/complete-step', { step_id: stepId });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['onboardingProgress']);
      toast.success('Step marked as completed!');
    },
    onError: () => {
      toast.error('Failed to update progress');
    },
  });

  // Skip onboarding mutation
  const skipMutation = useMutation({
    mutationFn: async () => {
      const { data } = await api.post('/company/onboarding/skip');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['onboardingProgress']);
      toast.success('Onboarding skipped. You can access it anytime from your dashboard.');
      onOpenChange(false);
    },
    onError: () => {
      toast.error('Failed to skip onboarding');
    },
  });

  // Auto-show logic: show wizard if onboarding is not complete
  useEffect(() => {
    if (autoShow && progress && !progress.is_completed && progress.completion_percentage < 100) {
      const hasShownToday = localStorage.getItem('onboarding_shown_date');
      const today = new Date().toDateString();

      if (hasShownToday !== today) {
        onOpenChange(true);
        localStorage.setItem('onboarding_shown_date', today);
      }
    }
  }, [autoShow, progress, onOpenChange]);

  const handleStepClick = (step: OnboardingStep) => {
    if (step.action) {
      onOpenChange(false);
      // Navigation will be handled by Link component
    }
  };

  const handleSkip = () => {
    if (confirm('Are you sure you want to skip the onboarding process? You can always access it later from your dashboard.')) {
      skipMutation.mutate();
    }
  };

  const completedSteps = steps.filter(s => s.status).length;
  const pendingSteps = steps.filter(s => !s.status);
  const nextStep = pendingSteps[0];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <div className="flex items-center justify-between">
            <div>
              <DialogTitle className="text-2xl">Complete Your Company Profile</DialogTitle>
              <DialogDescription className="mt-2">
                Follow these steps to set up your company profile and start engaging with investors
              </DialogDescription>
            </div>
            <Button variant="ghost" size="icon" onClick={() => onOpenChange(false)}>
              <X className="w-4 h-4" />
            </Button>
          </div>
        </DialogHeader>

        {isLoading ? (
          <div className="py-12 text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
            <p className="mt-4 text-muted-foreground">Loading your progress...</p>
          </div>
        ) : progress ? (
          <div className="space-y-6">
            {/* Progress Overview */}
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Your Progress</CardTitle>
                    <CardDescription>
                      {completedSteps} of {steps.length} steps completed
                    </CardDescription>
                  </div>
                  <div className="text-right">
                    <div className="text-3xl font-bold text-primary">
                      {Math.round(progress.completion_percentage)}%
                    </div>
                    <p className="text-sm text-muted-foreground">Complete</p>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <Progress value={progress.completion_percentage} className="h-3" />
              </CardContent>
            </Card>

            {/* Next Step Highlight */}
            {nextStep && !progress.is_completed && (
              <Card className="border-2 border-primary bg-primary/5">
                <CardHeader>
                  <div className="flex items-start gap-4">
                    <div className="p-3 bg-primary/10 rounded-lg">
                      <ArrowRight className="w-6 h-6 text-primary" />
                    </div>
                    <div className="flex-1">
                      <CardTitle className="text-lg">Next Step: {nextStep.title}</CardTitle>
                      <CardDescription className="mt-1">{nextStep.description}</CardDescription>
                      <Link href={nextStep.action}>
                        <Button className="mt-4" onClick={() => onOpenChange(false)}>
                          Continue <ChevronRight className="w-4 h-4 ml-2" />
                        </Button>
                      </Link>
                    </div>
                  </div>
                </CardHeader>
              </Card>
            )}

            {/* All Steps */}
            <div>
              <h3 className="text-lg font-semibold mb-4">Onboarding Checklist</h3>
              <div className="space-y-2">
                {steps.map((step, index) => {
                  const Icon = stepIcons[step.id] || Circle;
                  return (
                    <Link key={step.id} href={step.action}>
                      <div
                        className={`
                          flex items-center gap-4 p-4 rounded-lg border cursor-pointer
                          transition-all hover:shadow-md
                          ${step.status ? 'bg-green-50 border-green-200' : 'bg-white hover:bg-gray-50'}
                        `}
                        onClick={() => handleStepClick(step)}
                      >
                        <div className="flex-shrink-0">
                          {step.status ? (
                            <div className="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                              <CheckCircle2 className="w-6 h-6 text-white" />
                            </div>
                          ) : (
                            <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                              <Icon className="w-5 h-5 text-gray-500" />
                            </div>
                          )}
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <h4 className="font-semibold">{step.title}</h4>
                            {step.status && (
                              <Badge variant="secondary" className="bg-green-100 text-green-800">
                                Complete
                              </Badge>
                            )}
                          </div>
                          <p className="text-sm text-muted-foreground">{step.description}</p>
                        </div>
                        <ChevronRight className="w-5 h-5 text-muted-foreground" />
                      </div>
                    </Link>
                  );
                })}
              </div>
            </div>

            {/* Recommendations */}
            {!showRecommendations ? (
              <Button
                variant="outline"
                className="w-full"
                onClick={() => setShowRecommendations(true)}
              >
                <Lightbulb className="w-4 h-4 mr-2" />
                Show Recommendations
              </Button>
            ) : (
              <div>
                <h3 className="text-lg font-semibold mb-4">Recommendations for You</h3>
                <div className="space-y-3">
                  {recommendations.length > 0 ? (
                    recommendations.map((rec, index) => (
                      <Card key={index} className={`border ${priorityColors[rec.priority]}`}>
                        <CardHeader className="pb-3">
                          <div className="flex items-start justify-between">
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-1">
                                <CardTitle className="text-base">{rec.title}</CardTitle>
                                <Badge variant="outline" className="text-xs">
                                  {rec.priority} priority
                                </Badge>
                              </div>
                              <CardDescription>{rec.description}</CardDescription>
                            </div>
                          </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                          <Link href={rec.action}>
                            <Button variant="outline" size="sm" onClick={() => onOpenChange(false)}>
                              Take Action
                            </Button>
                          </Link>
                        </CardContent>
                      </Card>
                    ))
                  ) : (
                    <Card>
                      <CardContent className="py-8 text-center">
                        <CheckCircle2 className="w-12 h-12 text-green-500 mx-auto mb-3" />
                        <p className="text-muted-foreground">
                          Great job! You're all set. No recommendations at this time.
                        </p>
                      </CardContent>
                    </Card>
                  )}
                </div>
              </div>
            )}

            {/* Completion Message */}
            {progress.is_completed && (
              <Card className="border-2 border-green-500 bg-green-50">
                <CardContent className="py-6 text-center">
                  <CheckCircle2 className="w-16 h-16 text-green-500 mx-auto mb-4" />
                  <h3 className="text-2xl font-bold mb-2">Congratulations! 🎉</h3>
                  <p className="text-muted-foreground">
                    You've completed all onboarding steps. Your company profile is ready to attract investors!
                  </p>
                </CardContent>
              </Card>
            )}
          </div>
        ) : (
          <div className="py-12 text-center text-muted-foreground">
            Unable to load onboarding progress
          </div>
        )}

        <DialogFooter className="flex items-center justify-between">
          <Button
            variant="ghost"
            onClick={handleSkip}
            disabled={skipMutation.isLoading || progress?.is_completed}
          >
            {skipMutation.isLoading ? 'Skipping...' : 'Skip for now'}
          </Button>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Close
            </Button>
            {nextStep && (
              <Link href={nextStep.action}>
                <Button onClick={() => onOpenChange(false)}>
                  Continue to Next Step
                  <ChevronRight className="w-4 h-4 ml-2" />
                </Button>
              </Link>
            )}
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
