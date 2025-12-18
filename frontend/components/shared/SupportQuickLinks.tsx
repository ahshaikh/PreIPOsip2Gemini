'use client';

import React from 'react';
import Link from 'next/link';
import { BookOpen, HelpCircle, Ticket, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface QuickLink {
  title: string;
  description: string;
  href: string;
  icon: React.ReactNode;
  color: string;
}

interface SupportQuickLinksProps {
  currentPage?: 'help-center' | 'faq' | 'support' | 'chat';
  className?: string;
}

export default function SupportQuickLinks({ currentPage, className = '' }: SupportQuickLinksProps) {
  const links: QuickLink[] = [
    {
      title: 'Help Center',
      description: 'Browse articles and guides',
      href: '/help-center',
      icon: <BookOpen className="w-5 h-5" />,
      color: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20'
    },
    {
      title: 'FAQs',
      description: 'Quick answers to common questions',
      href: '/faq',
      icon: <HelpCircle className="w-5 h-5" />,
      color: 'text-purple-600 bg-purple-50 dark:bg-purple-900/20'
    },
    {
      title: 'Support Tickets',
      description: 'Create or view your tickets',
      href: '/support',
      icon: <Ticket className="w-5 h-5" />,
      color: 'text-orange-600 bg-orange-50 dark:bg-orange-900/20'
    }
  ];

  return (
    <div className={`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ${className}`}>
      {links.map((link) => {
        const isCurrent = currentPage === link.href.replace('/', '');

        if (isCurrent) return null; // Don't show the current page link

        return (
          <Link key={link.title} href={link.href}>
            <Card className="group hover:shadow-lg transition-all duration-200 cursor-pointer border-2 hover:border-primary/50">
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className={`p-2 rounded-lg ${link.color}`}>
                    {link.icon}
                  </div>
                  <ArrowRight className="w-4 h-4 text-muted-foreground group-hover:text-primary group-hover:translate-x-1 transition-transform" />
                </div>
                <CardTitle className="text-base mt-3">{link.title}</CardTitle>
                <CardDescription className="text-sm">{link.description}</CardDescription>
              </CardHeader>
            </Card>
          </Link>
        );
      })}
    </div>
  );
}

// Compact version for sidebars
export function SupportQuickLinksCompact({ currentPage, className = '' }: SupportQuickLinksProps) {
  const links = [
    { title: 'Help Center', href: '/help-center', icon: <BookOpen className="w-4 h-4" /> },
    { title: 'FAQs', href: '/faq', icon: <HelpCircle className="w-4 h-4" /> },
    { title: 'Support', href: '/support', icon: <Ticket className="w-4 h-4" /> }
  ];

  return (
    <div className={`space-y-2 ${className}`}>
      <h3 className="text-sm font-semibold text-slate-900 dark:text-white mb-3">Need More Help?</h3>
      {links.map((link) => {
        const isCurrent = currentPage === link.href.replace('/', '');
        if (isCurrent) return null;

        return (
          <Link
            key={link.title}
            href={link.href}
            className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            {link.icon}
            {link.title}
          </Link>
        );
      })}
    </div>
  );
}
