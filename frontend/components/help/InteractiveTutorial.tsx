'use client';

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  X,
  ChevronLeft,
  ChevronRight,
  Check,
  Play,
  Pause,
  SkipForward,
  Lightbulb
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

interface TutorialStep {
  id: number;
  stepNumber: number;
  title: string;
  content: string;
  targetElement?: string; // CSS selector
  highlightStyle?: 'pulse' | 'glow' | 'border' | 'none';
  position?: 'top' | 'bottom' | 'left' | 'right' | 'center' | 'modal';
  imageUrl?: string;
  videoUrl?: string;
  gifUrl?: string;
  requiresAction?: boolean;
  actionType?: string;
  canSkip?: boolean;
  nextButtonText?: string;
  backButtonText?: string;
}

interface Tutorial {
  id: number;
  slug: string;
  title: string;
  description: string;
  steps: TutorialStep[];
  estimatedMinutes: number;
}

interface InteractiveTutorialProps {
  tutorial: Tutorial;
  isOpen: boolean;
  onClose: () => void;
  onComplete?: () => void;
}

export default function InteractiveTutorial({
  tutorial,
  isOpen,
  onClose,
  onComplete
}: InteractiveTutorialProps) {
  const [currentStep, setCurrentStep] = useState(0);
  const [completedSteps, setCompletedSteps] = useState<number[]>([]);
  const [highlightedElement, setHighlightedElement] = useState<HTMLElement | null>(null);
  const [startTime] = useState(Date.now());
  const [isPaused, setIsPaused] = useState(false);

  const currentStepData = tutorial.steps[currentStep];
  const progress = ((currentStep + 1) / tutorial.steps.length) * 100;

  // Highlight target element
  useEffect(() => {
    if (!isOpen || !currentStepData?.targetElement) {
      clearHighlight();
      return;
    }

    const element = document.querySelector(currentStepData.targetElement) as HTMLElement;
    if (element) {
      setHighlightedElement(element);
      highlightElement(element, currentStepData.highlightStyle || 'pulse');

      // Scroll element into view
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return () => clearHighlight();
  }, [isOpen, currentStep, currentStepData]);

  // Track tutorial progress
  useEffect(() => {
    if (isOpen && currentStep === 0) {
      trackProgress('started');
    }
  }, [isOpen]);

  const highlightElement = (element: HTMLElement, style: string) => {
    element.style.position = 'relative';
    element.style.zIndex = '9999';

    switch (style) {
      case 'pulse':
        element.style.animation = 'pulse 2s infinite';
        element.style.boxShadow = '0 0 0 0 rgba(59, 130, 246, 0.7)';
        break;
      case 'glow':
        element.style.boxShadow = '0 0 20px 5px rgba(59, 130, 246, 0.6)';
        break;
      case 'border':
        element.style.border = '3px solid #3B82F6';
        element.style.borderRadius = '8px';
        break;
    }

    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'tutorial-backdrop';
    backdrop.style.cssText = `
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9998;
      pointer-events: none;
    `;
    document.body.appendChild(backdrop);
  };

  const clearHighlight = () => {
    if (highlightedElement) {
      highlightedElement.style.animation = '';
      highlightedElement.style.boxShadow = '';
      highlightedElement.style.border = '';
      highlightedElement.style.zIndex = '';
    }

    const backdrop = document.getElementById('tutorial-backdrop');
    if (backdrop) {
      backdrop.remove();
    }
  };

  const handleNext = () => {
    if (!completedSteps.includes(currentStep)) {
      setCompletedSteps([...completedSteps, currentStep]);
    }

    if (currentStep < tutorial.steps.length - 1) {
      setCurrentStep(currentStep + 1);
      trackProgress('step_completed', currentStep + 1);
    } else {
      handleComplete();
    }
  };

  const handleBack = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleSkip = () => {
    if (currentStepData.canSkip !== false) {
      handleNext();
    }
  };

  const handleComplete = () => {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    trackProgress('completed', tutorial.steps.length, timeSpent);
    clearHighlight();
    onComplete?.();
    onClose();
  };

  const handleClose = () => {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    trackProgress('abandoned', currentStep + 1, timeSpent);
    clearHighlight();
    onClose();
  };

  const trackProgress = (action: string, step?: number, timeSpent?: number) => {
    fetch('/api/v1/user/help/tutorial/progress', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tutorial_id: tutorial.id,
        action,
        current_step: step || currentStep + 1,
        time_spent_seconds: timeSpent,
        completed_steps: completedSteps
      })
    }).catch(() => {});
  };

  if (!isOpen) return null;

  const isModal = currentStepData?.position === 'modal' || !currentStepData?.targetElement;

  return (
    <>
      {/* Tutorial Card */}
      <AnimatePresence>
        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0, scale: 0.9 }}
          className={`fixed z-[10000] ${
            isModal
              ? 'inset-0 flex items-center justify-center p-4'
              : 'bottom-4 right-4 w-96'
          }`}
        >
          {isModal && (
            <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={handleClose} />
          )}

          <Card className={`relative shadow-2xl border-2 border-blue-500 ${
            isModal ? 'w-full max-w-2xl' : 'w-96'
          }`}>
            <CardContent className="p-6 space-y-4">
              {/* Header */}
              <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-3 flex-1">
                  <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <Lightbulb className="w-5 h-5 text-blue-600" />
                  </div>
                  <div className="flex-1">
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white">
                      {tutorial.title}
                    </h3>
                    <p className="text-sm text-slate-500">
                      Step {currentStep + 1} of {tutorial.steps.length}
                    </p>
                  </div>
                </div>
                <button
                  onClick={handleClose}
                  className="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  <X className="w-5 h-5 text-slate-400" />
                </button>
              </div>

              {/* Progress Bar */}
              <div className="space-y-2">
                <Progress value={progress} className="h-2" />
                <p className="text-xs text-center text-slate-500">
                  {Math.round(progress)}% Complete • ~{tutorial.estimatedMinutes} min
                </p>
              </div>

              {/* Step Content */}
              <div className="space-y-3">
                <h4 className="font-semibold text-slate-900 dark:text-white">
                  {currentStepData.title}
                </h4>
                <div className="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  {currentStepData.content}
                </div>

                {/* Media */}
                {currentStepData.imageUrl && (
                  <img
                    src={currentStepData.imageUrl}
                    alt={currentStepData.title}
                    className="w-full rounded-lg border"
                  />
                )}

                {currentStepData.gifUrl && (
                  <img
                    src={currentStepData.gifUrl}
                    alt={currentStepData.title}
                    className="w-full rounded-lg border"
                  />
                )}

                {currentStepData.videoUrl && (
                  <video
                    src={currentStepData.videoUrl}
                    controls
                    className="w-full rounded-lg border"
                  />
                )}
              </div>

              {/* Action Required Indicator */}
              {currentStepData.requiresAction && (
                <div className="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                  <p className="text-sm text-yellow-800 dark:text-yellow-200">
                    ⚠️ Complete the action above to proceed
                  </p>
                </div>
              )}

              {/* Navigation */}
              <div className="flex items-center justify-between gap-2 pt-4 border-t">
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={handleBack}
                    disabled={currentStep === 0}
                  >
                    <ChevronLeft className="w-4 h-4" />
                    {currentStepData.backButtonText || 'Back'}
                  </Button>

                  {currentStepData.canSkip !== false && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={handleSkip}
                    >
                      <SkipForward className="w-4 h-4" />
                      Skip
                    </Button>
                  )}
                </div>

                <Button
                  size="sm"
                  onClick={handleNext}
                  disabled={currentStepData.requiresAction}
                >
                  {currentStep === tutorial.steps.length - 1 ? (
                    <>
                      Complete <Check className="w-4 h-4 ml-1" />
                    </>
                  ) : (
                    <>
                      {currentStepData.nextButtonText || 'Next'} <ChevronRight className="w-4 h-4 ml-1" />
                    </>
                  )}
                </Button>
              </div>

              {/* Pause Option */}
              <div className="text-center">
                <button
                  onClick={() => setIsPaused(!isPaused)}
                  className="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                >
                  {isPaused ? (
                    <>
                      <Play className="w-3 h-3 inline mr-1" />
                      Resume Tutorial
                    </>
                  ) : (
                    <>
                      <Pause className="w-3 h-3 inline mr-1" />
                      Pause Tutorial
                    </>
                  )}
                </button>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </AnimatePresence>

      {/* CSS for pulse animation */}
      <style jsx>{`
        @keyframes pulse {
          0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
          }
          70% {
            box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
          }
          100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
          }
        }
      `}</style>
    </>
  );
}
