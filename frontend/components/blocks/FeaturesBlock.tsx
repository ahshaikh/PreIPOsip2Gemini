// V-CMS-ENHANCEMENT-013 | FeaturesBlock Component
// Created: 2025-12-10 | Purpose: Render features grid block

import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Zap, Shield, TrendingUp, Users, Award, CheckCircle } from 'lucide-react';

interface Feature {
  title: string;
  description: string;
  icon?: string;
}

interface FeaturesConfig {
  heading?: string;
  items?: Feature[];
  columns?: 2 | 3 | 4;
}

interface FeaturesBlockProps {
  config: FeaturesConfig;
}

// Icon mapping
const iconMap: Record<string, any> = {
  Zap,
  Shield,
  TrendingUp,
  Users,
  Award,
  CheckCircle,
};

export function FeaturesBlock({ config }: FeaturesBlockProps) {
  const columns = config.columns || 3;
  const gridClass = columns === 2 ? 'md:grid-cols-2' : columns === 4 ? 'md:grid-cols-4' : 'md:grid-cols-3';

  return (
    <div>
      {config.heading && (
        <h2 className="text-4xl font-bold text-center mb-12">
          {config.heading}
        </h2>
      )}

      <div className={`grid grid-cols-1 ${gridClass} gap-6`}>
        {config.items?.map((item, index) => {
          const IconComponent = item.icon && iconMap[item.icon] ? iconMap[item.icon] : CheckCircle;

          return (
            <Card key={index} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="mb-4 inline-flex p-3 rounded-lg bg-primary/10 text-primary">
                  <IconComponent className="h-6 w-6" />
                </div>
                <CardTitle>{item.title}</CardTitle>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-base">
                  {item.description}
                </CardDescription>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
