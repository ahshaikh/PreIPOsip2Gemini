"use client";

import { useState, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { GlobalSearch } from "@/components/shared/GlobalSearch";
import { cn } from "@/lib/utils";
import api from "@/lib/api";
import {
  Search,
  Bell,
  Plus,
  Sun,
  Moon,
  User,
  Settings,
  LogOut,
  Globe,
  Users,
  CreditCard,
  FileCheck2,
  LifeBuoy,
  Package,
  FileText,
  BookOpen,
  Megaphone,
  ChevronDown,
  AlertCircle,
  DollarSign,
  UserPlus,
  AlertTriangle,
  Database,
  Shield,
  Activity,
  Key,
  RefreshCw,
  TrendingUp
} from "lucide-react";

interface SystemNotification {
  id: number;
  type: string;
  title: string;
  message: string;
  count?: number;
  created_at: string;
  read: boolean;
}

export function AdminTopNav({ user }: { user: any }) {
  const router = useRouter();
  const [searchOpen, setSearchOpen] = useState(false);
  const [theme, setTheme] = useState<"light" | "dark">("light");
  const [language, setLanguage] = useState("en");

  // Fetch system notifications with defensive handling
  const { data: systemNotifications, refetch: refetchNotifications } = useQuery({
    queryKey: ["admin-system-notifications"],
    queryFn: async () => {
      const response = await api.get("/admin/notifications/system");
      const data = response.data;
      // Handle nested data structures
      if (Array.isArray(data)) return data;
      if (data?.data && Array.isArray(data.data)) return data.data;
      if (data?.notifications && Array.isArray(data.notifications)) return data.notifications;
      return [];
    },
    refetchInterval: 30000 // Refresh every 30 seconds
  });

  // Mock system notifications for display
  const mockNotifications: SystemNotification[] = [
    { id: 1, type: "kyc", title: "Pending KYC", message: "users awaiting verification", count: 12, created_at: "2024-03-15 10:30", read: false },
    { id: 2, type: "withdrawal", title: "New Withdrawal Requests", message: "requests pending approval", count: 5, created_at: "2024-03-15 10:25", read: false },
    { id: 3, type: "deposit", title: "Deposit Issues", message: "failed deposits need attention", count: 2, created_at: "2024-03-15 10:20", read: false },
    { id: 4, type: "payment", title: "Failed Payment Callbacks", message: "payment callbacks failed", count: 3, created_at: "2024-03-15 10:15", read: true },
    { id: 5, type: "user", title: "New User Signups", message: "new users registered today", count: 28, created_at: "2024-03-15 09:00", read: true },
    { id: 6, type: "system", title: "System Alert", message: "Database backup completed", created_at: "2024-03-15 06:00", read: true },
  ];

  const notifications = Array.isArray(systemNotifications) && systemNotifications.length > 0
    ? systemNotifications
    : mockNotifications;
  const unreadCount = notifications.filter((n: SystemNotification) => !n.read).length;

  // Theme toggle
  useEffect(() => {
    const savedTheme = localStorage.getItem("admin-theme") as "light" | "dark" || "light";
    setTheme(savedTheme);
    document.documentElement.classList.toggle("dark", savedTheme === "dark");
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === "light" ? "dark" : "light";
    setTheme(newTheme);
    localStorage.setItem("admin-theme", newTheme);
    document.documentElement.classList.toggle("dark", newTheme === "dark");
  };

  const handleLogout = async () => {
    try {
      await api.post("/logout");
    } catch (e) {}
    localStorage.removeItem("auth_token");
    router.push("/login");
  };

  const getNotificationIcon = (type: string) => {
    const icons: Record<string, any> = {
      kyc: FileCheck2,
      withdrawal: DollarSign,
      deposit: AlertCircle,
      payment: CreditCard,
      user: UserPlus,
      system: AlertTriangle
    };
    return icons[type] || Bell;
  };

  const getNotificationColor = (type: string) => {
    const colors: Record<string, string> = {
      kyc: "text-blue-500",
      withdrawal: "text-orange-500",
      deposit: "text-red-500",
      payment: "text-red-500",
      user: "text-green-500",
      system: "text-yellow-500"
    };
    return colors[type] || "text-gray-500";
  };

  const quickActions = [
    { label: "Add New Investment", icon: TrendingUp, href: "/admin/investments/new" },
    { label: "Add New Administrator", icon: Shield, href: "/admin/settings/roles?action=add" },
    { label: "Add New Blog Post", icon: BookOpen, href: "/admin/settings/blog?action=new" },
    { label: "Create Announcement", icon: Megaphone, href: "/admin/settings/banners?action=new" },
    { label: "Add New Plan", icon: Package, href: "/admin/settings/plans?action=new" },
  ];

  const languages = [
    { code: "en", name: "English" },
    { code: "hi", name: "हिंदी (Hindi)" },
    { code: "ta", name: "தமிழ் (Tamil)" },
    { code: "te", name: "తెలుగు (Telugu)" },
    { code: "mr", name: "मराठी (Marathi)" },
    { code: "bn", name: "বাংলা (Bengali)" },
  ];

  return (
    <>
      <div className="fixed top-0 left-0 right-0 z-50 bg-background border-b">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <div className="flex items-center gap-4">
              <Link href="/admin/dashboard" className="flex items-center gap-2">
                <div className="gradient-primary text-white px-3 py-1.5 rounded-lg font-bold text-lg">
                  PreIPO SIP
                </div>
                <Badge variant="secondary" className="text-xs">Admin</Badge>
              </Link>
            </div>

            {/* Center - Global Search */}
            <div className="flex-1 max-w-xl mx-8">
              <Button
                variant="outline"
                className="w-full justify-start text-muted-foreground"
                onClick={() => setSearchOpen(true)}
              >
                <Search className="h-4 w-4 mr-2" />
                <span>Search users, transactions, investments...</span>
                <kbd className="ml-auto pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
                  ⌘K
                </kbd>
              </Button>
            </div>

            {/* Right Side Actions */}
            <div className="flex items-center gap-2">
              {/* Quick Actions */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="default" size="sm">
                    <Plus className="h-4 w-4 mr-1" />
                    Add New
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>Quick Actions</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {quickActions.map((action) => (
                    <DropdownMenuItem key={action.label} asChild>
                      <Link href={action.href} className="flex items-center">
                        <action.icon className="mr-2 h-4 w-4" />
                        {action.label}
                      </Link>
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>

              {/* System Notifications */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon" className="relative">
                    <Bell className="h-5 w-5" />
                    {unreadCount > 0 && (
                      <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">
                        {unreadCount}
                      </span>
                    )}
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-80">
                  <DropdownMenuLabel className="flex items-center justify-between">
                    System Notifications
                    <Button variant="ghost" size="sm" onClick={() => refetchNotifications()}>
                      <RefreshCw className="h-3 w-3" />
                    </Button>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <div className="max-h-[400px] overflow-y-auto">
                    {notifications.map((notification: SystemNotification) => {
                      const Icon = getNotificationIcon(notification.type);
                      return (
                        <DropdownMenuItem key={notification.id} className="flex items-start gap-3 p-3">
                          <Icon className={cn("h-5 w-5 mt-0.5", getNotificationColor(notification.type))} />
                          <div className="flex-1">
                            <div className="flex items-center gap-2">
                              <p className="font-medium text-sm">{notification.title}</p>
                              {!notification.read && (
                                <span className="h-2 w-2 rounded-full bg-blue-500" />
                              )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                              {notification.count && <span className="font-bold">{notification.count} </span>}
                              {notification.message}
                            </p>
                            <p className="text-xs text-muted-foreground mt-1">{notification.created_at}</p>
                          </div>
                        </DropdownMenuItem>
                      );
                    })}
                  </div>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild className="justify-center">
                    <Link href="/admin/notifications">View All Notifications</Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Theme Toggle */}
              <Button variant="ghost" size="icon" onClick={toggleTheme}>
                {theme === "light" ? <Moon className="h-5 w-5" /> : <Sun className="h-5 w-5" />}
              </Button>

              {/* Language Switcher */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon">
                    <Globe className="h-5 w-5" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuLabel>Language</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {languages.map((lang) => (
                    <DropdownMenuItem
                      key={lang.code}
                      onClick={() => setLanguage(lang.code)}
                      className={cn(language === lang.code && "bg-muted")}
                    >
                      {lang.name}
                      {language === lang.code && <span className="ml-auto">✓</span>}
                    </DropdownMenuItem>
                  ))}
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Admin Profile Menu */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="flex items-center gap-2 px-2">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={user?.profile?.avatar_url} />
                      <AvatarFallback>
                        {user?.profile?.first_name?.[0] || user?.username?.[0] || "A"}
                      </AvatarFallback>
                    </Avatar>
                    <div className="hidden md:block text-left">
                      <p className="text-sm font-medium">
                        {user?.profile?.first_name || user?.username}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {user?.role === "super_admin" ? "Super Admin" : "Admin"}
                      </p>
                    </div>
                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>My Account</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                      <Link href="/admin/profile">
                        <User className="mr-2 h-4 w-4" />
                        My Profile
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/admin/profile/password">
                        <Key className="mr-2 h-4 w-4" />
                        Change Password
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuGroup>
                  <DropdownMenuSeparator />
                  <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                      <Link href="/admin/settings/activity">
                        <Activity className="mr-2 h-4 w-4" />
                        Activity Logs
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/admin/settings/system-health">
                        <Database className="mr-2 h-4 w-4" />
                        System Logs
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuGroup>
                  <DropdownMenuSeparator />
                  <DropdownMenuGroup>
                    <DropdownMenuLabel className="text-xs text-muted-foreground">Switch Role</DropdownMenuLabel>
                    <DropdownMenuItem>
                      <Shield className="mr-2 h-4 w-4" />
                      Super Admin
                    </DropdownMenuItem>
                    <DropdownMenuItem>
                      <FileCheck2 className="mr-2 h-4 w-4" />
                      KYC Team
                    </DropdownMenuItem>
                    <DropdownMenuItem>
                      <DollarSign className="mr-2 h-4 w-4" />
                      Finance Team
                    </DropdownMenuItem>
                  </DropdownMenuGroup>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={handleLogout} className="text-red-600">
                    <LogOut className="mr-2 h-4 w-4" />
                    Logout
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </div>
      </div>

      {/* Global Search Component */}
      <GlobalSearch
        open={searchOpen}
        onOpenChange={setSearchOpen}
        scope="admin"
        placeholder="Search users, transactions, investments, tickets, KYC..."
      />
    </>
  );
}
