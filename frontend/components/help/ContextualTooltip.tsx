'use client';

import React, { useState, useEffect, useRef } from 'react';
import { X, HelpCircle, ExternalLink, Play, Info } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface TooltipData {
  id: string;
  title: string;
  content: string;
  position?: 'top' | 'bottom' | 'left' | 'right' | 'auto';
  icon?: string;
  imageUrl?: string;
  videoUrl?: string;
  learnMoreUrl?: string;
  ctaText?: string;
  ctaUrl?: string;
  dismissible?: boolean;
  showOnce?: boolean;
  autoHideSeconds?: number;
}

interface ContextualTooltipProps {
  elementId: string;
  tooltip?: TooltipData;
  children?: React.ReactNode;
  trigger?: 'hover' | 'click' | 'focus' | 'auto';
  delay?: number;
}

export default function ContextualTooltip({
  elementId,
  tooltip,
  children,
  trigger = 'hover',
  delay = 500
}: ContextualTooltipProps) {
  const [isVisible, setIsVisible] = useState(false);
  const [hasBeenDismissed, setHasBeenDismissed] = useState(false);
  const [hasBeenShown, setHasBeenShown] = useState(false);
  const timeoutRef = useRef<NodeJS.Timeout>();
  const autoHideRef = useRef<NodeJS.Timeout>();

  // Check if tooltip was previously dismissed
  useEffect(() => {
    if (tooltip?.showOnce) {
      const dismissed = localStorage.getItem(`tooltip-dismissed-${elementId}`);
      if (dismissed) {
        setHasBeenDismissed(true);
      }
    }
  }, [elementId, tooltip?.showOnce]);

  // Auto-show for auto trigger
  useEffect(() => {
    if (trigger === 'auto' && !hasBeenDismissed && !hasBeenShown && tooltip) {
      timeoutRef.current = setTimeout(() => {
        setIsVisible(true);
        setHasBeenShown(true);
      }, delay);
    }

    return () => {
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
    };
  }, [trigger, delay, hasBeenDismissed, hasBeenShown, tooltip]);

  // Auto-hide after specified seconds
  useEffect(() => {
    if (isVisible && tooltip?.autoHideSeconds) {
      autoHideRef.current = setTimeout(() => {
        handleDismiss();
      }, tooltip.autoHideSeconds * 1000);
    }

    return () => {
      if (autoHideRef.current) clearTimeout(autoHideRef.current);
    };
  }, [isVisible, tooltip?.autoHideSeconds]);

  const handleShow = () => {
    if (!hasBeenDismissed && tooltip) {
      setIsVisible(true);
      trackInteraction('tooltip_viewed');
    }
  };

  const handleHide = () => {
    setIsVisible(false);
  };

  const handleDismiss = () => {
    setIsVisible(false);
    if (tooltip?.showOnce) {
      localStorage.setItem(`tooltip-dismissed-${elementId}`, 'true');
      setHasBeenDismissed(true);
    }
    trackInteraction('tooltip_dismissed');
  };

  const trackInteraction = (type: string) => {
    // Track interaction with backend
    fetch('/api/v1/user/help/track', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        interaction_type: type,
        element_id: elementId,
        page_url: window.location.pathname
      })
    }).catch(() => {}); // Silent fail
  };

  const handleMouseEnter = () => {
    if (trigger === 'hover') {
      timeoutRef.current = setTimeout(handleShow, delay);
    }
  };

  const handleMouseLeave = () => {
    if (trigger === 'hover') {
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
      handleHide();
    }
  };

  const handleClick = () => {
    if (trigger === 'click') {
      setIsVisible(!isVisible);
    }
  };

  if (hasBeenDismissed || !tooltip) {
    return <>{children}</>;
  }

  return (
    <div className="relative inline-block">
      <div
        onMouseEnter={handleMouseEnter}
        onMouseLeave={handleMouseLeave}
        onClick={handleClick}
        className="inline-flex"
      >
        {children || (
          <button className="p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <HelpCircle className="w-4 h-4 text-slate-400 hover:text-blue-500" />
          </button>
        )}
      </div>

      <AnimatePresence>
        {isVisible && (
          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: -10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: -10 }}
            transition={{ duration: 0.15 }}
            className="absolute z-50 w-80"
            style={getPositionStyles(tooltip.position || 'auto')}
            onMouseEnter={() => trigger === 'hover' && setIsVisible(true)}
            onMouseLeave={() => trigger === 'hover' && handleHide()}
          >
            <Card className="shadow-xl border-2 border-blue-200 dark:border-blue-800">
              <CardContent className="p-4 space-y-3">
                {/* Header */}
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-start gap-2 flex-1">
                    {tooltip.icon && (
                      <Info className="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
                    )}
                    <h4 className="font-semibold text-sm text-slate-900 dark:text-white">
                      {tooltip.title}
                    </h4>
                  </div>
                  {tooltip.dismissible !== false && (
                    <button
                      onClick={handleDismiss}
                      className="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex-shrink-0"
                    >
                      <X className="w-4 h-4 text-slate-400" />
                    </button>
                  )}
                </div>

                {/* Content */}
                <div className="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                  {tooltip.content}
                </div>

                {/* Media */}
                {tooltip.imageUrl && (
                  <img
                    src={tooltip.imageUrl}
                    alt={tooltip.title}
                    className="w-full rounded-lg"
                  />
                )}

                {tooltip.videoUrl && (
                  <div className="relative aspect-video bg-slate-100 dark:bg-slate-800 rounded-lg overflow-hidden">
                    <div className="absolute inset-0 flex items-center justify-center">
                      <a
                        href={tooltip.videoUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                      >
                        <Play className="w-4 h-4" />
                        Watch Video
                      </a>
                    </div>
                  </div>
                )}

                {/* Actions */}
                {(tooltip.learnMoreUrl || tooltip.ctaUrl) && (
                  <div className="flex gap-2 pt-2 border-t">
                    {tooltip.learnMoreUrl && (
                      <a
                        href={tooltip.learnMoreUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                      >
                        Learn More
                        <ExternalLink className="w-3 h-3" />
                      </a>
                    )}
                    {tooltip.ctaUrl && tooltip.ctaText && (
                      <a
                        href={tooltip.ctaUrl}
                        className="ml-auto"
                      >
                        <Button size="sm" variant="default">
                          {tooltip.ctaText}
                        </Button>
                      </a>
                    )}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Arrow */}
            <div
              className="absolute w-3 h-3 bg-white dark:bg-slate-900 border-l-2 border-t-2 border-blue-200 dark:border-blue-800 transform rotate-45"
              style={getArrowStyles(tooltip.position || 'auto')}
            />
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

function getPositionStyles(position: string) {
  switch (position) {
    case 'top':
      return { bottom: '100%', left: '50%', transform: 'translateX(-50%)', marginBottom: '12px' };
    case 'bottom':
      return { top: '100%', left: '50%', transform: 'translateX(-50%)', marginTop: '12px' };
    case 'left':
      return { right: '100%', top: '50%', transform: 'translateY(-50%)', marginRight: '12px' };
    case 'right':
      return { left: '100%', top: '50%', transform: 'translateY(-50%)', marginLeft: '12px' };
    default:
      return { top: '100%', left: '50%', transform: 'translateX(-50%)', marginTop: '12px' };
  }
}

function getArrowStyles(position: string) {
  switch (position) {
    case 'top':
      return { bottom: '-6px', left: '50%', transform: 'translateX(-50%) rotate(225deg)' };
    case 'bottom':
      return { top: '-6px', left: '50%', transform: 'translateX(-50%) rotate(45deg)' };
    case 'left':
      return { right: '-6px', top: '50%', transform: 'translateY(-50%) rotate(315deg)' };
    case 'right':
      return { left: '-6px', top: '50%', transform: 'translateY(-50%) rotate(135deg)' };
    default:
      return { top: '-6px', left: '50%', transform: 'translateX(-50%) rotate(45deg)' };
  }
}
