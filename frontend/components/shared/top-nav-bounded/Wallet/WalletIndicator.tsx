import { Wallet, Loader2, AlertCircle, IndianRupee } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { WalletBalance } from "@/types/domain/wallet";
import Link from "next/link";

interface WalletIndicatorProps {
  onAddFunds: () => void;
  onWithdraw: () => void;
}

export function WalletIndicator({ onAddFunds, onWithdraw }: WalletIndicatorProps) {
  const { data: wallet, isLoading, isError } = useQuery<WalletBalance>({
    queryKey: ["user-wallet"],
    queryFn: async () => {
      const res = await api.get("/user/wallet");
      // Adapter: Map nested backend structure to frontend interface
      const serverData = res.data;
      const walletData = serverData?.wallet;
      
      if (!walletData) throw new Error("Wallet data missing");

      // Handle float balance vs integer paise
      const rawBalance = walletData.balance ?? (walletData.balance_paise ? walletData.balance_paise / 100 : 0);
      const locked = walletData.locked_balance ?? (walletData.locked_amount ?? 0);

      return {
          currency: 'INR',
          amount: Number(rawBalance),
          // [PROTOCOL 1 FIX]: Space Optimization - Remove decimals (e.g. ₹5,000)
          formatted: new Intl.NumberFormat('en-IN', { 
              style: 'currency', 
              currency: 'INR',
              maximumFractionDigits: 0 
          }).format(Number(rawBalance)),
          is_locked: Number(locked) > 0,
          locked_amount: Number(locked),
          last_updated: walletData.updated_at || new Date().toISOString(),
      };
    },
    refetchInterval: 30000, 
  });

  if (isLoading) {
    return (
      <Button variant="outline" size="sm" disabled className="w-24 opacity-70">
        <Loader2 className="h-4 w-4 animate-spin mr-2" />
        ...
      </Button>
    );
  }

  if (isError || !wallet) {
    return (
      <Button variant="destructive" size="sm" className="gap-2">
        <AlertCircle className="h-4 w-4" />
        <span className="hidden sm:inline">Unavailable</span>
      </Button>
    );
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" className="hidden sm:flex gap-2">
          <Wallet className="h-4 w-4 text-green-600" />
          <span className="font-semibold flex items-center">
            {/* Display formatted amount without decimals */}
            {wallet.formatted}
          </span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-64">
        <DropdownMenuLabel>Wallet Balance</DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        <div className="p-4 space-y-3 bg-muted/30">
          <div className="flex justify-between items-center text-sm">
            <span className="text-muted-foreground">Available</span>
            <span className="font-bold text-lg text-primary flex items-center">
               {wallet.formatted}
            </span>
          </div>
          
          {wallet.is_locked && (
            <div className="flex justify-between items-center text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 p-2 rounded">
              <span>Locked</span>
              <span className="font-medium flex items-center">
                <IndianRupee className="h-3 w-3" /> {wallet.locked_amount.toLocaleString()}
              </span>
            </div>
          )}
        </div>

        <DropdownMenuSeparator />
        
        <DropdownMenuItem onClick={onAddFunds} className="cursor-pointer text-green-600 font-medium">
             + Add Funds
        </DropdownMenuItem>
        
        <DropdownMenuItem onClick={onWithdraw} className="cursor-pointer text-orange-600 font-medium">
             - Withdraw Funds
        </DropdownMenuItem>
        
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link href="/wallet" className="w-full cursor-pointer">
            View History
          </Link>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}