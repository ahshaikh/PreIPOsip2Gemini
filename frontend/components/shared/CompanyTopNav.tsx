'use client';

import { useEffect, useState } from 'react';
import { Bell, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Link from 'next/link';

export default function CompanyTopNav() {
  const [companyUser, setCompanyUser] = useState<any>(null);

  useEffect(() => {
    const user = localStorage.getItem('company_user');
    if (user) {
      setCompanyUser(JSON.parse(user));
    }
  }, []);

  return (
    <header className="h-16 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 fixed top-0 right-0 left-64 z-10">
      <div className="flex items-center justify-between h-full px-6">
        <div>
          <h2 className="text-xl font-semibold">
            {companyUser?.company_name || 'Company Dashboard'}
          </h2>
          {companyUser?.status === 'pending' && (
            <Badge variant="secondary" className="mt-1">
              Pending Approval
            </Badge>
          )}
          {companyUser?.status === 'active' && companyUser?.is_verified && (
            <Badge variant="default" className="mt-1 bg-green-600">
              ✓ Verified
            </Badge>
          )}
        </div>

        <div className="flex items-center gap-4">
          {/* Notifications */}
          <Button variant="ghost" size="icon" className="relative">
            <Bell className="h-5 w-5" />
            <span className="absolute top-1 right-1 h-2 w-2 bg-red-500 rounded-full" />
          </Button>

          {/* User Menu */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon">
                <User className="h-5 w-5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuLabel>
                <div className="flex flex-col">
                  <span className="font-medium">{companyUser?.contact_person_name}</span>
                  <span className="text-xs text-muted-foreground">{companyUser?.email}</span>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <Link href="/company/profile">Company Profile</Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href="/company/account">Account Settings</Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>
  );
}
