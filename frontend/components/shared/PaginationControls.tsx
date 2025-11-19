// V-POLISH-1730-176
'use client';

import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react";
import { useRouter, useSearchParams } from "next/navigation";

interface PaginationProps {
  meta: {
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
  };
}

export function PaginationControls({ meta }: PaginationProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  // Fix: Prevent crash if meta is missing or malformed
  if (!meta || typeof meta.last_page !== "number" || meta.last_page <= 1) {
    return null;
  }

  const handlePageChange = (page: number) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set('page', page.toString());
    router.push(`?${params.toString()}`);
  };

  return (
    <div className="flex items-center justify-between px-2 py-4">
      <div className="text-sm text-muted-foreground">
        Showing {meta.from} to {meta.to} of {meta.total} results
      </div>
      <div className="flex items-center space-x-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(1)}
          disabled={meta.current_page === 1}
        >
          <ChevronsLeft className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(meta.current_page - 1)}
          disabled={meta.current_page === 1}
        >
          <ChevronLeft className="h-4 w-4" />
          Previous
        </Button>
        <div className="text-sm font-medium">
          Page {meta.current_page} of {meta.last_page}
        </div>
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(meta.current_page + 1)}
          disabled={meta.current_page === meta.last_page}
        >
          Next
          <ChevronRight className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(meta.last_page)}
          disabled={meta.current_page === meta.last_page}
        >
          <ChevronsRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}
