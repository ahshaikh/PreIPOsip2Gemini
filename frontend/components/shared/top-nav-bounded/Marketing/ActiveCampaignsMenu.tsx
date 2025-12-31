import { Gift } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import Link from "next/link";
import { CampaignOffer } from "@/types/domain/campaigns";

export function ActiveCampaignsMenu() {
  const { data: rawData, isLoading } = useQuery<any>({
    queryKey: ["active-offers"],
    queryFn: async () => {
      try {
          const res = await api.get("/campaigns/active");
          return res.data;
      } catch (e) {
          return [];
      }
    }
  });

  // [PROTOCOL 1 FIX] Normalize
  const offers: CampaignOffer[] = Array.isArray(rawData)
    ? rawData
    : Array.isArray(rawData?.data)
        ? rawData.data
        : Array.isArray(rawData?.offers) // Legacy support
            ? rawData.offers
            : [];

  if (isLoading || offers.length === 0) return null;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="sm" className="hidden sm:flex text-orange-600 hover:text-orange-700 hover:bg-orange-50">
          <Gift className="h-4 w-4 mr-1" />
          Offers
          <Badge variant="destructive" className="ml-1 h-5 w-5 p-0 flex items-center justify-center text-[10px]">
            {offers.length}
          </Badge>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex justify-between items-center">
             Active Campaigns
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        {offers.map((offer) => (
          <DropdownMenuItem key={offer.id} asChild>
            <Link href={`/offers/${offer.id}`} className="flex flex-col items-start p-3 cursor-pointer group">
              <div className="flex items-center gap-2 w-full justify-between">
                <Badge variant="outline" className="font-mono text-xs">{offer.code}</Badge>
                {offer.expiry && <span className="text-[10px] text-muted-foreground">{offer.expiry}</span>}
              </div>
              <p className="text-sm mt-1 font-medium">{offer.title}</p>
              <p className="text-xs text-muted-foreground line-clamp-1">{offer.description}</p>
            </Link>
          </DropdownMenuItem>
        ))}
        <DropdownMenuSeparator />
        <div className="p-2 bg-muted/20 text-[10px] text-muted-foreground text-center">
            *T&C Apply. Not investment advice.
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}