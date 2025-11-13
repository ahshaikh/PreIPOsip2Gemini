// V-FINAL-1730-248
'use client';

import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';

export function Navbar() {
  // Fetch global settings (menus, logo, etc.)
  const { data: settings } = useQuery({
    queryKey: ['globalSettings'],
    queryFn: async () => (await api.get('/global-settings')).data,
    staleTime: 60000, // Cache for 1 min
  });

  const headerMenu = settings?.menus?.['header-nav']?.items || [
    { label: 'Home', url: '/' },
    { label: 'Plans', url: '/plans' },
    // Fallback defaults
  ];

  return (
    <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      
      {/* Dynamic Top Bar Banner */}
      {settings?.banners?.map((banner: any) => (
          banner.type === 'top_bar' && (
              <div key={banner.id} className="bg-primary text-primary-foreground text-center text-sm py-2 px-4">
                  {banner.content}
              </div>
          )
      ))}

      <div className="container flex h-14 items-center">
        <div className="mr-4 flex">
          <Link href="/" className="mr-6 flex items-center space-x-2">
            {settings?.theme?.logo ? (
                <img src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${settings.theme.logo}`} alt="Logo" className="h-8" />
            ) : (
                <span className="font-bold">PreIPO SIP</span>
            )}
          </Link>
          <nav className="hidden md:flex items-center space-x-6 text-sm font-medium">
            {headerMenu.map((link: any, i: number) => (
              <Link
                key={i}
                href={link.url}
                className="text-foreground/60 transition-colors hover:text-foreground/80"
              >
                {link.label}
              </Link>
            ))}
          </nav>
        </div>
        
        <div className="flex flex-1 items-center justify-end space-x-2">
          <Button variant="ghost" asChild>
            <Link href="/login">Login</Link>
          </Button>
          <Button asChild>
            <Link href="/signup">Sign Up</Link>
          </Button>
        </div>
      </div>
    </header>
  );
}