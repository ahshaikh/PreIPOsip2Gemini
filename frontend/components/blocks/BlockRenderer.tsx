// V-CMS-ENHANCEMENT-013 | BlockRenderer Component
// Created: 2025-12-10 | Purpose: Render page blocks dynamically on frontend

import React from 'react';
import { HeroBlock } from './HeroBlock';
import { CtaBlock } from './CtaBlock';
import { FeaturesBlock } from './FeaturesBlock';
import { RichTextBlock } from './RichTextBlock';
import { AccordionBlock } from './AccordionBlock';

interface Block {
  id: number;
  type: string;
  name?: string;
  config: any;
  container_width?: 'full' | 'boxed' | 'narrow';
  background_type?: 'none' | 'color' | 'gradient' | 'image';
  background_config?: any;
  spacing?: {
    top?: string;
    bottom?: string;
  };
  visibility?: 'always' | 'desktop_only' | 'mobile_only';
  is_active: boolean;
}

interface BlockRendererProps {
  blocks: Block[];
}

export function BlockRenderer({ blocks }: BlockRendererProps) {
  if (!blocks || blocks.length === 0) {
    return null;
  }

  return (
    <>
      {blocks.filter(block => block.is_active).map((block) => {
        // Visibility classes
        let visibilityClass = '';
        if (block.visibility === 'desktop_only') visibilityClass = 'hidden md:block';
        if (block.visibility === 'mobile_only') visibilityClass = 'block md:hidden';

        // Container width
        let containerClass = 'max-w-7xl mx-auto px-4'; // default boxed
        if (block.container_width === 'full') containerClass = 'w-full';
        if (block.container_width === 'narrow') containerClass = 'max-w-4xl mx-auto px-4';

        // Spacing
        const spacingTop = block.spacing?.top || 'py-12';
        const spacingBottom = block.spacing?.bottom || 'py-12';
        const spacingClass = `${spacingTop} ${spacingBottom}`;

        // Background styles
        let backgroundStyle: React.CSSProperties = {};
        let backgroundClass = '';

        if (block.background_type === 'color' && block.background_config?.color) {
          backgroundStyle.backgroundColor = block.background_config.color;
        } else if (block.background_type === 'gradient' && block.background_config) {
          backgroundStyle.background = `linear-gradient(${block.background_config.direction || '135deg'}, ${block.background_config.from || '#667eea'}, ${block.background_config.to || '#764ba2'})`;
        } else if (block.background_type === 'image' && block.background_config?.url) {
          backgroundStyle.backgroundImage = `url(${block.background_config.url})`;
          backgroundStyle.backgroundSize = block.background_config.size || 'cover';
          backgroundStyle.backgroundPosition = block.background_config.position || 'center';
          backgroundClass = 'bg-cover bg-center';
        }

        // Render the appropriate block component
        let BlockComponent: React.ReactNode = null;

        switch (block.type) {
          case 'hero':
            BlockComponent = <HeroBlock config={block.config} />;
            break;
          case 'cta':
            BlockComponent = <CtaBlock config={block.config} />;
            break;
          case 'features':
            BlockComponent = <FeaturesBlock config={block.config} />;
            break;
          case 'richtext':
            BlockComponent = <RichTextBlock config={block.config} />;
            break;
          case 'accordion':
            BlockComponent = <AccordionBlock config={block.config} />;
            break;
          default:
            return null;
        }

        return (
          <section
            key={block.id}
            className={`${spacingClass} ${visibilityClass} ${backgroundClass}`}
            style={backgroundStyle}
          >
            <div className={containerClass}>
              {BlockComponent}
            </div>
          </section>
        );
      })}
    </>
  );
}
