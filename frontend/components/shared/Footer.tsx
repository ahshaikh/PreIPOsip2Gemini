// V-PHASE4-1730-104
'use client';

import Link from 'next/link';

export function Footer() {
  // These links would also be fetched from the API
  return (
    <footer className="border-t">
      <div className="container py-12">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div>
            <h4 className="font-semibold mb-2">Company</h4>
            <Link href="/about" className="block text-sm text-muted-foreground hover:underline">About Us</Link>
            <Link href="/how-it-works" className="block text-sm text-muted-foreground hover:underline">How It Works</Link>
          </div>
          <div>
            <h4 className="font-semibold mb-2">Products</h4>
            <Link href="/plans" className="block text-sm text-muted-foreground hover:underline">Investment Plans</Link>
            <Link href="/products" className="block text-sm text-muted-foreground hover:underline">Pre-IPO Products</Link>
          </div>
          <div>
            <h4 className="font-semibold mb-2">Support</h4>
            <Link href="/faq" className="block text-sm text-muted-foreground hover:underline">FAQs</Link>
            <Link href="/contact" className="block text-sm text-muted-foreground hover:underline">Contact Us</Link>
          </div>
          <div>
            <h4 className="font-semibold mb-2">Legal</h4>
            <Link href="/terms" className="block text-sm text-muted-foreground hover:underline">Terms & Conditions</Link>
            <Link href="/privacy" className="block text-sm text-muted-foreground hover:underline">Privacy Policy</Link>
          </div>
        </div>
        <div className="mt-8 pt-8 border-t">
          <p className="text-center text-sm text-muted-foreground">
            © {new Date().getFullYear()} PreIPOsip.com. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
}