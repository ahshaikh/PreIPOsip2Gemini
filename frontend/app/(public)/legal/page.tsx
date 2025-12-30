// V-FINAL-1730-666 | V-COMPLIANCE-ROUTER-FIXED
'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { 
  FileText, Shield, Lock, Cookie, RefreshCw, AlertTriangle, 
  Scale, Users, ChevronRight, Search, ChevronLeft, Printer, Calendar, 
  CheckCircle, Globe, Clock, UserCheck
} from 'lucide-react';
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from "@/lib/utils";

// Helper to decode HTML entities
const decodeHtml = (html: string) => {
  if (typeof window === 'undefined') return html;
  const txt = document.createElement("textarea");
  txt.innerHTML = html;
  return txt.value;
};

export default function LegalDocsPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const activeType = searchParams.get('type');
  const [searchTerm, setSearchTerm] = useState('');
  const [isMounted, setIsMounted] = useState(false);

  useEffect(() => setIsMounted(true), []);

  // 1. Fetch Dynamic Document Content
  const { data: document, isLoading, isError } = useQuery({
    queryKey: ['legalDocument', activeType],
    queryFn: async () => {
      if (!activeType) return null;
      try {
        const res = await api.get(`/legal/documents/${activeType}`);
        return res.data;
      } catch (error) {
        console.error("Error fetching document:", error);
        throw error;
      }
    },
    enabled: !!activeType,
    retry: 1,
    staleTime: 1000 * 60 * 5, 
  });

  // --- CONFIGURATION ---
  // Keys here MUST match 'type' in LegalAgreementSeeder.php
  const legalDocuments = [
    {
      title: 'Terms of Service',
      description: 'Comprehensive terms and conditions governing your use of our platform and services.',
      icon: FileText,
      href: '?type=terms_of_service',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Privacy Policy',
      description: 'How we collect, use, protect, and handle your personal information.',
      icon: Lock,
      href: '?type=privacy_policy',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Cookie Policy',
      description: 'Information about how we use cookies and similar tracking technologies.',
      icon: Cookie,
      href: '?type=cookie_policy',
      category: 'Core Documents',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'AML & KYC Policy',
      description: 'Our Anti-Money Laundering and Know Your Customer compliance procedures.',
      icon: Shield,
      href: '?type=aml_kyc_policy', // Matched with Seeder
      category: 'Compliance',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Refund Policy',
      description: 'Terms and conditions regarding refunds, cancellations, and payment processing.',
      icon: RefreshCw,
      href: '?type=refund_policy',
      category: 'Policies',
      lastUpdated: '2025-01-15',
    },
    {
      title: 'Risk Disclosure',
      description: 'Important information about the risks associated with private market investments.',
      icon: AlertTriangle,
      href: '?type=risk_disclosure', // Matched with Seeder
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

  // --- SMART CONTENT RENDERER ---
  // Converts plain text with numbering into structured HTML with proper classes
  const renderFormattedContent = (content: string) => {
    if (!isMounted || !content) return null;

    let safeContent = content;
    if (safeContent.includes('&lt;') || safeContent.includes('&gt;')) {
      safeContent = decodeHtml(safeContent);
    }

    // Check if it already has block tags
    if (/<\/?(p|div|h[1-6]|ul|ol)/i.test(safeContent)) {
      return <div dangerouslySetInnerHTML={{ __html: safeContent }} />;
    }

    // Process Plain Text Line-by-Line
    const lines = safeContent.split('\n');
    let formattedHTML = '';
    let inList = false;

    lines.forEach((line, index) => {
      const trimmed = line.trim();
      if (!trimmed) return;

      // Regex Patterns
      const isMainHeading = /^\d+\.\s/.test(trimmed);     // "8. Title"
      const isSubHeading = /^\d+\.\d+\s/.test(trimmed);   // "8.1 Title"
      const isSubSubHeading = /^\d+\.\d+\.\d+\s/.test(trimmed); // "8.1.1 Title"
      
      const prevLine = index > 0 ? lines[index - 1].trim() : '';
      const isListContext = prevLine.endsWith(':');
      const isBullet = /^[-•*]\s/.test(trimmed);

      if (isSubSubHeading) {
        if (inList) { formattedHTML += '</ul>'; inList = false; }
        formattedHTML += `<h5 class="text-md font-semibold text-slate-800 dark:text-slate-200 mt-4 mb-2">${trimmed}</h5>`;
      } 
      else if (isSubHeading) {
        if (inList) { formattedHTML += '</ul>'; inList = false; }
        formattedHTML += `<h4 class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-6 mb-3">${trimmed}</h4>`;
      } 
      else if (isMainHeading) {
        if (inList) { formattedHTML += '</ul>'; inList = false; }
        formattedHTML += `<h3 class="text-xl font-extrabold text-slate-900 dark:text-white mt-10 mb-4 border-b pb-2 border-slate-100 dark:border-slate-800">${trimmed}</h3>`;
      } 
      else if (isListContext || isBullet || inList) {
        if (!inList) {
            formattedHTML += '<ul class="list-disc pl-6 mb-4 space-y-2 text-slate-600 dark:text-slate-300">';
            inList = true;
        }
        const cleanText = trimmed.replace(/^[-•*]\s/, '');
        formattedHTML += `<li class="leading-relaxed">${cleanText}</li>`;

        const nextLine = index < lines.length - 1 ? lines[index+1].trim() : '';
        const nextIsHeading = /^\d/.test(nextLine);
        if (nextIsHeading || nextLine === '') {
            formattedHTML += '</ul>';
            inList = false;
        }
      } 
      else {
        if (inList) { formattedHTML += '</ul>'; inList = false; }
        formattedHTML += `<p class="mb-4 text-slate-600 dark:text-slate-300 leading-7">${trimmed}</p>`;
      }
    });

    if (inList) formattedHTML += '</ul>';

    return <div dangerouslySetInnerHTML={{ __html: formattedHTML }} />;
  };

  // --- VIEW 1: DYNAMIC DOCUMENT READER ---
  if (activeType) {
    return (
      <div className="min-h-screen bg-slate-50 dark:bg-slate-950 pt-20 pb-12">
        <div className="max-w-4xl mx-auto px-4">
          
          {/* Navigation Header */}
          <div className="flex items-center justify-between mb-6">
            <Button 
              variant="ghost" 
              onClick={() => router.push('/legal')}
              className="text-slate-600 hover:text-purple-600 hover:bg-purple-50 dark:text-slate-400 dark:hover:text-purple-400 dark:hover:bg-purple-900/20"
            >
              <ChevronLeft className="mr-2 h-4 w-4" /> Back to Compliance Center
            </Button>
            
            {document && (
              <div className="flex gap-2">
                <Button variant="outline" size="sm" onClick={() => window.print()}>
                  <Printer className="mr-2 h-4 w-4" /> Print
                </Button>
              </div>
            )}
          </div>

          {/* Loading State */}
          {isLoading && (
            <Card className="h-96 flex flex-col items-center justify-center border-dashed">
               <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mb-4"></div>
               <p className="text-muted-foreground">Retrieving secure document...</p>
            </Card>
          )}

          {/* Error State */}
          {isError && (
            <Card className="border-red-200 bg-red-50 dark:bg-red-900/10 p-8 text-center">
              <AlertTriangle className="h-12 w-12 text-red-500 mx-auto mb-4" />
              <h2 className="text-xl font-bold text-red-700 dark:text-red-400">Document Not Found</h2>
              <p className="text-red-600 dark:text-red-300 mt-2">The requested document ({activeType}) could not be loaded.</p>
              <Button onClick={() => router.push('/legal')} className="mt-6" variant="destructive">
                Return to Compliance Center
              </Button>
            </Card>
          )}

          {/* Document Content */}
          {document && (
            <Card className="shadow-xl overflow-hidden border-t-4 border-t-purple-600">
              <CardHeader className="bg-slate-50 dark:bg-slate-900 border-b pb-6">
                <div className="space-y-6">
                    {/* Title */}
                    <div>
                        <CardTitle className="text-3xl font-bold text-slate-900 dark:text-white">
                        {document.title}
                        </CardTitle>
                    </div>

                    {/* Metadata Grid (Requirement 5) */}
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4 p-4 bg-white dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 text-sm">
                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <Shield className="w-3 h-3" /> Version
                            </span>
                            <p className="font-semibold">v{document.version || '1.0.0'}</p>
                        </div>
                        
                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <Calendar className="w-3 h-3" /> Effective Date
                            </span>
                            <p className="font-semibold">{document.effective_date ? new Date(document.effective_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'long', year: 'numeric' }) : '01 January 2026'}</p>
                        </div>

                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <Clock className="w-3 h-3" /> Last Updated
                            </span>
                            <p className="font-semibold">{document.updated_at ? new Date(document.updated_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'long', year: 'numeric' }) : '30 December 2025'}</p>
                        </div>

                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <UserCheck className="w-3 h-3" /> Applies To
                            </span>
                            <p className="font-semibold">All registered users</p>
                        </div>

                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <Globe className="w-3 h-3" /> Governing Law
                            </span>
                            <p className="font-semibold">India</p>
                        </div>

                        <div className="space-y-1">
                            <span className="text-muted-foreground text-xs uppercase tracking-wider flex items-center gap-1">
                                <CheckCircle className="w-3 h-3 text-green-600" /> Status
                            </span>
                            <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">Active</Badge>
                        </div>
                    </div>
                </div>
              </CardHeader>
              
              <CardContent className="p-0">
                <ScrollArea className="h-[calc(100vh-350px)]">
                    <div className="p-8 md:p-12">
                        {renderFormattedContent(document.content)}
                    </div>
                    
                    {/* Legal Footer in Document */}
                    <div className="p-8 border-t bg-slate-50/50 dark:bg-slate-900/50 mt-8">
                        <div className="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                            <CheckCircle className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                            <div>
                                <h4 className="font-semibold text-blue-900 dark:text-blue-100 text-sm">Binding Agreement</h4>
                                <p className="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    By continuing to use PreIPOSIP services, you acknowledge that you have read, understood, and agreed to be bound by the terms contained in this document.
                                </p>
                            </div>
                        </div>
                    </div>
                </ScrollArea>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    );
  }

  // --- VIEW 2: DASHBOARD (Default) ---
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 pt-20">
      
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-16">
        <div className="max-w-7xl mx-auto px-4">
          <div className="text-center">
            <div className="inline-flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full text-sm mb-4 backdrop-blur-sm">
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
              className="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-slate-900 dark:text-white shadow-sm"
            />
          </div>
        </div>

        {/* Document Categories */}
        {categories.map(category => {
          const categoryDocs = filteredDocuments.filter(doc => doc.category === category);

          if (categoryDocs.length === 0) return null;

          return (
            <div key={category} className="mb-12">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <div className="h-1 w-12 bg-gradient-to-r from-purple-600 to-blue-600 rounded"></div>
                {category}
              </h2>

              <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {categoryDocs.map((doc) => {
                  const Icon = doc.icon;
                  const isDynamic = doc.href.startsWith('?');
                  
                  return (
                    <div
                      key={doc.title}
                      onClick={() => router.push(isDynamic ? `/legal${doc.href}` : doc.href)}
                      className="group bg-white dark:bg-slate-900 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 dark:border-slate-800 hover:border-purple-300 dark:hover:border-purple-700 cursor-pointer"
                    >
                      <div className="p-6">
                        {/* Icon & Title */}
                        <div className="flex items-start justify-between mb-4">
                          <div className="p-3 bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/30 dark:to-blue-900/30 rounded-lg group-hover:from-purple-100 group-hover:to-blue-100 dark:group-hover:from-purple-900/50 dark:group-hover:to-blue-900/50 transition-colors">
                            <Icon className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                          </div>
                          <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 group-hover:translate-x-1 transition-all" />
                        </div>

                        {/* Title */}
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                          {doc.title}
                        </h3>

                        {/* Description */}
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                          {doc.description}
                        </p>

                        {/* Footer */}
                        <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-500">
                          <span className="flex items-center gap-1">
                            <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                            Active
                          </span>
                          <span>Updated {doc.lastUpdated}</span>
                        </div>
                      </div>
                    </div>
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
        <div className="mt-12 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
          <div className="flex gap-4">
            <div className="flex-shrink-0">
              <AlertTriangle className="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">Important Notice</h3>
              <p className="text-sm text-blue-800 dark:text-blue-200 mb-4">
                All legal documents are regularly reviewed and updated to ensure compliance with current regulations.
                We recommend reviewing these documents periodically, especially before making investment decisions.
              </p>
              <p className="text-sm text-blue-800 dark:text-blue-200">
                For questions or clarifications regarding any legal document, please contact our{' '}
                <Link href="/help-center" className="font-semibold underline hover:text-blue-600 dark:hover:text-blue-400">
                  Help Center
                </Link>
                {' '}or email{' '}
                <a href="mailto:legal@preiposip.com" className="font-semibold underline hover:text-blue-600 dark:hover:text-blue-400">
                  legal@preiposip.com
                </a>
              </p>
            </div>
          </div>
        </div>

        {/* Compliance Statement */}
        <div className="mt-8 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/10 dark:to-blue-900/10 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
          <div className="flex items-center gap-3 mb-3">
            <Shield className="w-6 h-6 text-purple-600 dark:text-purple-400" />
            <h3 className="font-semibold text-purple-900 dark:text-purple-100">SEBI Compliant Platform</h3>
          </div>
          <p className="text-sm text-purple-800 dark:text-purple-200">
            PreIPO SIP is registered with SEBI (Registration No: INZ000421765) and adheres to all applicable
            regulations governing investment platforms in India. Our legal framework is designed to protect
            investor interests while maintaining the highest standards of transparency and compliance.
          </p>
        </div>
      </div>
    </div>
  );
}