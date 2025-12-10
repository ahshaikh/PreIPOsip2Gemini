// V-CMS-ENHANCEMENT-013 | RichTextBlock Component
// Created: 2025-12-10 | Purpose: Render rich text content block

import React from 'react';

interface RichTextConfig {
  content?: string;
  text_align?: 'left' | 'center' | 'right';
}

interface RichTextBlockProps {
  config: RichTextConfig;
}

export function RichTextBlock({ config }: RichTextBlockProps) {
  const textAlign = config.text_align || 'left';
  const alignClass = textAlign === 'center' ? 'text-center' : textAlign === 'right' ? 'text-right' : 'text-left';

  return (
    <div
      className={`prose prose-lg dark:prose-invert max-w-none ${alignClass}`}
      dangerouslySetInnerHTML={{ __html: config.content || '' }}
    />
  );
}
