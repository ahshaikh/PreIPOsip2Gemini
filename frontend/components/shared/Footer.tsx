// V-PHASE4-1730-104 (Fixed: Dark Mode Toggle Compatibility)
// 'use client';

import { Mail, Phone, MapPin } from 'lucide-react';
import Link from 'next/link';

export default function Footer() {
  return (
    <footer className="bg-slate-50 dark:bg-[#1a1f2e] text-slate-600 dark:text-gray-300 py-12 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
      <div className="max-w-7xl mx-auto px-4">

        {/* Main Footer Content */}
        <div className="grid md:grid-cols-5 gap-8 mb-12">

          {/* Brand Column */}
          <div className="md:col-span-1">
            <div className="mb-4">
              <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 rounded-lg font-bold text-xl inline-block shadow-sm">
                PreIPO SIP
              </div>
              <div className="text-xs text-purple-600 dark:text-purple-400 mt-1 flex items-center gap-1 font-medium">
                <span className="inline-block w-2 h-2 bg-purple-600 dark:bg-purple-400 rounded-full animate-pulse"></span>
                SEBI Compliant
              </div>
            </div>

            <p className="text-sm mb-6 text-slate-500 dark:text-gray-400 leading-relaxed">
              Democratizing access to private market investments for retail investors.
            </p>

            {/* Contact Information */}
            <div className="space-y-3 text-sm text-slate-500 dark:text-gray-400">
              <div className="flex items-start gap-2 hover:text-blue-600 dark:hover:text-white transition-colors">
                <MapPin className="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                <span>Mira Bhayandar, Maharashtra, India.</span>
              </div>

              <div className="flex items-center gap-2 group">
                <Mail className="w-4 h-4 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                <a href="mailto:support@preiposip.com" className="group-hover:text-blue-600 dark:group-hover:text-white transition-colors">
                  support@preiposip.com
                </a>
              </div>

              <div className="flex items-center gap-2 group">
                <Phone className="w-4 h-4 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                <span className="group-hover:text-blue-600 dark:group-hover:text-white transition-colors">+91-22-XXXX-XXXX</span>
              </div>
            </div>

            {/* Social Media */}
            <div className="flex gap-4 mt-6">
              {['linkedin', 'twitter', 'facebook'].map((social) => (
                <a 
                  key={social} 
                  href={`https://${social}.com`} 
                  target="_blank" 
                  className="text-slate-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-white transition-colors"
                >
                   {/* Simplified SVG mapping for cleaner code */}
                   <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                     <path d="M19 0h-14C2.239 0 0 2.239 0 5v14c0..." />
                   </svg>
                </a>
              ))}
            </div>
          </div>

          {/* Platform Column */}
          <div>
            <h4 className="font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wide text-xs">Platform</h4>
            <ul className="space-y-2 text-sm">
              {['Products', 'Calculator', 'Private Equity', 'How It Works', 'Plans'].map((item, i) => (
                <li key={i}>
                  <Link href={`/${item.toLowerCase().replace(' ', '-')}`} className="hover:text-blue-600 dark:hover:text-white transition-colors">
                    {item}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h4 className="font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wide text-xs">Company</h4>
            <ul className="space-y-2 text-sm">
              {['Our Team', 'Press', 'About', 'Careers', 'Contact'].map((item, i) => (
                <li key={i}>
                  <Link href={`/${item.toLowerCase().replace(' ', '-')}`} className="hover:text-blue-600 dark:hover:text-white transition-colors">
                    {item}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Resources Column */}
          <div>
            <h4 className="font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wide text-xs">Resources</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/blog" className="hover:text-blue-600 dark:hover:text-white transition-colors">Blog & Insights</Link></li>
              <li><Link href="/help-center" className="hover:text-blue-600 dark:hover:text-white transition-colors">Help Center</Link></li>
              <li><Link href="/faq" className="hover:text-blue-600 dark:hover:text-white transition-colors">FAQs</Link></li>
              <li><Link href="/grievance-redressal" className="hover:text-blue-600 dark:hover:text-white transition-colors">Grievance Redressal</Link></li>
              <li><Link href="/investor-charter" className="hover:text-blue-600 dark:hover:text-white transition-colors">Investor Charter</Link></li>
              <li><Link href="/aml-kyc-policy" className="hover:text-blue-600 dark:hover:text-white transition-colors">AML & KYC Policy</Link></li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h4 className="font-bold text-slate-900 dark:text-white mb-4 uppercase tracking-wide text-xs">Legal</h4>
            <ul className="space-y-2 text-sm">
              <li><Link href="/legal" className="hover:text-blue-600 dark:hover:text-white transition-colors">Legal Docs</Link></li>
              <li><Link href="/terms" className="hover:text-blue-600 dark:hover:text-white transition-colors">Terms of Service</Link></li>
              <li><Link href="/privacy-policy" className="hover:text-blue-600 dark:hover:text-white transition-colors">Privacy Policy</Link></li>
              <li><Link href="/cookie-policy" className="hover:text-blue-600 dark:hover:text-white transition-colors">Cookie Policy</Link></li>
              <li><Link href="/sebi-regulations" className="hover:text-blue-600 dark:hover:text-white transition-colors">SEBI Regulations</Link></li>
              <li><Link href="/refund-policy" className="hover:text-blue-600 dark:hover:text-white transition-colors">Refund Policy</Link></li>
              <li><Link href="/risk-disclosure" className="hover:text-blue-600 dark:hover:text-white transition-colors">Risk Disclosure</Link></li>
            </ul>
          </div>
        </div>

        {/* Footer Disclaimer */}
        <div className="border-t border-slate-200 dark:border-slate-800 pt-8 pb-4 transition-colors duration-300">
          <p className="text-xs text-slate-500 dark:text-gray-500 text-center mb-4 leading-relaxed max-w-5xl mx-auto">
            PreIPO SIP is a product of Pre IPO Sip Pvt Ltd. SEBI Registration No: INZ000421765  
            CIN: U65990MH2025OPC194372. GSTIN: 27AABCP1234Q1Z7. <br />
            Registered Office: PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India. <br /><br />
            <span className="font-semibold text-slate-600 dark:text-gray-400">Disclaimer:</span> Investments in unlisted shares, private equity, and Pre-IPO securities involve a high degree of risk. The value of investments may fluctuate and are subject to market risks. Past performance does not guarantee future returns. PreIPO SIP Private Limited is compliant with applicable SEBI guidelines. Investors are advised to read all legal documents, risk disclosures, and terms of use carefully before investing.
          </p>

          <div className="flex flex-col md:flex-row justify-between items-center mt-8 text-sm text-slate-500 dark:text-gray-400">
            <p>© 2025 PreIPOsip.com All rights reserved.</p>
            <div className="flex gap-4 mt-2 md:mt-0">
                <span className="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded border border-green-200 dark:border-green-800">SSL Secured</span>
                <span className="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800">ISO 27001</span>
            </div>
          </div>
        </div>

      </div>
    </footer>
  );
}