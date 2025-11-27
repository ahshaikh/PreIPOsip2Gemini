// V-PHASE4-1730-104 (Updated with comprehensive navigation structure)
'use client';

import { useCallback } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

export default function Footer() {
  const router = useRouter();

  const scrollTo = useCallback((id: string) => {
    // try exact id
    const el = document.getElementById(id);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // small focus to help some browsers
      el.setAttribute('tabindex', '-1');
      (el as HTMLElement).focus({ preventScroll: true });
      return;
    }
    // fallback: try with leading hash or lowercased versions
    const el2 = document.querySelector(`[id="${id.replace('#','')}"]`) as HTMLElement | null;
    if (el2) {
      el2.scrollIntoView({ behavior: 'smooth', block: 'start' });
      el2.setAttribute('tabindex', '-1');
      el2.focus({ preventScroll: true });
      return;
    }
    // if not found, try navigating to root with hash (works if the page is different)
    if (typeof window !== 'undefined') {
      const current = window.location.pathname;
      if (current !== '/') {
        router.push(`/#${id}`);
      }
    }
  }, [router]);

  return (
    <footer className="bg-gray-900 text-gray-300 py-12">
      <div className="max-w-6xl mx-auto px-4">

        <div className="grid md:grid-cols-4 gap-8 mb-8">

          <div>
            <div className="gradient-primary text-white px-4 py-2 rounded-lg font-bold text-xl inline-block mb-4">
              PreIPO SIP
            </div>
            <p className="text-sm">India&apos;s first 100% free pre-IPO SIP platform with guaranteed bonuses.</p>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Platform</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/explore" className="hover:text-white cursor-pointer block">Explore Listings</Link></li>
              <li><button className="hover:text-white" onClick={() => scrollTo('calculator')}>SIP Calculator</button></li>
              <li><Link href="/private-equity" className="hover:text-white cursor-pointer block">Private Equity</Link></li>
              <li><button className="hover:text-white" onClick={() => scrollTo('how-it-works')}>How It Works</button></li>
              <li><Link href="/pricing" className="hover:text-white cursor-pointer block">Pricing</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Company</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/team" className="hover:text-white cursor-pointer block">Our Team</Link></li>
              <li><Link href="/press" className="hover:text-white cursor-pointer block">Press</Link></li>
              <li><Link href="/about" className="hover:text-white cursor-pointer block">About Us</Link></li>
              <li><Link href="/careers" className="hover:text-white cursor-pointer block">Careers</Link></li>
              <li><Link href="/contact" className="hover:text-white cursor-pointer block">Contact</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Resources</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/blog" className="hover:text-white cursor-pointer block">Blog & Insights</Link></li>
              <li><Link href="/help" className="hover:text-white cursor-pointer block">Help Center</Link></li>
              <li><Link href="/faq" className="hover:text-white cursor-pointer block">FAQs</Link></li>
              <li><Link href="/grievance-redressal" className="hover:text-white cursor-pointer block">Grievance Redressal</Link></li>
              <li><Link href="/investor-charter" className="hover:text-white cursor-pointer block">Investor Charter</Link></li>
            </ul>
          </div>

        </div>

        <div className="grid md:grid-cols-2 gap-8 mb-8">

          <div>
            <h4 className="font-semibold text-white mb-4">Legal & Compliance</h4>
            <ul className="space-y-2 text-sm grid md:grid-cols-2 gap-x-4">
              <li><Link href="/risk-disclosure" className="hover:text-white cursor-pointer block">Risk Disclosure</Link></li>
              <li><Link href="/sebi-regulations" className="hover:text-white cursor-pointer block">SEBI Regulations</Link></li>
              <li><Link href="/terms" className="hover:text-white cursor-pointer block">Terms of Service</Link></li>
              <li><Link href="/privacy-policy" className="hover:text-white cursor-pointer block">Privacy Policy</Link></li>
              <li><Link href="/cookie-policy" className="hover:text-white cursor-pointer block">Cookie Policy</Link></li>
              <li><Link href="/sebi" className="hover:text-white cursor-pointer block">SEBI Compliance</Link></li>
              <li><Link href="/refund-policy" className="hover:text-white cursor-pointer block">Refund Policy</Link></li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Account</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/login" className="hover:text-white cursor-pointer block">Login</Link></li>
              <li><Link href="/signup" className="hover:text-white cursor-pointer block">Create Account</Link></li>
            </ul>
          </div>

        </div>

        <div className="border-t border-gray-800 pt-8 text-center text-sm">
          <p>© 2024 PreIPO SIP Platform. All rights reserved. SEBI Registered.</p>
        </div>

      </div>
    </footer>
  );
}
