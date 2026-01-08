/**
 * Backward Compatibility Shim for use-toast
 *
 * This project uses 'sonner' for toast notifications.
 * This file exists to maintain compatibility with any code that imports
 * from '@/components/ui/use-toast' instead of directly from 'sonner'.
 *
 * USAGE: Always prefer importing directly from 'sonner':
 * ✅ import { toast } from 'sonner';
 * ⚠️  import { toast } from '@/components/ui/use-toast'; // works but deprecated
 *
 * FIX: Resolves "Module not found: Can't resolve '@/components/ui/use-toast'" error
 */

import { toast } from 'sonner';

export { toast };
export { toast as useToast }; // Legacy hook name compatibility

// Re-export all sonner types for full compatibility
export type { ExternalToast, ToastT } from 'sonner';
