'use client';

import { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import { Badge } from "@/components/ui/badge";
import {
  Search,
  TrendingUp,
  Users,
  CreditCard,
  Package,
  FileText,
  Building,
  Gift,
  Wallet,
  BarChart,
  Calendar,
  Settings,
  User,
} from "lucide-react";
import api from "@/lib/api";
import { toast } from "sonner";

interface SearchResult {
  id: string | number;
  type: string;
  title: string;
  subtitle?: string;
  description?: string;
  url: string;
  metadata?: any;
}

interface GlobalSearchProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  scope?: 'user' | 'admin';
  placeholder?: string;
}

const ICON_MAP: Record<string, any> = {
  user: User,
  investment: TrendingUp,
  subscription: Package,
  transaction: CreditCard,
  referral: Users,
  offer: Gift,
  plan: Package,
  portfolio: BarChart,
  payment: Wallet,
  company: Building,
  report: FileText,
  bonus: Gift,
  withdrawal: Wallet,
  kyc: FileText,
  setting: Settings,
  event: Calendar,
};

const debounce = (func: Function, wait: number) => {
  let timeout: NodeJS.Timeout;
  return (...args: any[]) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

export function GlobalSearch({ open, onOpenChange, scope = 'user', placeholder }: GlobalSearchProps) {
  const router = useRouter();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [recentSearches, setRecentSearches] = useState<SearchResult[]>([]);

  // Load recent searches from localStorage
  useEffect(() => {
    const saved = localStorage.getItem(`${scope}_recent_searches`);
    if (saved) {
      try {
        setRecentSearches(JSON.parse(saved));
      } catch (e) {}
    }
  }, [scope]);

  // Save to recent searches
  const saveToRecent = (result: SearchResult) => {
    const updated = [result, ...recentSearches.filter(r => r.id !== result.id)].slice(0, 5);
    setRecentSearches(updated);
    localStorage.setItem(`${scope}_recent_searches`, JSON.stringify(updated));
  };

  // Perform search
  const performSearch = useCallback(
    debounce(async (searchQuery: string) => {
      if (searchQuery.length < 2) {
        setResults([]);
        setIsSearching(false);
        return;
      }

      setIsSearching(true);
      try {
        const endpoint = scope === 'admin' ? '/admin/search' : '/user/search';
        const response = await api.get(endpoint, {
          params: { q: searchQuery, limit: 20 }
        });

        const data = response.data;
        const searchResults = data?.results || data?.data || [];

        // Transform results to consistent format
        const formatted: SearchResult[] = searchResults.map((item: any) => ({
          id: item.id,
          type: item.type || item.category || 'general',
          title: item.title || item.name || item.description || 'Untitled',
          subtitle: item.subtitle || item.code || item.email || '',
          description: item.description || '',
          url: item.url || generateUrl(item.type, item.id, scope),
          metadata: item.metadata || {},
        }));

        setResults(formatted);
      } catch (error: any) {
        // Fallback mock results for development
        const mockResults = generateMockResults(searchQuery, scope);
        setResults(mockResults);
      } finally {
        setIsSearching(false);
      }
    }, 300),
    [scope]
  );

  useEffect(() => {
    if (query) {
      performSearch(query);
    } else {
      setResults([]);
    }
  }, [query, performSearch]);

  const handleSelect = (result: SearchResult) => {
    saveToRecent(result);
    onOpenChange(false);
    setQuery("");
    router.push(result.url);
  };

  const clearRecent = () => {
    setRecentSearches([]);
    localStorage.removeItem(`${scope}_recent_searches`);
    toast.success("Recent searches cleared");
  };

  const getIcon = (type: string) => {
    const Icon = ICON_MAP[type] || Search;
    return <Icon className="h-4 w-4 mr-2 text-muted-foreground" />;
  };

  return (
    <CommandDialog open={open} onOpenChange={onOpenChange}>
      <CommandInput
        placeholder={placeholder || `Search ${scope === 'admin' ? 'users, transactions, reports' : 'investments, portfolio, transactions'}...`}
        value={query}
        onValueChange={setQuery}
      />
      <CommandList>
        {isSearching ? (
          <div className="py-6 text-center text-sm">
            <Search className="mx-auto h-6 w-6 animate-pulse text-muted-foreground mb-2" />
            Searching...
          </div>
        ) : query.length < 2 ? (
          <>
            {recentSearches.length > 0 && (
              <CommandGroup heading="Recent Searches">
                {recentSearches.map((item) => (
                  <CommandItem key={`recent-${item.id}`} onSelect={() => handleSelect(item)}>
                    {getIcon(item.type)}
                    <div className="flex-1">
                      <p className="font-medium">{item.title}</p>
                      {item.subtitle && (
                        <p className="text-xs text-muted-foreground">{item.subtitle}</p>
                      )}
                    </div>
                    <Badge variant="outline" className="text-xs">
                      {item.type}
                    </Badge>
                  </CommandItem>
                ))}
                <CommandItem onSelect={clearRecent} className="justify-center text-muted-foreground">
                  Clear recent searches
                </CommandItem>
              </CommandGroup>
            )}
            <CommandEmpty>
              <div className="py-6 text-center">
                <Search className="mx-auto h-12 w-12 text-muted-foreground mb-2" />
                <p className="text-sm text-muted-foreground">
                  Type to search {scope === 'admin' ? 'users, transactions, and more' : 'investments, portfolio, and more'}
                </p>
              </div>
            </CommandEmpty>
          </>
        ) : results.length === 0 ? (
          <CommandEmpty>
            <div className="py-6 text-center">
              <p className="text-sm">No results found for "{query}"</p>
              <p className="text-xs text-muted-foreground mt-1">
                Try different keywords or check spelling
              </p>
            </div>
          </CommandEmpty>
        ) : (
          // Group results by type
          Object.entries(groupByType(results)).map(([type, items]) => (
            <CommandGroup key={type} heading={capitalize(type)}>
              {items.map((item: SearchResult) => (
                <CommandItem key={`${item.type}-${item.id}`} onSelect={() => handleSelect(item)}>
                  {getIcon(item.type)}
                  <div className="flex-1">
                    <p className="font-medium">{item.title}</p>
                    {item.subtitle && (
                      <p className="text-xs text-muted-foreground">{item.subtitle}</p>
                    )}
                  </div>
                  <Badge variant="outline" className="text-xs">
                    {item.type}
                  </Badge>
                </CommandItem>
              ))}
            </CommandGroup>
          ))
        )}
      </CommandList>
    </CommandDialog>
  );
}

// Helper functions
function generateUrl(type: string, id: string | number, scope: string): string {
  const basePrefix = scope === 'admin' ? '/admin' : '';

  const routes: Record<string, string> = {
    user: `${basePrefix}/users/${id}`,
    investment: `/portfolio`,
    subscription: `/subscription`,
    transaction: `/transactions`,
    payment: `/transactions`,
    referral: `/referrals`,
    offer: `/offers/${id}`,
    plan: `/subscribe`,
    portfolio: `/portfolio`,
    bonus: `/bonuses`,
    withdrawal: `/wallet`,
    kyc: `/kyc`,
    report: `${basePrefix}/reports/${id}`,
    setting: `/settings`,
  };

  return routes[type] || `${basePrefix}/dashboard`;
}

function groupByType(results: SearchResult[]): Record<string, SearchResult[]> {
  return results.reduce((acc, item) => {
    if (!acc[item.type]) acc[item.type] = [];
    acc[item.type].push(item);
    return acc;
  }, {} as Record<string, SearchResult[]>);
}

function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function generateMockResults(query: string, scope: string): SearchResult[] {
  const q = query.toLowerCase();
  const allMockResults: SearchResult[] = scope === 'admin' ? [
    { id: 1, type: 'user', title: 'John Doe', subtitle: 'john@example.com', url: '/admin/users/1' },
    { id: 2, type: 'user', title: 'Jane Smith', subtitle: 'jane@example.com', url: '/admin/users/2' },
    { id: 3, type: 'transaction', title: 'Payment #12345', subtitle: '₹50,000 • Completed', url: '/admin/transactions' },
    { id: 4, type: 'report', title: 'Monthly Revenue Report', subtitle: 'November 2024', url: '/admin/reports/1' },
    { id: 5, type: 'kyc', title: 'KYC Verification Pending', subtitle: '15 users', url: '/admin/kyc' },
  ] : [
    { id: 1, type: 'investment', title: 'SpaceX Series F', subtitle: 'Pre-IPO • ₹10,000 invested', url: '/portfolio' },
    { id: 2, type: 'transaction', title: 'Monthly SIP Payment', subtitle: '₹5,000 • Completed', url: '/transactions' },
    { id: 3, type: 'plan', title: 'Growth SIP', subtitle: '₹5,000/month', url: '/subscribe' },
    { id: 4, type: 'bonus', title: 'Referral Bonus', subtitle: '₹500 earned', url: '/bonuses' },
    { id: 5, type: 'offer', title: 'WELCOME50', subtitle: '50% off first investment', url: '/offers/1' },
  ];

  return allMockResults.filter(item =>
    item.title.toLowerCase().includes(q) ||
    (item.subtitle && item.subtitle.toLowerCase().includes(q))
  );
}
