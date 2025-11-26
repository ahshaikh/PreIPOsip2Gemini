// V-PHASE4-1730-104 (Created)
'use client';

import { useRouter } from 'next/navigation';
import { Mail, Phone, MapPin } from 'lucide-react';

export default function Footer() {
  const router = useRouter();

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
                  support@preiposip.com.
                </a>
              </div>
              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 flex-shrink-0" />
                <span>+91-22-XXXX-XXXX.</span>
              </div>
            </div>

            {/* Social Media Icons */}
            <div className="flex gap-4 mt-6">
              <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                </svg>
              </a>
              <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                </svg>
              </a>
              <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" className="hover:text-white">
                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </a>
            </div>
          </div>

          {/* Platform Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Platform</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/listings" className="hover:text-white">Explore Listings</a></li>
              <li><a href="/calculator" className="hover:text-white">SIP Calculator</a></li>
              <li><a href="/private-equity" className="hover:text-white">Private Equity</a></li>
              <li><a href="/how-it-works" className="hover:text-white">How It Works</a></li>
              <li><a href="/pricing" className="hover:text-white">Pricing</a></li>
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Company</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/about" className="hover:text-white">About Us</a></li>
              <li><a href="/team" className="hover:text-white">Our Team</a></li>
              <li><a href="/careers" className="hover:text-white">Careers</a></li>
              <li><a href="/press" className="hover:text-white">Press</a></li>
              <li><a href="/contact" className="hover:text-white">Contact Us</a></li>
            </ul>
          </div>

          {/* Resources Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Resources</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/blog" className="hover:text-white">Blog & Insights</a></li>
              <li><a href="/help" className="hover:text-white">Help Center</a></li>
              <li><a href="/faqs" className="hover:text-white">FAQs</a></li>
              <li><a href="/grievance" className="hover:text-white">Grievance Redressal</a></li>
              <li><a href="/investor-charter" className="hover:text-white">Investor Charter</a></li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h4 className="font-semibold text-white mb-4">Legal</h4>
            <ul className="space-y-2 text-sm">
              <li><a href="/terms" className="hover:text-white">Terms of Service</a></li>
              <li><a href="/risk-disclosure" className="hover:text-white">Risk Disclosure</a></li>
              <li><a href="/privacy-policy" className="hover:text-white">Privacy Policy</a></li>
              <li><a href="/cookie-policy" className="hover:text-white">Cookie Policy</a></li>
              <li><a href="/sebi" className="hover:text-white">SEBI Compliance</a></li>
              <li><a href="/refund-policy" className="hover:text-white">Refund Policy</a></li>
              <li><a href="/sebi" className="hover:text-white">SEBI Regulations</a></li>
            </ul>
          </div>

        </div>

        {/* Disclaimer Section */}
        <div className="border-t border-gray-800 pt-8 pb-4">
          <p className="text-xs text-gray-500 text-center mb-4">
            PreIPO SIP is a product of [Legal Entity Name]. SEBI Registration No: INZ000xxxxxx. CIN: U65990MHxxxxPTCxxxxxx. Registered Office: [Full Address in Mira Bhayandar].
            Disclaimer: Investments in unlisted securities and Pre-IPO markets carry a high degree of risk. The value of investments can go up or down. Past performance is not indicative of future results. Please read the Risk Disclosure Document carefully before investing.
          </p>
          <p className="text-sm text-gray-400 text-center">
            © 2025 PreIPO SIP Platform. All rights reserved.
          </p>
        </div>

      </div>
    </footer>
  );
}
