// V-CMS-ENHANCEMENT-013 | AccordionBlock Component
// Created: 2025-12-10 | Purpose: Render accordion/FAQ block

import React from 'react';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';

interface AccordionItem {
  question: string;
  answer: string;
}

interface AccordionConfig {
  heading?: string;
  items?: AccordionItem[];
}

interface AccordionBlockProps {
  config: AccordionConfig;
}

export function AccordionBlock({ config }: AccordionBlockProps) {
  return (
    <div>
      {config.heading && (
        <h2 className="text-4xl font-bold text-center mb-12">
          {config.heading}
        </h2>
      )}

      <Accordion type="single" collapsible className="max-w-3xl mx-auto">
        {config.items?.map((item, index) => (
          <AccordionItem key={index} value={`item-${index}`}>
            <AccordionTrigger className="text-left text-lg font-semibold">
              {item.question}
            </AccordionTrigger>
            <AccordionContent className="text-base text-muted-foreground">
              {item.answer}
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </div>
  );
}
