'use client';

import { useState } from 'react';
import Link from 'next/link';
import { FileText, Shield, Lock, Cookie, RefreshCw, AlertTriangle, Scale, Users, ChevronRight, Search } from 'lucide-react';

export default function LegalDocsPage() {
  const [searchTerm, setSearchTerm] = useState('');

  const legalDocuments = [
    {
      title: 'Terms of Service',
      description: 'Comprehensive terms and conditions governing your use of our platform and services.',
      icon: FileText,
      href: '/terms',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Privacy Policy',
      description: 'How we collect, use, protect, and handle your personal information.',
      icon: Lock,
      href: '/privacy-policy',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Cookie Policy',
      description: 'Information about how we use cookies and similar tracking technologies.',
      icon: Cookie,
      href: '/cookie-policy',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'AML & KYC Policy',
      description: 'Our Anti-Money Laundering and Know Your Customer compliance procedures.',
      icon: Shield,
      href: '/aml-kyc-policy',
      category: 'Compliance',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Refund Policy',
      description: 'Terms and conditions regarding refunds, cancellations, and payment processing.',
      icon: RefreshCw,
      href: '/refund-policy',
      category: 'Policies',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Risk Disclosure',
      description: 'Important information about the risks associated with private market investments.',
      icon: AlertTriangle,
      href: '/risk-disclosure',
      category: 'Investment',
      lastUpdated: '2025-01-10',
    },
    {
      title: 'SEBI Regulations',
      description: 'Overview of SEBI regulations and compliance framework we adhere to.',
      icon: Scale,
      href: '/sebi-regulations',
      category: 'Regulatory',
      lastUpdated: '2025-01-10',
    },
    {
      title: 'Investor Charter',
      description: 'Your rights, responsibilities, and our commitments as outlined by regulatory authorities.',
      icon: Users,
      href: '/investor-charter',
      category: 'Investor Resources',
      lastUpdated: '2025-01-10',
    },
  ];

  const filteredDocuments = legalDocuments.filter(doc =>
    doc.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    doc.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
    doc.category.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const categories = Array.from(new Set(legalDocuments.map(doc => doc.category)));

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-16">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center">
            <div className="inline-flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full text-sm mb-4">
              <Scale className="w-4 h-4" />
              <span>Legal & Compliance Center</span>
            </div>
            <h1 className="text-4xl md:text-5xl font-bold mb-4">Legal Documents</h1>
            <p className="text-xl text-purple-100 max-w-3xl mx-auto">
              Complete transparency through comprehensive legal documentation.
              All policies, terms, and regulatory information in one place.
            </p>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 py-12">
        {/* Search Bar */}
        <div className="mb-8">
          <div className="relative max-w-xl mx-auto">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search legal documents..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
          </div>
        </div>

        {/* Document Categories */}
        {categories.map(category => {
          const categoryDocs = filteredDocuments.filter(doc => doc.category === category);

          if (categoryDocs.length === 0) return null;

          return (
            <div key={category} className="mb-12">
              <h2 className="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <div className="h-1 w-12 bg-gradient-to-r from-purple-600 to-blue-600 rounded"></div>
                {category}
              </h2>

              <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {categoryDocs.map((doc) => {
                  const Icon = doc.icon;
                  return (
                    <Link
                      key={doc.title}
                      href={doc.href}
                      className="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 hover:border-purple-300"
                    >
                      <div className="p-6">
                        {/* Icon & Title */}
                        <div className="flex items-start justify-between mb-4">
                          <div className="p-3 bg-gradient-to-br from-purple-50 to-blue-50 rounded-lg group-hover:from-purple-100 group-hover:to-blue-100 transition-colors">
                            <Icon className="w-6 h-6 text-purple-600" />
                          </div>
                          <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-purple-600 group-hover:translate-x-1 transition-all" />
                        </div>

                        {/* Title */}
                        <h3 className="text-lg font-semibold text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
                          {doc.title}
                        </h3>

                        {/* Description */}
                        <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                          {doc.description}
                        </p>

                        {/* Footer */}
                        <div className="flex items-center justify-between text-xs text-gray-500">
                          <span className="flex items-center gap-1">
                            <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                            Active
                          </span>
                          <span>Updated {doc.lastUpdated}</span>
                        </div>
                      </div>
                    </Link>
                  );
                })}
              </div>
            </div>
          );
        })}

        {filteredDocuments.length === 0 && (
          <div className="text-center py-12">
            <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <p className="text-gray-500 text-lg">No documents found matching your search.</p>
          </div>
        )}

        {/* Important Notice */}
        <div className="mt-12 bg-blue-50 border border-blue-200 rounded-xl p-6">
          <div className="flex gap-4">
            <div className="flex-shrink-0">
              <AlertTriangle className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <h3 className="font-semibold text-blue-900 mb-2">Important Notice</h3>
              <p className="text-sm text-blue-800 mb-4">
                All legal documents are regularly reviewed and updated to ensure compliance with current regulations.
                We recommend reviewing these documents periodically, especially before making investment decisions.
              </p>
              <p className="text-sm text-blue-800">
                For questions or clarifications regarding any legal document, please contact our{' '}
                <Link href="/help-center" className="font-semibold underline hover:text-blue-600">
                  Help Center
                </Link>
                {' '}or email{' '}
                <a href="mailto:legal@preiposip.com" className="font-semibold underline hover:text-blue-600">
                  legal@preiposip.com
                </a>
              </p>
            </div>
          </div>
        </div>

        {/* Compliance Statement */}
        <div className="mt-8 bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-6 border border-purple-200">
          <div className="flex items-center gap-3 mb-3">
            <Shield className="w-6 h-6 text-purple-600" />
            <h3 className="font-semibold text-purple-900">SEBI Compliant Platform</h3>
          </div>
          <p className="text-sm text-purple-800">
            PreIPO SIP is registered with SEBI (Registration No: INZ000421765) and adheres to all applicable
            regulations governing investment platforms in India. Our legal framework is designed to protect
            investor interests while maintaining the highest standards of transparency and compliance.
          </p>
        </div>
      </div>
    </div>
  );
}
