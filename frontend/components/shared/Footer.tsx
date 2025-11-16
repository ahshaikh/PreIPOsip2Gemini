// V-PHASE4-1730-104 (Created) | V-FINAL-1730-524 (Dynamic CMS)
'use client';

import Link from 'next/link';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';

export function Footer() {
  // Fetch global settings
  const { data: settings } = useQuery({
    queryKey: ['globalSettings'],
    queryFn: async ()_ => (await api.get('/global-settings')).data,
    staleTime: 60000,
  });

  // FSD-FRONT-003: Dynamic Footer Menus
  const companyLinks = settings?.menus?.['footer-company']?.items || [
    { label: 'About Us', url: '/about' },
  ];
  const legalLinks = settings?.menus?.['footer-legal']?.items || [
    { label: 'Privacy Policy', url: '/privacy' },
    { label: 'Terms', url: '/terms' },
  ];

  return (
    <footer className="border-t">
      <div className="container py-12">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div>
            <h4 className="font-semibold mb-2">Company</h4>
            {companyLinks.map((link: any, i: number) => (
              <Link key={i} href={link.url} className="block text-sm text-muted-foreground hover:underline">
                {link.label}
              </Link>
            ))}
          </div>
          <div>
            <h4 className="font-semibold mb-2">Legal</h4>
            {legalLinks.map((link: any, i: number) => (
              <Link key={i} href={link.url} className="block text-sm text-muted-foreground hover:underline">
                {link.label}
              </Link>
            ))}
          </div>
          {/* (Other columns can be added here) */}
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