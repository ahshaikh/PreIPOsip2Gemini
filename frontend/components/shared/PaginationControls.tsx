'use client';

import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";

// [FIX] Added 'from' and 'to' to the interface definition so page.tsx can pass them
export interface PaginationControlsProps {
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  totalItems?: number;
  from?: number;
  to?: number;

  // Legacy support for meta object
  meta?: {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
  };
}

export function PaginationControls({ 
  currentPage, 
  totalPages, 
  onPageChange, 
  totalItems, 
  from, 
  to,
  meta 
}: PaginationControlsProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  // 1. Normalize Data (Use direct props if available, else meta)
  const current = currentPage ?? meta?.current_page ?? 1;
  const total = totalPages ?? meta?.last_page ?? 1;
  const count = totalItems ?? meta?.total ?? 0;
  
  // [FIX] Use server-provided 'from'/'to' values if available
  const fromDisplay = from ?? meta?.from ?? ((current - 1) * 15 + 1); 
  const toDisplay = to ?? meta?.to ?? Math.min(fromDisplay + 14, count);

  // Guard: Don't show if only 1 page
  if (total <= 1) {
    return null;
  }

  const handlePageChange = (page: number) => {
    if (page < 1 || page > total) return;

    if (onPageChange) {
      onPageChange(page);
    } else {
      const params = new URLSearchParams(searchParams.toString());
      params.set('page', page.toString());
      router.push(`?${params.toString()}`);
    }
  };

  return (
    <div className="flex items-center justify-between px-2 py-4 border-t mt-4">
      {/* Result Counter */}
      <div className="text-sm text-muted-foreground hidden sm:block">
        Showing <span className="font-medium">{count > 0 ? fromDisplay : 0}</span> to <span className="font-medium">{toDisplay}</span> of <span className="font-medium">{count}</span> results
      </div>

      {/* Buttons */}
      <div className="flex items-center space-x-2 w-full sm:w-auto justify-center">
        <Button
          variant="outline"
          size="icon"
          className="h-8 w-8"
          onClick={() => handlePageChange(1)}
          disabled={current === 1}
          title="First Page"
        >
          <ChevronsLeft className="h-4 w-4" />
        </Button>
        
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(current - 1)}
          disabled={current === 1}
        >
          <ChevronLeft className="h-4 w-4 mr-1" />
          Previous
        </Button>

        <div className="text-sm font-medium px-4">
          Page {current} of {total}
        </div>

        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(current + 1)}
          disabled={current === total}
        >
          Next
          <ChevronRight className="h-4 w-4 ml-1" />
        </Button>

        <Button
          variant="outline"
          size="icon"
          className="h-8 w-8"
          onClick={() => handlePageChange(total)}
          disabled={current === total}
          title="Last Page"
        >
          <ChevronsRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}