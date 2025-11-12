<?php
// V-PHASE6-1730-123 (REVISED v3)
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
  PieChart, // <-- 1. IMPORT NEW ICON
} from 'lucide-react';

const navItems = [
  { href: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/admin/users', label: 'User Management', icon: Users },
  { href: '/admin/kyc-queue', label: 'KYC Queue', icon: FileCheck2 },
  { href: '/admin/withdrawal-queue', label: 'Withdrawal Queue', icon: DollarSign },
  { href: '/admin/reports', label: 'Reports', icon: LineChart },
  { href: '/admin/lucky-draws', label: 'Lucky Draw', icon: Ticket },
  { href: '/admin/profit-sharing', label: 'Profit Sharing', icon: PieChart }, // <-- 2. ADD THIS LINK
  { href: '/admin/support', label: 'Support Tickets', icon: LifeBuoy },
];

const settingsNav = [
    // ... (rest of the file is unchanged)
// ...