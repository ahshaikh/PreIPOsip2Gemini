// V-PHASE6-1730-123 (Created) | V-FINAL-1730-278 | V-FINAL-1730-451 (Health/Log Links) | V-FINAL-1730-548 (Captcha Link Added)
'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  Users,
  FileCheck2,
  Package,
  ShoppingCart,
  DollarSign,
  Gift,
  Settings,
  ShieldCheck,
  FileText,
  LifeBuoy,
  LineChart,
  Ticket,
  PieChart,
  Mail,
  BookOpen,
  HelpCircle,
  CreditCard,
  Menu,
  Megaphone,
  Palette,
  Zap,
  Activity,
  Database,
  Shield,
  TrendingUp,
  Building2,
  GraduationCap,
  FileBarChart,
  Layers,
  Clock
} from 'lucide-react';

const navItems = [
  { href: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/admin/users', label: 'User Management', icon: Users },
  { href: '/admin/company-users', label: 'Company Users', icon: Building2 },
  { href: '/admin/payments', label: 'Payments', icon: CreditCard },
  { href: '/admin/kyc-queue', label: 'KYC Queue', icon: FileCheck2 },
  { href: '/admin/withdrawal-queue', label: 'Withdrawal Queue', icon: DollarSign },
  { href: '/admin/reports', label: 'Reports', icon: LineChart },
  { href: '/admin/campaigns', label: 'Campaigns', icon: Gift },
  { href: '/admin/lucky-draws', label: 'Lucky Draw', icon: Ticket },
  { href: '/admin/profit-sharing', label: 'Profit Sharing', icon: PieChart },
  { href: '/admin/support', label: 'Support Tickets', icon: LifeBuoy },
  { href: '/admin/help-center', label: 'Help Center', icon: BookOpen },
];

const contentManagementNav = [
    { href: '/admin/content/deals', label: 'Deals Management', icon: TrendingUp },
    { href: '/admin/content/companies', label: 'Companies', icon: Building2 },
    { href: '/admin/content/tutorials', label: 'Tutorials', icon: GraduationCap },
    { href: '/admin/content/reports', label: 'Reports & Analysis', icon: FileBarChart },
    { href: '/admin/content/sectors', label: 'Sectors', icon: Layers },
];

const notificationNav = [
    { href: '/admin/notifications/push', label: 'Push Notifications', icon: Mail },
];

const settingsNav = [
    { href: '/admin/settings/system', label: 'General Settings', icon: Settings },
    { href: '/admin/settings/plans', label: 'Plan Management', icon: Package },
    { href: '/admin/settings/product', label: 'Product Management', icon: ShoppingCart },
    { href: '/admin/settings/bonuses', label: 'Bonus Config', icon: Gift },
    { href: '/admin/settings/referral-campaigns', label: 'Referral Campaigns', icon: Zap },
    { href: '/admin/settings/roles', label: 'Role Management', icon: ShieldCheck },
    { href: '/admin/settings/ip-whitelist', label: 'IP Whitelist', icon: ShieldCheck },
    { href: '/admin/settings/captcha', label: 'CAPTCHA', icon: Shield }, // <-- NEW
    { href: '/admin/settings/compliance', label: 'Compliance', icon: ShieldCheck },
    { href: '/admin/settings/cms', label: 'CMS / Pages', icon: FileText },
    { href: '/admin/settings/menus', label: 'Menu Manager', icon: Menu },
    { href: '/admin/settings/banners', label: 'Banners & Popups', icon: Megaphone },
    { href: '/admin/settings/theme-seo', label: 'Theme & SEO', icon: Palette },
    { href: '/admin/settings/blog', label: 'Blog Manager', icon: BookOpen },
    { href: '/admin/settings/faq', label: 'FAQ Manager', icon: HelpCircle },
    { href: '/admin/settings/notifications', label: 'Notifications', icon: Mail },
    { href: '/admin/settings/system-health', label: 'System Health', icon: Activity },
    { href: '/admin/settings/activity', label: 'Global Audit Log', icon: FileText },
    { href: '/admin/settings/backups', label: 'Backups', icon: Database },
    { href: '/admin/settings/cron-jobs', label: 'Cron Jobs', icon: Clock },
];

export function AdminNav() {
  const pathname = usePathname();

  const renderLink = (item: any) => (
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
  );

  return (
    <nav className="flex flex-col space-y-1">
      <h4 className="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Main</h4>
      {navItems.map(renderLink)}

      <h4 className="px-3 pt-4 pb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Content Management</h4>
      {contentManagementNav.map(renderLink)}

      <h4 className="px-3 pt-4 pb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Notifications</h4>
      {notificationNav.map(renderLink)}

      <h4 className="px-3 pt-4 pb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Settings</h4>
      {settingsNav.map(renderLink)}
    </nav>
  );
}
