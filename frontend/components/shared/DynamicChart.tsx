// Dynamic Chart Components - Lazy loaded to reduce initial bundle size
'use client';

import dynamic from 'next/dynamic';
import { ComponentType } from 'react';

// Loading fallback for charts
const ChartSkeleton = () => (
  <div className="h-full w-full bg-muted animate-pulse rounded" />
);

// Dynamically import Recharts components
export const DynamicLineChart = dynamic(
  () => import('recharts').then((mod) => mod.LineChart as ComponentType<any>),
  {
    loading: () => <ChartSkeleton />,
    ssr: false, // Charts don't need SSR
  }
);

export const DynamicBarChart = dynamic(
  () => import('recharts').then((mod) => mod.BarChart as ComponentType<any>),
  {
    loading: () => <ChartSkeleton />,
    ssr: false,
  }
);

export const DynamicAreaChart = dynamic(
  () => import('recharts').then((mod) => mod.AreaChart as ComponentType<any>),
  {
    loading: () => <ChartSkeleton />,
    ssr: false,
  }
);

export const DynamicPieChart = dynamic(
  () => import('recharts').then((mod) => mod.PieChart as ComponentType<any>),
  {
    loading: () => <ChartSkeleton />,
    ssr: false,
  }
);

// Export other Recharts components (these are small, but keep them together)
export { Line, Bar, Area, Pie, Cell } from 'recharts';
export { XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
