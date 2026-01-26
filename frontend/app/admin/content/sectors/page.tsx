'use client';

import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Layers, Loader2 } from 'lucide-react';

// Default color for sectors without a color set
const DEFAULT_COLOR = '#6366F1';

export default function SectorsPage() {
  // FIX: Fetch sectors from API instead of using hardcoded data
  const { data: sectorsData, isLoading } = useQuery({
    queryKey: ['admin-sectors'],
    queryFn: async () => {
      const response = await api.get('/admin/sectors');
      return response.data;
    },
  });

  const sectors = sectorsData?.data || [];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Sectors Management</h1>
        <p className="text-muted-foreground">Industry sectors are pre-configured in the database</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Available Sectors</CardTitle>
          <CardDescription>
            These sectors are automatically seeded during database setup. To add more sectors, update the ContentManagementSeeder.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {sectors.length === 0 ? (
              <p className="col-span-full text-center text-muted-foreground py-8">
                No sectors found. Run the database seeder to add sectors.
              </p>
            ) : (
              sectors.map((sector: any) => (
                <Card key={sector.id} className="border-l-4" style={{ borderLeftColor: sector.color || DEFAULT_COLOR }}>
                  <CardContent className="p-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <h3 className="font-semibold">{sector.name}</h3>
                        <p className="text-sm text-muted-foreground">
                          {sector.companies_count || 0} companies
                        </p>
                        <p className="text-xs text-muted-foreground">
                          {sector.deals_count || 0} deals • {sector.products_count || 0} products
                        </p>
                      </div>
                      <div
                        className="w-10 h-10 rounded-full flex items-center justify-center"
                        style={{ backgroundColor: `${sector.color || DEFAULT_COLOR}20` }}
                      >
                        <Layers style={{ color: sector.color || DEFAULT_COLOR }} className="h-5 w-5" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        </CardContent>
      </Card>

      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardHeader>
          <CardTitle className="text-blue-900 dark:text-blue-100">About Sectors</CardTitle>
        </CardHeader>
        <CardContent className="text-blue-800 dark:text-blue-200 space-y-2">
          <p>
            Sectors are pre-defined industry categories used to organize companies and deals. The initial sectors are seeded automatically when you run the database seeder.
          </p>
          <p className="font-semibold">To add or modify sectors:</p>
          <ol className="list-decimal list-inside space-y-1 ml-4">
            <li>Update the <code className="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">backend/database/seeders/ContentManagementSeeder.php</code> file</li>
            <li>Run: <code className="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">php artisan db:seed --class=ContentManagementSeeder</code></li>
          </ol>
        </CardContent>
      </Card>
    </div>
  );
}
