'use client';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Layers } from 'lucide-react';

export default function SectorsPage() {
  const sectors = [
    { name: 'Technology', icon: 'laptop', color: '#3B82F6', count: 0 },
    { name: 'Healthcare', icon: 'heart', color: '#EF4444', count: 0 },
    { name: 'Fintech', icon: 'credit-card', color: '#10B981', count: 0 },
    { name: 'E-commerce', icon: 'shopping-cart', color: '#F59E0B', count: 0 },
    { name: 'EdTech', icon: 'book', color: '#8B5CF6', count: 0 },
    { name: 'Clean Energy', icon: 'zap', color: '#14B8A6', count: 0 },
    { name: 'Real Estate', icon: 'home', color: '#F97316', count: 0 },
    { name: 'Transportation', icon: 'truck', color: '#6366F1', count: 0 },
  ];

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
            {sectors.map((sector) => (
              <Card key={sector.name} className="border-l-4" style={{ borderLeftColor: sector.color }}>
                <CardContent className="p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="font-semibold">{sector.name}</h3>
                      <p className="text-sm text-muted-foreground">
                        {sector.count} companies
                      </p>
                    </div>
                    <div
                      className="w-10 h-10 rounded-full flex items-center justify-center"
                      style={{ backgroundColor: `${sector.color}20` }}
                    >
                      <Layers style={{ color: sector.color }} className="h-5 w-5" />
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
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
