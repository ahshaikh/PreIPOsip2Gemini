/**
 * PHASE 5 - Public Frontend: Mandatory Disclaimer Banner
 *
 * PURPOSE:
 * - Establish that public information is informational only
 * - No investment solicitation
 * - Investment requires subscription and platform review
 *
 * USAGE:
 * - Must be rendered on PublicCompanyList
 * - Must be rendered on PublicCompanyProfile
 *
 * DEFENSIVE PRINCIPLES:
 * - Disclaimer content is platform-owned, not issuer-controlled
 * - Clear, prominent, cannot be dismissed
 */

import { AlertTriangle, Info } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

interface PublicDisclaimerBannerProps {
  variant?: "default" | "prominent";
  className?: string;
}

export function PublicDisclaimerBanner({
  variant = "default",
  className = "",
}: PublicDisclaimerBannerProps) {
  if (variant === "prominent") {
    return (
      <div
        className={`bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-950/30 dark:to-yellow-950/30 border-2 border-amber-300 dark:border-amber-700 rounded-xl p-6 ${className}`}
      >
        <div className="flex items-start space-x-4">
          <AlertTriangle className="w-8 h-8 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-1" />
          <div className="flex-1">
            <h3 className="text-lg font-bold text-amber-900 dark:text-amber-200 mb-3">
              Important Notice
            </h3>
            <div className="space-y-2 text-sm text-amber-800 dark:text-amber-300">
              <p className="font-semibold">
                This information is for informational purposes only.
              </p>
              <ul className="list-disc list-inside space-y-1 ml-2">
                <li>
                  <strong>No Investment Solicitation:</strong> This page does not constitute an offer to sell or solicitation to buy securities.
                </li>
                <li>
                  <strong>Platform Review Required:</strong> Investment opportunities are available only to registered subscribers who have completed platform verification and review.
                </li>
                <li>
                  <strong>Not Investment Advice:</strong> Information presented here should not be considered as investment advice or a recommendation.
                </li>
                <li>
                  <strong>Risk Disclosure Required:</strong> Pre-IPO investments carry significant risks. Complete risk disclosures and acknowledgements are required before any investment can be made.
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Default variant - compact banner
  return (
    <Alert className={`border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/30 ${className}`}>
      <Info className="h-5 w-5 text-amber-600 dark:text-amber-400" />
      <AlertTitle className="text-amber-900 dark:text-amber-200 font-semibold">
        Information Only - Not Investment Advice
      </AlertTitle>
      <AlertDescription className="text-sm text-amber-800 dark:text-amber-300">
        Information presented is for informational purposes only and does not constitute investment solicitation.
        Investment opportunities require platform subscription, verification, and explicit risk acknowledgements.
        Pre-IPO investments carry significant risks and may result in total loss of capital.
      </AlertDescription>
    </Alert>
  );
}
