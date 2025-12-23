"use client";

import { usePathname } from "next/navigation";
import Link from "next/link";
import Image from "next/image"; // Added Image import
import { useState, useRef, useEffect } from "react";
import { cn } from "@/lib/utils";
import {
  ChevronDown,
  Sun,
  Moon,
  Globe,
  Shield,
  CheckCircle2,
  TrendingUp,
  FileText,
  Users,
  Building2,
  BarChart3,
  Newspaper,
  BookOpen,
  HelpCircle,
  Mail,
  MessageSquare,
  Briefcase,
  Target,
  Award,
  Rocket,
} from "lucide-react";

interface NavItem {
  label: string;
  href?: string;
  items?: {
    icon: React.ElementType;
    label: string;
    href: string;
    description?: string;
  }[];
}

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [activeDropdown, setActiveDropdown] = useState<string | null>(null);
  const [darkMode, setDarkMode] = useState(false);
  const [language, setLanguage] = useState("en");
  const pathname = usePathname();
  const dropdownTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const isHome = pathname === "/";

  // Navigation structure
  const navigation: NavItem[] = [
    {
      label: "About",
      items: [
        {
          icon: Users,
          label: "Who We Are",
          href: "/about",
          description: "Learn about our mission and vision",
        },
        {
          icon: Rocket,
          label: "Our Story",
          href: "/about/story",
          description: "How we started and where we're going",
        },
        {
          icon: Target,
          label: "How PreIPOsip Works",
          href: "/how-it-works",
          description: "Understand our investment process",
        },
        {
          icon: Shield,
          label: "Why Trust Us",
          href: "/about/trust",
          description: "SEBI compliance and security",
        },
        {
          icon: Award,
          label: "Team",
          href: "/about/team",
          description: "Meet our expert team",
        },
      ],
    },
    {
      label: "Pre-IPO Listings",
      items: [
        {
          icon: TrendingUp,
          label: "Live Deals",
          href: "/products?filter=live",
          description: "Active pre-IPO opportunities",
        },
        {
          icon: Rocket,
          label: "Upcoming Deals",
          href: "/products?filter=upcoming",
          description: "Coming soon to market",
        },
        {
          icon: Building2,
          label: "Companies",
          href: "/products?view=companies",
          description: "Browse by company",
        },
        {
          icon: Briefcase,
          label: "Sectors",
          href: "/products?view=sectors",
          description: "Explore by industry",
        },
        {
          icon: BarChart3,
          label: "Compare Plans",
          href: "/plans",
          description: "Find the right plan for you",
        },
      ],
    },
    {
      label: "Insights",
      items: [
        {
          icon: BarChart3,
          label: "Market Analysis",
          href: "/insights/market",
          description: "Latest market trends and data",
        },
        {
          icon: FileText,
          label: "Reports",
          href: "/insights/reports",
          description: "In-depth industry reports",
        },
        {
          icon: Newspaper,
          label: "News & Updates",
          href: "/insights/news",
          description: "Stay updated with latest news",
        },
        {
          icon: BookOpen,
          label: "Tutorials",
          href: "/insights/tutorials",
          description: "Learn to invest in pre-IPOs",
        },
      ],
    },
    {
      label: "Pricing",
      href: "/plans",
    },
    {
      label: "Support",
      items: [
        {
          icon: HelpCircle,
          label: "FAQs",
          href: "/faq",
          description: "Common questions answered",
        },
        {
          icon: Mail,
          label: "Contact Us",
          href: "/contact",
          description: "Get in touch with our team",
        },
        {
          icon: BookOpen,
          label: "Help Center",
          href: "/help-center",
          description: "Browse our knowledge base",
        },
        {
          icon: MessageSquare,
          label: "Raise a Ticket",
          href: "/help-center/ticket",
          description: "Submit a support request",
        },
      ],
    },
  ];

  // Handle dropdown hover with delay
  const handleMouseEnter = (label: string) => {
    if (dropdownTimeoutRef.current) {
      clearTimeout(dropdownTimeoutRef.current);
    }
    setActiveDropdown(label);
  };

  const handleMouseLeave = () => {
    dropdownTimeoutRef.current = setTimeout(() => {
      setActiveDropdown(null);
    }, 200);
  };

  // Toggle dark mode
  const toggleDarkMode = () => {
    setDarkMode(!darkMode);
    // Add your dark mode logic here (e.g., toggle class on <html>)
    if (!darkMode) {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  };

  // Toggle language
  //const toggleLanguage = () => {
  //  setLanguage(language === "en" ? "hi" : "en");
    // Add your language switching logic here
  //};

  return (
    <nav
      className={cn(
        "fixed w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-sm z-50 transition-all duration-300 border-b border-gray-100 dark:border-slate-800"
      )}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* LOGO */}
            <Link href="/" className="flex items-center space-x-3 group">
            <div className="relative h-10 w-[150px] flex items-center bg-transparent">
                <Image
                    src="/preiposip.png"
                    alt="PreIPO SIP"
                    width={75}
                    height={20}
                    className="object-contain bg-transparent"
                />
              </div>

              <div className="hidden lg:flex items-center space-x-1 text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-full">
                {/* <Shield className="w-3 h-3" /> */}
                <span className="inline-block w-2 h-2 bg-purple-600 dark:bg-green-400 rounded-full animate-pulse"></span>
                <span className="font-medium">SEBI-aligned platform</span>
              </div>
            </Link>


          {/* Desktop Navigation */}
          <div className="hidden lg:flex items-center space-x-1">


            {/* Navigation Items with Dropdowns */}
            {navigation.map((item) => (
              <div
                key={item.label}
                className="relative"
                onMouseEnter={() => item.items && handleMouseEnter(item.label)}
                onMouseLeave={handleMouseLeave}
              >
                {item.href ? (
                  // Simple link
                  <Link
                    href={item.href}
                    className={cn(
                      "px-4 py-2 text-sm font-medium rounded-lg transition-colors inline-flex items-center space-x-1",
                      pathname === item.href
                        ? "text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20"
                        : "text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-gray-50 dark:hover:bg-slate-800"
                    )}
                  >
                    <span>{item.label}</span>
                  </Link>
                ) : (
                  // Dropdown trigger
                  <button
                    className={cn(
                      "px-4 py-2 text-sm font-medium rounded-lg transition-colors inline-flex items-center space-x-1",
                      activeDropdown === item.label
                        ? "text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20"
                        : "text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-gray-50 dark:hover:bg-slate-800"
                    )}
                  >
                    <span>{item.label}</span>
                    <ChevronDown
                      className={cn(
                        "w-4 h-4 transition-transform",
                        activeDropdown === item.label && "rotate-180"
                      )}
                    />
                  </button>
                )}

                {/* Mega Menu Dropdown */}
                {item.items && activeDropdown === item.label && (
                  <div className="absolute left-0 top-full mt-2 w-80 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                    <div className="p-2">
                      {item.items.map((subItem) => (
                        <Link
                          key={subItem.href}
                          href={subItem.href}
                          className="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors group"
                          onClick={() => setActiveDropdown(null)}
                        >
                          <div className="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <subItem.icon className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                              {subItem.label}
                            </div>
                            {subItem.description && (
                              <div className="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
                                {subItem.description}
                              </div>
                            )}
                          </div>
                        </Link>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>

          {/* Right Side: Theme Toggle, Language, CTAs */}
          <div className="flex items-center space-x-3">
            {/* Dark/Light Toggle */}
            <button
              onClick={toggleDarkMode}
              className="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
              aria-label="Toggle theme"
            >
              {darkMode ? (
                <Sun className="w-5 h-5" />
              ) : (
                <Moon className="w-5 h-5" />
              )}
            </button>

            {/* Language Switcher 
            <button
              onClick={toggleLanguage}
              className="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors flex items-center space-x-1"
              aria-label="Toggle language"
            >
              <Globe className="w-5 h-5" />
              <span className="text-xs font-medium uppercase">{language}</span>
            </button>
            */}
            {/* Login Button */}
            <Link
              href="/login"
              className="hidden sm:inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400 transition-colors"
            >
              Login
            </Link>

            {/* Get Started CTA */}
            <Link
              href="/signup"
              className="inline-flex items-center px-6 py-2.5 text-sm font-semibold text-white gradient-primary rounded-lg hover:shadow-lg hover:scale-105 transition-all duration-200"
            >
              Get Started
              <svg
                className="w-4 h-4 ml-1"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M13 7l5 5m0 0l-5 5m5-5H6"
                />
              </svg>
            </Link>

            {/* Mobile menu toggle */}
            <button
              className="lg:hidden p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              onClick={() => setOpen(!open)}
              aria-label="Toggle menu"
            >
              <div className="flex flex-col gap-1.5">
                <span
                  className={cn(
                    "w-6 h-0.5 bg-current transition-all",
                    open && "rotate-45 translate-y-2"
                  )}
                />
                <span
                  className={cn(
                    "w-6 h-0.5 bg-current transition-all",
                    open && "opacity-0"
                  )}
                />
                <span
                  className={cn(
                    "w-6 h-0.5 bg-current transition-all",
                    open && "-rotate-45 -translate-y-2"
                  )}
                />
              </div>
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Dropdown */}
      {open && (
        <div className="lg:hidden bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 max-h-[calc(100vh-4rem)] overflow-y-auto animate-in fade-in slide-in-from-top-2 duration-200">
          <div className="px-4 py-4 space-y-1">
            {/* Home */}
            <Link
              href="/"
              className="block px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
              onClick={() => setOpen(false)}
            >
              Home
            </Link>

            {/* Navigation Items */}
            {navigation.map((item) => (
              <div key={item.label}>
                {item.href ? (
                  <Link
                    href={item.href}
                    className="block px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                    onClick={() => setOpen(false)}
                  >
                    {item.label}
                  </Link>
                ) : (
                  <>
                    <div className="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {item.label}
                    </div>
                    <div className="space-y-1">
                      {item.items?.map((subItem) => (
                        <Link
                          key={subItem.href}
                          href={subItem.href}
                          className="flex items-center space-x-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-lg transition-colors"
                          onClick={() => setOpen(false)}
                        >
                          <subItem.icon className="w-4 h-4 text-gray-400" />
                          <span>{subItem.label}</span>
                        </Link>
                      ))}
                    </div>
                  </>
                )}
              </div>
            ))}

            {/* Mobile CTAs */}
            <div className="pt-4 space-y-2 border-t border-gray-200 dark:border-slate-800">
              <Link
                href="/login"
                className="block px-4 py-3 text-sm font-semibold text-center text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-800 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                onClick={() => setOpen(false)}
              >
                Login
              </Link>
              <Link
                href="/signup"
                className="block px-4 py-3 text-sm font-semibold text-center text-white gradient-primary rounded-lg hover:shadow-lg transition-shadow"
                onClick={() => setOpen(false)}
              >
                Get Started →
              </Link>
            </div>

            {/* Trust Badge Mobile */}
            <div className="flex items-center justify-center space-x-2 py-4 text-xs text-emerald-600 dark:text-emerald-400">
              <Shield className="w-4 h-4" />
              <span className="font-medium">SEBI Compliant & Secure Platform</span>
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
        </div>
      )}
    </nav>
  );
}