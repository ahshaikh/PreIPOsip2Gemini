// V-PHASE5-1730-114
'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  FileCheck2,
  Package,
  Wallet,
  AreaChart,
  Gift,
  Users,
  LifeBuoy,
  User,
} from 'lucide-react';

const navItems = [
  { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/kyc', label: 'KYC Verification', icon: FileCheck2 },
  { href: '/subscription', label: 'My Subscription', icon: Package },
  { href: '/portfolio', label: 'My Portfolio', icon: AreaChart },
  { href: '/bonuses', label: 'My Bonuses', icon: Gift },
  { href: '/referrals', label: 'My Referrals', icon: Users },
  { href: '/wallet', label: 'My Wallet', icon: Wallet },
  { href: '/support', label: 'Support', icon: LifeBuoy },
  { href: '/profile', label: 'Profile', icon: User },
];

export function DashboardNav() {
  const pathname = usePathname();

  return (
    <nav className="flex flex-col space-y-1">
      {navItems.map((item) => (
        <Link
          key={item.href}
          href={item.href}
          className={cn(
            'flex items-center px-3 py-2 rounded-md text-sm font-medium',
            pathname === item.href
              ? 'bg-primary text-primary-foreground'
              : 'text-muted-foreground hover:bg-muted'
          )}
        >
          <item.icon className="mr-3 h-5 w-5" />
          <span>{item.label}</span>
        </Link>
      ))}
    </nav>
  );
}