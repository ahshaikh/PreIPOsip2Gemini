"use client";

import { useState, useEffect } from "react";
import { useRouter, usePathname } from "next/navigation";
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
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import {
  Command,
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";
import api from "@/lib/api";
import {
  Search,
  Bell,
  Wallet,
  Sun,
  Moon,
  User,
  Settings,
  LogOut,
  Globe,
  Menu,
  X,
  TrendingUp,
  CreditCard,
  Package,
  BookOpen,
  Users,
  Download,
  Gift,
  ChevronDown,
  IndianRupee,
  Plus,
  Minus,
  History,
  FileCheck2,
  Building,
  HelpCircle,
  LifeBuoy,
  Percent,
  ExternalLink,
  Sparkles
} from "lucide-react";
import { Label } from "@/components/ui/label";

interface UserNotification {
  id: number;
  type: string;
  title: string;
  message: string;
  created_at: string;
  read: boolean;
}

export function UserTopNav({ user }: { user: any }) {
  const router = useRouter();
  const pathname = usePathname();
  const [searchOpen, setSearchOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [theme, setTheme] = useState<"light" | "dark">("light");
  const [language, setLanguage] = useState("en");
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [walletDialogOpen, setWalletDialogOpen] = useState(false);
  const [walletAction, setWalletAction] = useState<"add" | "withdraw">("add");
  const [announcementVisible, setAnnouncementVisible] = useState(true);

  // Fetch wallet balance
  const { data: walletData } = useQuery({
    queryKey: ["user-wallet"],
    queryFn: async () => {
      const response = await api.get("/user/wallet");
      return response.data;
    }
  });

  // Fetch notifications
  const { data: notifications, refetch: refetchNotifications } = useQuery({
    queryKey: ["user-notifications"],
    queryFn: async () => {
      const response = await api.get("/user/notifications");
      return response.data;
    },
    refetchInterval: 60000
  });

  // Fetch announcements
  const { data: announcement } = useQuery({
    queryKey: ["site-announcement"],
    queryFn: async () => {
      const response = await api.get("/announcements/latest");
      return response.data;
    }
  });

  // Fetch offers
  const { data: offers } = useQuery({
    queryKey: ["active-offers"],
    queryFn: async () => {
      const response = await api.get("/offers/active");
      return response.data;
    }
  });

  // Mock data
  const mockWallet = { balance: 12500, locked: 5000 };
  const mockNotifications: UserNotification[] = [
    { id: 1, type: "investment", title: "Investment Confirmed", message: "Your investment of ₹10,000 in SpaceX has been confirmed", created_at: "2 hours ago", read: false },
    { id: 2, type: "payment", title: "Payment Successful", message: "₹25,000 added to your wallet", created_at: "1 day ago", read: false },
    { id: 3, type: "kyc", title: "KYC Approved", message: "Your KYC verification is complete", created_at: "2 days ago", read: true },
    { id: 4, type: "offer", title: "New Offer!", message: "Get 2x referral bonus this weekend", created_at: "3 days ago", read: true },
    { id: 5, type: "reminder", title: "SIP Due", message: "Your monthly SIP of ₹5,000 is due tomorrow", created_at: "3 days ago", read: true },
  ];
  const mockAnnouncement = { id: 1, text: "New Pre-IPO available: SpaceX Round F — Invest Now!", link: "/investments/spacex" };
  const mockOffers = [
    { id: 1, code: "WELCOME50", description: "50% off on first investment", expiry: "2024-03-31" },
    { id: 2, code: "REFER2X", description: "2x referral bonus this month", expiry: "2024-03-25" },
  ];

  const wallet = walletData || mockWallet;
  const userNotifications = notifications || mockNotifications;
  const currentAnnouncement = announcement || mockAnnouncement;
  const activeOffers = offers || mockOffers;
  const unreadCount = userNotifications.filter((n: UserNotification) => !n.read).length;

  // Theme toggle
  useEffect(() => {
    const savedTheme = localStorage.getItem("user-theme") as "light" | "dark" || "light";
    setTheme(savedTheme);
    document.documentElement.classList.toggle("dark", savedTheme === "dark");
  }, []);

  const toggleTheme = () => {
    const newTheme = theme === "light" ? "dark" : "light";
    setTheme(newTheme);
    localStorage.setItem("user-theme", newTheme);
    document.documentElement.classList.toggle("dark", newTheme === "dark");
  };

  // Global search
  const handleSearch = async (query: string) => {
    setSearchQuery(query);
    if (query.length < 2) {
      setSearchResults([]);
      return;
    }

    setIsSearching(true);
    try {
      const response = await api.get(`/search?q=${encodeURIComponent(query)}`);
      setSearchResults(response.data);
    } catch (error) {
      setSearchResults([
        { type: "ipo", id: 1, title: "SpaceX Series F", subtitle: "Pre-IPO • Min ₹5,000" },
        { type: "company", id: 2, title: "Stripe Inc", subtitle: "Fintech • USA" },
        { type: "plan", id: 3, title: "Growth SIP", subtitle: "₹5,000/month" },
        { type: "portfolio", id: 4, title: "My SpaceX Holdings", subtitle: "₹1,00,000 invested" },
      ].filter(item =>
        item.title.toLowerCase().includes(query.toLowerCase()) ||
        item.subtitle.toLowerCase().includes(query.toLowerCase())
      ));
    } finally {
      setIsSearching(false);
    }
  };

  const handleSearchSelect = (item: any) => {
    setSearchOpen(false);
    const routes: Record<string, string> = {
      ipo: `/investments/${item.id}`,
      company: `/companies/${item.id}`,
      plan: `/plans/${item.id}`,
      portfolio: `/portfolio`,
    };
    router.push(routes[item.type] || "/dashboard");
  };

  const handleLogout = async () => {
    try {
      await api.post("/logout");
    } catch (e) {}
    localStorage.removeItem("auth_token");
    router.push("/login");
  };

  const openWalletDialog = (action: "add" | "withdraw") => {
    setWalletAction(action);
    setWalletDialogOpen(true);
  };

  const getNotificationIcon = (type: string) => {
    const icons: Record<string, any> = {
      investment: TrendingUp,
      payment: CreditCard,
      kyc: FileCheck2,
      offer: Gift,
      reminder: Bell
    };
    return icons[type] || Bell;
  };

  const mainLinks = [
    { href: "/portfolio", label: "Portfolio", icon: TrendingUp },
    { href: "/transactions", label: "Transactions", icon: CreditCard },
    { href: "/plans", label: "Plans", icon: Package },
  ];

  const marketingLinks = [
    { href: "/blog", label: "Learn", icon: BookOpen },
    { href: "/referrals", label: "Invite Friends", icon: Users },
    { href: "/materials", label: "Download", icon: Download },
  ];

  const languages = [
    { code: "en", name: "English" },
    { code: "hi", name: "हिंदी (Hindi)" },
    { code: "ta", name: "தமிழ் (Tamil)" },
    { code: "te", name: "తెలుగు (Telugu)" },
    { code: "mr", name: "मराठी (Marathi)" },
    { code: "bn", name: "বাংলা (Bengali)" },
  ];

  const isActive = (href: string) => pathname === href || pathname.startsWith(href + "/");

  return (
    <>
      {/* Announcement Bar */}
      {announcementVisible && currentAnnouncement && (
        <div className="bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-2 px-4">
          <div className="container mx-auto flex items-center justify-between">
            <div className="flex items-center gap-2 flex-1">
              <Sparkles className="h-4 w-4" />
              <p className="text-sm">
                {currentAnnouncement.text}
                {currentAnnouncement.link && (
                  <Link href={currentAnnouncement.link} className="ml-2 underline font-medium">
                    Invest Now →
                  </Link>
                )}
              </p>
            </div>
            <button onClick={() => setAnnouncementVisible(false)} className="p-1 hover:bg-white/10 rounded">
              <X className="h-4 w-4" />
            </button>
          </div>
        </div>
      )}

      {/* Main Navigation Bar */}
      <div className="sticky top-0 z-50 bg-background border-b">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <div className="flex items-center gap-6">
              <Link href="/dashboard" className="flex items-center gap-2">
                <div className="gradient-primary text-white px-3 py-1.5 rounded-lg font-bold text-lg">
                  PreIPO SIP
                </div>
              </Link>

              {/* Main Links - Desktop */}
              <nav className="hidden lg:flex items-center gap-1">
                {mainLinks.map((link) => (
                  <Link
                    key={link.href}
                    href={link.href}
                    className={cn(
                      "flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors",
                      isActive(link.href)
                        ? "bg-primary/10 text-primary"
                        : "text-muted-foreground hover:bg-muted hover:text-foreground"
                    )}
                  >
                    <link.icon className="h-4 w-4" />
                    {link.label}
                  </Link>
                ))}
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="text-muted-foreground">
                      More <ChevronDown className="h-4 w-4 ml-1" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    {marketingLinks.map((link) => (
                      <DropdownMenuItem key={link.href} asChild>
                        <Link href={link.href} className="flex items-center">
                          <link.icon className="mr-2 h-4 w-4" />
                          {link.label}
                        </Link>
                      </DropdownMenuItem>
                    ))}
                  </DropdownMenuContent>
                </DropdownMenu>
              </nav>
            </div>

            {/* Center - Search Bar */}
            <div className="hidden md:flex flex-1 max-w-md mx-4">
              <Button
                variant="outline"
                className="w-full justify-start text-muted-foreground"
                onClick={() => setSearchOpen(true)}
              >
                <Search className="h-4 w-4 mr-2" />
                <span className="hidden sm:inline">Search investments, companies, or plans...</span>
                <span className="sm:hidden">Search...</span>
              </Button>
            </div>

            {/* Right Side Actions */}
            <div className="flex items-center gap-2">
              {/* Offers Button */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm" className="hidden sm:flex text-orange-500">
                    <Gift className="h-4 w-4 mr-1" />
                    Offers
                    {activeOffers.length > 0 && (
                      <Badge variant="destructive" className="ml-1 h-5 w-5 p-0 flex items-center justify-center">
                        {activeOffers.length}
                      </Badge>
                    )}
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-72">
                  <DropdownMenuLabel>Active Offers</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {activeOffers.map((offer: any) => (
                    <DropdownMenuItem key={offer.id} className="flex flex-col items-start p-3">
                      <div className="flex items-center gap-2 w-full">
                        <Badge variant="outline" className="font-mono">{offer.code}</Badge>
                        <span className="text-xs text-muted-foreground ml-auto">Expires: {offer.expiry}</span>
                      </div>
                      <p className="text-sm mt-1">{offer.description}</p>
                    </DropdownMenuItem>
                  ))}
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild className="justify-center">
                    <Link href="/offers">View All Offers</Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Wallet Balance */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" size="sm" className="hidden sm:flex">
                    <Wallet className="h-4 w-4 mr-2 text-green-500" />
                    <span className="flex items-center">
                      <IndianRupee className="h-3 w-3" />
                      {wallet.balance?.toLocaleString()}
                    </span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>Wallet</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <div className="p-3 space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">Available</span>
                      <span className="font-medium flex items-center">
                        <IndianRupee className="h-3 w-3" />
                        {wallet.balance?.toLocaleString()}
                      </span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">Locked</span>
                      <span className="font-medium flex items-center">
                        <IndianRupee className="h-3 w-3" />
                        {wallet.locked?.toLocaleString()}
                      </span>
                    </div>
                  </div>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={() => openWalletDialog("add")}>
                    <Plus className="mr-2 h-4 w-4 text-green-500" />
                    Add Money
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => openWalletDialog("withdraw")}>
                    <Minus className="mr-2 h-4 w-4 text-orange-500" />
                    Withdraw Money
                  </DropdownMenuItem>
                  <DropdownMenuItem asChild>
                    <Link href="/wallet">
                      <History className="mr-2 h-4 w-4" />
                      Wallet History
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Notifications */}
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
                    Notifications
                    {unreadCount > 0 && (
                      <Badge variant="secondary">{unreadCount} new</Badge>
                    )}
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <div className="max-h-[350px] overflow-y-auto">
                    {userNotifications.map((notification: UserNotification) => {
                      const Icon = getNotificationIcon(notification.type);
                      return (
                        <DropdownMenuItem key={notification.id} className="flex items-start gap-3 p-3">
                          <div className={cn(
                            "p-2 rounded-full",
                            notification.read ? "bg-muted" : "bg-primary/10"
                          )}>
                            <Icon className={cn("h-4 w-4", notification.read ? "text-muted-foreground" : "text-primary")} />
                          </div>
                          <div className="flex-1">
                            <div className="flex items-center gap-2">
                              <p className={cn("text-sm", !notification.read && "font-medium")}>{notification.title}</p>
                              {!notification.read && <span className="h-2 w-2 rounded-full bg-blue-500" />}
                            </div>
                            <p className="text-xs text-muted-foreground">{notification.message}</p>
                            <p className="text-xs text-muted-foreground mt-1">{notification.created_at}</p>
                          </div>
                        </DropdownMenuItem>
                      );
                    })}
                  </div>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild className="justify-center">
                    <Link href="/notifications">View All Notifications</Link>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Theme Toggle */}
              <Button variant="ghost" size="icon" onClick={toggleTheme} className="hidden sm:flex">
                {theme === "light" ? <Moon className="h-5 w-5" /> : <Sun className="h-5 w-5" />}
              </Button>

              {/* Language Switcher */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon" className="hidden sm:flex">
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

              {/* User Profile Menu */}
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="flex items-center gap-2 px-2">
                    <Avatar className="h-8 w-8">
                      <AvatarImage src={user?.profile?.avatar_url} />
                      <AvatarFallback>
                        {user?.profile?.first_name?.[0] || user?.username?.[0] || "U"}
                      </AvatarFallback>
                    </Avatar>
                    <ChevronDown className="h-4 w-4 text-muted-foreground hidden sm:block" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                  <DropdownMenuLabel>
                    <div>
                      <p className="font-medium">{user?.profile?.first_name || user?.username}</p>
                      <p className="text-xs text-muted-foreground">{user?.email}</p>
                    </div>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                      <Link href="/profile">
                        <User className="mr-2 h-4 w-4" />
                        My Profile
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/kyc">
                        <FileCheck2 className="mr-2 h-4 w-4" />
                        KYC Status
                        {user?.kyc_status === "pending" && (
                          <Badge variant="secondary" className="ml-auto">Pending</Badge>
                        )}
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/bank-details">
                        <Building className="mr-2 h-4 w-4" />
                        Bank Details
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuGroup>
                  <DropdownMenuSeparator />
                  <DropdownMenuGroup>
                    <DropdownMenuItem asChild>
                      <Link href="/settings">
                        <Settings className="mr-2 h-4 w-4" />
                        Settings
                      </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                      <Link href="/support">
                        <LifeBuoy className="mr-2 h-4 w-4" />
                        Support / Help Center
                      </Link>
                    </DropdownMenuItem>
                  </DropdownMenuGroup>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={handleLogout} className="text-red-600">
                    <LogOut className="mr-2 h-4 w-4" />
                    Logout
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>

              {/* Mobile Menu */}
              <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon" className="lg:hidden">
                    <Menu className="h-5 w-5" />
                  </Button>
                </SheetTrigger>
                <SheetContent side="right" className="w-80">
                  <SheetHeader>
                    <SheetTitle>Menu</SheetTitle>
                  </SheetHeader>
                  <div className="mt-6 space-y-6">
                    {/* Mobile Search */}
                    <Button
                      variant="outline"
                      className="w-full justify-start"
                      onClick={() => { setMobileMenuOpen(false); setSearchOpen(true); }}
                    >
                      <Search className="h-4 w-4 mr-2" />
                      Search...
                    </Button>

                    {/* Mobile Wallet */}
                    <div className="p-4 bg-muted rounded-lg">
                      <div className="flex items-center justify-between mb-3">
                        <span className="text-sm text-muted-foreground">Wallet Balance</span>
                        <Wallet className="h-4 w-4 text-green-500" />
                      </div>
                      <p className="text-2xl font-bold flex items-center">
                        <IndianRupee className="h-5 w-5" />
                        {wallet.balance?.toLocaleString()}
                      </p>
                      <div className="flex gap-2 mt-3">
                        <Button size="sm" className="flex-1" onClick={() => { setMobileMenuOpen(false); openWalletDialog("add"); }}>
                          <Plus className="h-3 w-3 mr-1" /> Add
                        </Button>
                        <Button size="sm" variant="outline" className="flex-1" onClick={() => { setMobileMenuOpen(false); openWalletDialog("withdraw"); }}>
                          <Minus className="h-3 w-3 mr-1" /> Withdraw
                        </Button>
                      </div>
                    </div>

                    {/* Mobile Links */}
                    <div className="space-y-1">
                      <p className="text-xs font-semibold text-muted-foreground uppercase mb-2">Main</p>
                      {mainLinks.map((link) => (
                        <Link
                          key={link.href}
                          href={link.href}
                          onClick={() => setMobileMenuOpen(false)}
                          className={cn(
                            "flex items-center gap-3 px-3 py-2 rounded-md text-sm",
                            isActive(link.href) ? "bg-primary/10 text-primary" : "hover:bg-muted"
                          )}
                        >
                          <link.icon className="h-4 w-4" />
                          {link.label}
                        </Link>
                      ))}
                    </div>

                    <div className="space-y-1">
                      <p className="text-xs font-semibold text-muted-foreground uppercase mb-2">More</p>
                      {marketingLinks.map((link) => (
                        <Link
                          key={link.href}
                          href={link.href}
                          onClick={() => setMobileMenuOpen(false)}
                          className="flex items-center gap-3 px-3 py-2 rounded-md text-sm hover:bg-muted"
                        >
                          <link.icon className="h-4 w-4" />
                          {link.label}
                        </Link>
                      ))}
                    </div>

                    {/* Mobile Theme & Language */}
                    <div className="flex gap-2">
                      <Button variant="outline" className="flex-1" onClick={toggleTheme}>
                        {theme === "light" ? <Moon className="h-4 w-4 mr-2" /> : <Sun className="h-4 w-4 mr-2" />}
                        {theme === "light" ? "Dark" : "Light"}
                      </Button>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="outline" className="flex-1">
                            <Globe className="h-4 w-4 mr-2" />
                            Language
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                          {languages.map((lang) => (
                            <DropdownMenuItem key={lang.code} onClick={() => setLanguage(lang.code)}>
                              {lang.name}
                            </DropdownMenuItem>
                          ))}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </div>

      {/* Global Search Dialog */}
      <CommandDialog open={searchOpen} onOpenChange={setSearchOpen}>
        <CommandInput
          placeholder="Search investments, companies, or plans..."
          value={searchQuery}
          onValueChange={handleSearch}
        />
        <CommandList>
          <CommandEmpty>
            {isSearching ? "Searching..." : "No results found."}
          </CommandEmpty>
          {searchResults.length > 0 && (
            <>
              <CommandGroup heading="IPOs & Investments">
                {searchResults.filter(r => r.type === "ipo").map((result) => (
                  <CommandItem key={result.id} onSelect={() => handleSearchSelect(result)}>
                    <TrendingUp className="mr-2 h-4 w-4" />
                    <div>
                      <p>{result.title}</p>
                      <p className="text-xs text-muted-foreground">{result.subtitle}</p>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandGroup heading="Companies">
                {searchResults.filter(r => r.type === "company").map((result) => (
                  <CommandItem key={result.id} onSelect={() => handleSearchSelect(result)}>
                    <Building className="mr-2 h-4 w-4" />
                    <div>
                      <p>{result.title}</p>
                      <p className="text-xs text-muted-foreground">{result.subtitle}</p>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandGroup heading="Plans">
                {searchResults.filter(r => r.type === "plan").map((result) => (
                  <CommandItem key={result.id} onSelect={() => handleSearchSelect(result)}>
                    <Package className="mr-2 h-4 w-4" />
                    <div>
                      <p>{result.title}</p>
                      <p className="text-xs text-muted-foreground">{result.subtitle}</p>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
              <CommandGroup heading="My Portfolio">
                {searchResults.filter(r => r.type === "portfolio").map((result) => (
                  <CommandItem key={result.id} onSelect={() => handleSearchSelect(result)}>
                    <TrendingUp className="mr-2 h-4 w-4" />
                    <div>
                      <p>{result.title}</p>
                      <p className="text-xs text-muted-foreground">{result.subtitle}</p>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            </>
          )}
        </CommandList>
      </CommandDialog>

      {/* Wallet Dialog */}
      <Dialog open={walletDialogOpen} onOpenChange={setWalletDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {walletAction === "add" ? "Add Money to Wallet" : "Withdraw from Wallet"}
            </DialogTitle>
            <DialogDescription>
              {walletAction === "add"
                ? "Add funds to your wallet to invest in Pre-IPO opportunities"
                : "Withdraw available balance to your bank account"}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Amount (₹)</Label>
              <Input type="number" placeholder="Enter amount" />
            </div>
            {walletAction === "add" && (
              <div className="flex gap-2">
                {[1000, 5000, 10000, 25000].map((amount) => (
                  <Button key={amount} variant="outline" size="sm">
                    ₹{amount.toLocaleString()}
                  </Button>
                ))}
              </div>
            )}
            {walletAction === "withdraw" && (
              <p className="text-sm text-muted-foreground">
                Available balance: ₹{wallet.balance?.toLocaleString()}
              </p>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setWalletDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => { setWalletDialogOpen(false); router.push("/wallet"); }}>
              {walletAction === "add" ? "Proceed to Pay" : "Request Withdrawal"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
