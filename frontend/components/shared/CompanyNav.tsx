'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard,
  Building2,
  FileText,
  FolderOpen,
  Users,
  TrendingUp,
  Newspaper,
  LogOut,
  User
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import companyApi from '@/lib/companyApi';

const navItems = [
  { href: '/company/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/company/profile', label: 'Company Profile', icon: Building2 },
  { href: '/company/financial-reports', label: 'Financial Reports', icon: FileText },
  { href: '/company/documents', label: 'Documents', icon: FolderOpen },
  { href: '/company/team', label: 'Team Members', icon: Users },
  { href: '/company/funding', label: 'Funding Rounds', icon: TrendingUp },
  { href: '/company/updates', label: 'News & Updates', icon: Newspaper },
];

const settingsNav = [
  { href: '/company/account', label: 'Account Settings', icon: User },
];

export default function CompanyNav() {
  const pathname = usePathname();
  const router = useRouter();

  const handleLogout = async () => {
    try {
      await companyApi.post('/logout');
      localStorage.removeItem('company_token');
      localStorage.removeItem('company_user');
      toast.success('Logged out successfully');
      router.push('/company/login');
    } catch (error) {
      toast.error('Logout failed');
    }
  };

  const renderLink = (item: { href: string; label: string; icon: any }) => {
    const Icon = item.icon;
    const isActive = pathname === item.href || pathname?.startsWith(item.href + '/');

    return (
      <Link
        key={item.href}
        href={item.href}
        className={`flex items-center gap-3 px-4 py-2.5 rounded-lg transition-colors ${
          isActive
            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100 font-medium'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
        }`}
      >
        <Icon className="h-5 w-5" />
        <span>{item.label}</span>
      </Link>
    );
  };

  return (
    <nav className="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 h-screen fixed left-0 top-0 overflow-y-auto p-4">
      <div className="mb-8">
        <Link href="/company/dashboard" className="flex items-center gap-2">
          <Building2 className="h-8 w-8 text-blue-600" />
          <div>
            <h1 className="text-lg font-bold">Company Portal</h1>
            <p className="text-xs text-muted-foreground">PreIPO SIP</p>
          </div>
        </Link>
      </div>

      <div className="space-y-1 mb-6">
        <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase px-4 mb-2">
          Main
        </h4>
        {navItems.map(renderLink)}
      </div>

      <div className="space-y-1 mb-6">
        <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase px-4 mb-2">
          Settings
        </h4>
        {settingsNav.map(renderLink)}
      </div>

      <div className="border-t border-gray-200 dark:border-gray-800 pt-4 mt-4">
        <Button
          variant="ghost"
          className="w-full justify-start text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
          onClick={handleLogout}
        >
          <LogOut className="mr-3 h-5 w-5" />
          Logout
        </Button>
      </div>
    </nav>
  );
}
