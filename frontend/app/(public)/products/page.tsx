// V-FINAL-1730-185
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";

export default function ProductsPage() {
  // In a real app, this would fetch from API. 
  // Since we didn't make a public products API endpoint yet, we'll hardcode the seeded data for display.
  const products = [
    { name: 'Swiggy', sector: 'Food Tech', price: 100, status: 'Active' },
    { name: 'Ola Electric', sector: 'EV', price: 75, status: 'Active' },
    { name: 'PharmEasy', sector: 'Health Tech', price: 50, status: 'Upcoming' },
  ];

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-4">Pre-IPO Products</h1>
      <p className="text-xl text-muted-foreground text-center mb-12">
        Invest in tomorrow's market leaders today.
      </p>

      <div className="grid md:grid-cols-3 gap-6">
        {products.map((p) => (
          <Card key={p.name}>
            <CardHeader>
              <CardTitle>{p.name}</CardTitle>
              <span className="text-sm text-muted-foreground">{p.sector}</span>
            </CardHeader>
            <CardContent>
              <div className="flex justify-between items-center mb-6">
                <span className="text-2xl font-bold">₹{p.price}</span>
                <span className={`px-2 py-1 rounded text-xs ${p.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                  {p.status}
                </span>
              </div>
              <Button className="w-full" asChild disabled={p.status !== 'Active'}>
                <Link href="/signup">
                  {p.status === 'Active' ? 'Invest Now' : 'Coming Soon'}
                </Link>
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}