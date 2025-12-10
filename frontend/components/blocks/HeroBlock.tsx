// V-CMS-ENHANCEMENT-013 | HeroBlock Component
// Created: 2025-12-10 | Purpose: Render hero section block

import React from 'react';
import Link from 'next/link';
import { Button } from '@/components/ui/button';

interface HeroConfig {
  heading?: string;
  subheading?: string;
  cta_text?: string;
  cta_url?: string;
  image_url?: string;
  text_align?: 'left' | 'center' | 'right';
}

interface HeroBlockProps {
  config: HeroConfig;
}

export function HeroBlock({ config }: HeroBlockProps) {
  const textAlign = config.text_align || 'center';
  const alignClass = textAlign === 'center' ? 'text-center' : textAlign === 'right' ? 'text-right' : 'text-left';

  return (
    <div className={`py-20 ${alignClass}`}>
      <div className="max-w-4xl mx-auto">
        {config.heading && (
          <h1 className="text-5xl md:text-6xl font-black mb-6 bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-600">
            {config.heading}
          </h1>
        )}

        {config.subheading && (
          <p className="text-xl md:text-2xl text-muted-foreground mb-8 max-w-3xl mx-auto">
            {config.subheading}
          </p>
        )}

        {config.cta_text && config.cta_url && (
          <div className={`flex gap-4 ${textAlign === 'center' ? 'justify-center' : textAlign === 'right' ? 'justify-end' : 'justify-start'}`}>
            <Link href={config.cta_url}>
              <Button size="lg" className="text-lg px-8 py-6">
                {config.cta_text}
              </Button>
            </Link>
          </div>
        )}

        {config.image_url && (
          <div className="mt-12">
            <img
              src={config.image_url}
              alt={config.heading || 'Hero image'}
              className="rounded-xl shadow-2xl mx-auto max-w-full"
            />
          </div>
        )}
      </div>
    </div>
  );
}
