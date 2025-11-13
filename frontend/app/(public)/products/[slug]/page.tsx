// V-FINAL-1730-291
'use client';

import { useParams } from "next/navigation";
import { ProductPriceChart } from "@/components/features/ProductPriceChart";
import { Button } from "@/components/ui/button";
import Link from "next/link";

export default function ProductDetailPage() {
  const { slug } = useParams();
  
  // Note: In a real app, you'd fetch product details here too.
  // For now, we focus on the chart integration.

  return (
    <div className="container py-20">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-4xl font-bold capitalize">{(slug as string).replace('-', ' ')}</h1>
        <Button asChild><Link href="/signup">Invest Now</Link></Button>
      </div>
      
      <div className="grid md:grid-cols-3 gap-8">
        <div className="md:col-span-2">
           {/* The New Chart */}
           <ProductPriceChart slug={slug as string} />
           
           <div className="mt-8 prose">
             <h3>About</h3>
             <p>Lorem ipsum description of the pre-IPO company...</p>
           </div>
        </div>
        
        <div className="bg-muted p-6 rounded-xl h-fit">
            <h3 className="font-bold mb-4">Investment Highlights</h3>
            <ul className="space-y-2 text-sm">
                <li>• Minimum Investment: ₹5,000</li>
                <li>• Sector: Technology</li>
                <li>• Lock-in: 6 Months</li>
            </ul>
        </div>
      </div>
    </div>
  );
}