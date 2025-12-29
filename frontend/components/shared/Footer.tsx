// V-PHASE4-1730-104 (Fixed: Dark Mode Toggle Compatibility)
'use client'; // 1. Uncommented this to fix the hydration/Ref crash

import { Mail, Phone, MapPin } from 'lucide-react';
import Link from 'next/link';
import Image from 'next/image';

export default function Footer() {
  return (
    <footer className="bg-slate-50 dark:bg-[#1a1f2e] text-slate-600 dark:text-gray-300 py-12 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
      <div className="max-w-7xl mx-auto px-4">

        {/* Main Footer Content */}
        <div className="grid md:grid-cols-5 gap-8 mb-8">

          {/* Brand Column */}
          <div className="md:col-span-1">
            <div className="mb-4">
              {/* Company Logo */}
              <div className="mb-3 relative">
                 {/* 2. Added relative wrapper and error handling safety */}
                <Image
                  src="/preiposip.png"
                  alt="PreIPO SIP Logo"
                  width={120}
                  height={32}
                  className="object-contain dark:brightness-110 h-32 w-auto"
                  priority
                  unoptimized // Keep this only if you want to bypass Next.js Image Optimization API
                />
              </div>
              <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 rounded-lg font-bold text-xl inline-block shadow-sm">
                PreIPO SIP
              </div>
              <div className="text-xs text-purple-600 dark:text-purple-400 mt-1 flex items-center gap-1 font-medium">
                <span className="inline-block w-2 h-2 bg-purple-600 dark:bg-purple-400 rounded-full animate-pulse"></span>
                SEBI-aligned platform
              </div>
            </div>

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

        {/* Tagline Callout */}
        <div className="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-6 mb-8">
          <p className="text-center text-base md:text-lg font-semibold text-purple-900 dark:text-purple-100 leading-relaxed">
            Democratizing access to private market investments for retail investors.
          </p>
        </div>

        {/* Social Media Links */}
        <div className="flex flex-wrap justify-center items-center gap-6 mb-8 pb-8 border-b border-slate-200 dark:border-slate-800">
          <a
            href="https://linkedin.com/company/preiposip"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-slate-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 shadow-sm hover:shadow-md"
            aria-label="LinkedIn"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
            <span className="font-medium text-sm">LinkedIn</span>
          </a>

          <a
            href="https://twitter.com/preiposip"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-slate-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 shadow-sm hover:shadow-md"
            aria-label="Twitter"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            <span className="font-medium text-sm">Twitter</span>
          </a>

          <a
            href="https://facebook.com/preiposip"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-slate-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200 shadow-sm hover:shadow-md"
            aria-label="Facebook"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fillRule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clipRule="evenodd"/>
            </svg>
            <span className="font-medium text-sm">Facebook</span>
          </a>

          <a
            href="https://instagram.com/preiposip"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-pink-100 dark:hover:bg-pink-900/30 text-slate-700 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 transition-all duration-200 shadow-sm hover:shadow-md"
            aria-label="Instagram"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fillRule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clipRule="evenodd"/>
            </svg>
            <span className="font-medium text-sm">Instagram</span>
          </a>

          <a
            href="https://youtube.com/@preiposip"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-red-100 dark:hover:bg-red-900/30 text-slate-700 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200 shadow-sm hover:shadow-md"
            aria-label="YouTube"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path fillRule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z" clipRule="evenodd"/>
            </svg>
            <span className="font-medium text-sm">YouTube</span>
          </a>
        </div>

        {/* Footer Disclaimer */}
        <div className="border-t border-slate-200 dark:border-slate-800 pt-8 pb-4 transition-colors duration-300">
          <p className="text-xs text-slate-500 dark:text-gray-500 text-center mb-4 leading-relaxed max-w-5xl mx-auto">
            PreIPO SIP is a product of Pre IPO Sip Pvt Ltd. SEBI Registration No: INZ000421765
            CIN: U65990MH2025OPC194372. GSTIN: 27AABCP1234Q1Z7. <br />
            Registered Office: PreIPO SIP Private Limited, Office No. 14, 2nd Floor, Crystal Business Park, Near Golden Nest Road, Mira Bhayandar (East), Thane, Maharashtra – 401107, India. <br /><br />
            <span className="font-semibold text-slate-600 dark:text-gray-400">Disclaimer:</span> Investments in unlisted shares, private equity, and Pre-IPO securities involve a high degree of risk. The value of investments may fluctuate and are subject to market risks. Past performance does not indicate future outcomes. PreIPO SIP Private Limited is compliant with applicable SEBI guidelines. Investors are advised to read all legal documents, risk disclosures, and terms of use carefully before investing.
          </p>

          <div className="flex flex-col md:flex-row justify-between items-center mt-8 text-sm text-slate-500 dark:text-gray-400">
            <p>© {new Date().getFullYear()} PreIPOsip.com All rights reserved.</p>
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