// V-FINAL-1730-290
'use client';

import { DynamicLineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from '@/components/shared/DynamicChart';
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export function ProductPriceChart({ slug }: { slug: string }) {
  const { data: history, isLoading } = useQuery({
    queryKey: ['priceHistory', slug],
    queryFn: async () => (await api.get(`/products/${slug}/history`)).data,
  });

  if (isLoading) return <div className="h-[200px] bg-muted animate-pulse rounded"></div>;
  if (!history || history.length < 2) return null; // Don't show empty charts

  return (
    <div className="h-[300px] w-full mt-6">
      <h3 className="text-lg font-semibold mb-4">Price History</h3>
      <ResponsiveContainer width="100%" height="100%">
        <DynamicLineChart data={history}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis />
          <Tooltip formatter={(value) => `₹${value}`} />
          <Line type="monotone" dataKey="price" stroke="#8884d8" strokeWidth={2} />
        </DynamicLineChart>
      </ResponsiveContainer>
    </div>
  );
}