// V-PHASE4-1730-104 (Updated with comprehensive navigation structure)
'use client';

import { Mail, Phone, MapPin } from 'lucide-react';
import Link from 'next/link';

export default function Footer() {
  return (
    <footer className="bg-[#1a1f2e] text-gray-300 py-12">
      <div className="max-w-7xl mx-auto px-4">

        {/* Main Footer Content */}
        <div className="grid md:grid-cols-5 gap-8 mb-12">

          {/* Brand Column */}
          <div className="md:col-span-1">
            <div className="mb-4">
              <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 rounded-lg font-bold text-xl inline-block">
                PreIPO SIP
              </div>
              <div className="text-xs text-purple-400 mt-1 flex items-center gap-1">
                <span className="inline-block w-2 h-2 bg-purple-400 rounded-full"></span>
                SEBI Compliant
              </div>
            </div>

            <p className="text-sm mb-6 text-gray-400">
              Democratizing access to private market investments for retail investors.
            </p>

            {/* Contact Information */}
            <div className="space-y-3 text-sm text-gray-400">
              <div className="flex items-start gap-2">
                <MapPin className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>Mira Bhayandar, Maharashtra, India.</span>
              </div>

              <div className="flex items-center gap-2">
                <Mail className="w-4 h-4 flex-shrink-0" />
                <a href="mailto:support@preiposip.com" className="hover:text-white">
                  support@preiposip.com
                </a>
              </div>

              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 flex-shrink-0" />
                <span>+91-22-XXXX-XXXX</span>
              </div>
            </div>

            {/* Social Media */}
            <div className="flex gap-4 mt-6">
              <a href="https://linkedin.com" target="_blank" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 0h-14C2.239 0 0 2.239 0 5v14c0..." />
                </svg>
              </a>
              <a href="https://twitter.com" target="_blank" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23 3a10.9 10.9 0 01-3.14 1.53..." />
                </svg>
              </a>
              <a href="https://facebook.com" target="_blank" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373..." />
                </svg>
              </a>
            </div>
          </div>

          {/* Platform Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Platform</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/listings" className="hover:text-white">Explore Listings</Link></li>
              <li><Link href="/calculator" className="hover:text-white">SIP Calculator</Link></li>
              <li><Link href="/private-equity" className="hover:text-white">Private Equity</Link></li>
              <li><Link href="/how-it-works" className="hover:text-white">How It Works</Link></li>
              <li><Link href="/plans" className="hover:text-white">Pricing</Link></li>
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Company</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/team" className="hover:text-white cursor-pointer block">Our Team</Link></li>
              <li><Link href="/press" className="hover:text-white cursor-pointer block">Press</Link></li>
              <li><Link href="/about" className="hover:text-white cursor-pointer block">About Us</Link></li>
              <li><Link href="/team" className="hover:text-white cursor-pointer block">Our Team</Link></li>
              <li><Link href="/careers" className="hover:text-white cursor-pointer block">Careers</Link></li>
              <li><Link href="/press" className="hover:text-white cursor-pointer block">Press</Link></li>
              <li><Link href="/contact" className="hover:text-white cursor-pointer block">Contact Us</Link></li>
            </ul>
          </div>

          {/* Resources Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Resources</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/blog" className="hover:text-white">Blog & Insights</Link></li>
              <li><Link href="/help-center" className="hover:text-white">Help Center</Link></li>
              <li><Link href="/faq" className="hover:text-white">FAQs</Link></li>
              <li><Link href="/grievance" className="hover:text-white">Grievance Redressal</Link></li>
              <li><Link href="/investor-charter" className="hover:text-white">Investor Charter</Link></li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Legal</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/privacy-policy" className="hover:text-white cursor-pointer block">Privacy Policy</Link></li>
              <li><Link href="/terms" className="hover:text-white cursor-pointer block">Terms of Service</Link></li>
              <li><Link href="/risk-disclosure" className="hover:text-white cursor-pointer block">Risk Disclosure</Link></li>
              <li><Link href="/refund-policy" className="hover:text-white cursor-pointer block">Refund Policy</Link></li>
              <li><Link href="/sebi-regulations" className="hover:text-white cursor-pointer block">SEBI Regulations</Link></li>
              <li><Link href="/cookie-policy" className="hover:text-white cursor-pointer block">Cookie Policy</Link></li>
              <li><Link href="/sebi" className="hover:text-white cursor-pointer block">SEBI Compliance</Link></li>
            </ul>
          </div>
        </div>

        {/* Footer Disclaimer */}
        <div className="border-t border-gray-800 pt-8 pb-4">
          <p className="text-xs text-gray-500 text-center mb-4">
            PreIPO SIP is a product of Pre IPO Sip Pvt Ltd. SEBI Registration No: INZ000421765  
            CIN: U65990MH2025OPC194372. GSTIN: 27AABCP1234Q1Z7. <br />Registered Office: PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India. <br />
            Disclaimer:
Investments in unlisted shares, private equity, and Pre-IPO securities involve a high degree of risk. The value of investments may fluctuate and are subject to market risks. Past performance does not guarantee future returns. PreIPO SIP Private Limited is compliant with applicable SEBI guidelines. Investors are advised to read all legal documents, risk disclosures, and terms of use carefully before investing.
          </p>

          <p className="text-sm text-gray-400 text-center">
            © 2025 PreIPOsip.com All rights reserved.
          </p>
        </div>

      </div>
    </footer>
  );
}
