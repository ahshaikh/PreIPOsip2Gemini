'use client';

import { useEffect, useState } from 'react';
import { useAuth } from '@/context/AuthContext';
import api from '@/lib/api';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { AlertTriangle, FileText, CheckCircle, ExternalLink } from 'lucide-react';
import { toast } from "sonner";
import { useRouter } from 'next/navigation';

export default function ComplianceGuard({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const isAuthenticated = !!user;
  const [isBlockerOpen, setIsBlockerOpen] = useState(false);
  const router = useRouter();

  // Poll status ONLY when authenticated
  // FIX: Added error handling to prevent 401 errors from breaking the UI
  const { data: status, refetch } = useQuery({
    queryKey: ['complianceStatus', user?.id],
    queryFn: async () => {
      try {
        const response = await api.get('/user/compliance/status');
        return response.data;
      } catch (error: any) {
        // FIX: If 401, user is not authenticated - return compliant status to not block UI
        if (error.response?.status === 401) {
          console.warn('User not authenticated for compliance check');
          return { is_compliant: true, pending_documents: [] };
        }
        throw error;
      }
    },
    enabled: isAuthenticated && !!user,
    retry: false,
    staleTime: 0,
  });

  useEffect(() => {
    // If pending documents > 0, block the UI
    if (status && status.is_compliant === false) {
      setIsBlockerOpen(true);
    } else {
      setIsBlockerOpen(false);
    }
  }, [status]);

  // Bulk Accept Mutation
  const acceptMutation = useMutation({
    mutationFn: async () => {
        // Map all pending documents for batch acceptance
        const agreements = status.pending_documents.map((doc: any) => ({
            id: doc.id,
            version: doc.version
        }));
        return api.post('/user/compliance/accept', { agreements });
    },
    onSuccess: () => {
        toast.success("Agreements Accepted");
        setIsBlockerOpen(false);
        refetch(); // Sync state
    },
    onError: () => {
        toast.error("Failed to process acceptance. Please try again.");
    }
  });

  if (!isAuthenticated) return <>{children}</>;

  return (
    <>
      {/* The Blocker Dialog */}
      <Dialog open={isBlockerOpen}>
        <DialogContent className="sm:max-w-[500px]" showCloseButton={false} onInteractOutside={(e) => e.preventDefault()}>
          <DialogHeader>
            <div className="mx-auto bg-amber-100 p-3 rounded-full w-fit mb-4">
              <AlertTriangle className="h-8 w-8 text-amber-600" />
            </div>
            <DialogTitle className="text-center text-xl">Legal Updates Required</DialogTitle>
            <DialogDescription className="text-center pt-2">
              We have updated our legal agreements to better protect you. 
              To continue using the platform, please review and accept the changes.
            </DialogDescription>
          </DialogHeader>
          
          <div className="bg-muted/40 p-4 rounded-lg border my-4">
            <h4 className="text-sm font-semibold mb-3 text-foreground">Updated Documents:</h4>
            <ul className="space-y-2">
                {status?.pending_documents?.map((doc: any) => (
                <li key={doc.id} className="flex items-center justify-between text-sm">
                    <div className="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                        <FileText className="h-4 w-4 text-primary" />
                        <span>{doc.title}</span>
                        <span className="text-[10px] bg-slate-200 dark:bg-slate-800 px-1.5 py-0.5 rounded">v{doc.version}</span>
                    </div>
                    <Button 
                        variant="link" 
                        className="h-auto p-0 text-primary text-xs"
                        onClick={() => window.open(`/legal?type=${doc.type}`, '_blank')}
                    >
                        View <ExternalLink className="ml-1 h-3 w-3" />
                    </Button>
                </li>
                ))}
            </ul>
          </div>

          <DialogFooter className="flex-col sm:flex-col gap-2">
            <Button 
                className="w-full" 
                size="lg" 
                onClick={() => acceptMutation.mutate()}
                disabled={acceptMutation.isPending}
            >
                {acceptMutation.isPending ? "Processing..." : "I Agree to All Updated Terms"}
            </Button>
            <p className="text-xs text-center text-muted-foreground mt-2">
                By clicking "I Agree", you acknowledge that you have read and understood the updated documents.
            </p>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Content Blur when blocked */}
      <div className={isBlockerOpen ? "filter blur-sm pointer-events-none h-screen overflow-hidden" : ""}>
        {children}
      </div>
    </>
  );
}