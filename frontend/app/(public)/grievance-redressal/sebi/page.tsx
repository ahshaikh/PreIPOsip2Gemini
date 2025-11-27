import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'SEBI Compliance & Investment Disclaimer | PreIPO SIP',
  description: 'Important SEBI compliance information and investment disclaimers for PreIPO SIP platform users.',
};

export default function SebiCompliancePage() {
  return (
    <LegalDocumentViewer
      documentType="investment_disclaimer"
      title="SEBI Compliance & Investment Disclaimer"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
