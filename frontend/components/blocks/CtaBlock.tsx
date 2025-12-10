// V-CMS-ENHANCEMENT-013 | CtaBlock Component
// Created: 2025-12-10 | Purpose: Render call-to-action block

import React from 'react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

interface CtaConfig {
  heading?: string;
  description?: string;
  button_text?: string;
  button_url?: string;
  style?: 'default' | 'gradient' | 'bordered';
}

interface CtaBlockProps {
  config: CtaConfig;
}

export function CtaBlock({ config }: CtaBlockProps) {
  const style = config.style || 'default';

  const cardClasses = {
    default: 'bg-card',
    gradient: 'bg-gradient-to-r from-purple-600 to-pink-600 text-white',
    bordered: 'border-2 border-primary bg-card'
  };

  return (
    <Card className={`${cardClasses[style]} shadow-lg`}>
      <CardContent className="p-12 text-center">
        {config.heading && (
          <h2 className="text-4xl font-bold mb-4">
            {config.heading}
          </h2>
        )}

        {config.description && (
          <p className={`text-lg mb-8 ${style === 'gradient' ? 'text-white/90' : 'text-muted-foreground'}`}>
            {config.description}
          </p>
        )}

        {config.button_text && config.button_url && (
          <Link href={config.button_url}>
            <Button
              size="lg"
              variant={style === 'gradient' ? 'secondary' : 'default'}
              className="text-lg px-10 py-6"
            >
              {config.button_text}
            </Button>
          </Link>
        )}
      </CardContent>
    </Card>
  );
}
