// V-PHASE4-1730-104 (Created) 
'use client';

import { useCallback } from 'react';
import { useRouter } from 'next/navigation';

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
            <h4 className="font-semibold text-white mb-4">Product</h4>
            <ul className="space-y-2 text-sm">
              <li><button className="hover:text-white" onClick={() => scrollTo('plans')}>Plans</button></li>
              <li><button className="hover:text-white" onClick={() => scrollTo('how-it-works')}>How It Works</button></li>
              <li><button className="hover:text-white" onClick={() => scrollTo('calculator')}>Calculator</button></li>
              <li><button className="hover:text-white" onClick={() => scrollTo('features')}>Features</button></li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Company</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/about" className="hover:text-white">About Us</a></li>
              <li><a href="/blog" className="hover:text-white">Blog</a></li>
              <li><a href="/careers" className="hover:text-white">Careers</a></li>
              <li><a href="/contact" className="hover:text-white">Contact</a></li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4">Legal & Account</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/privacy-policy" className="hover:text-white">Privacy Policy</a></li>
              <li><a href="/terms" className="hover:text-white">Terms of Service</a></li>
              <li><a href="/sebi" className="hover:text-white">SEBI Compliance</a></li>
              <li><a href="/refund-policy" className="hover:text-white">Refund Policy</a></li>
              <li><button className="hover:text-white" onClick={() => router.push('/login')}>Login</button></li>
              <li><button className="hover:text-white" onClick={() => router.push('/signup')}>Create Account</button></li>
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
