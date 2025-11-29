import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'Terms of Service | PreIPO SIP',
  description: 'Read our Terms of Service to understand the rules and regulations for using PreIPO SIP platform.',
};

export default function TermsOfServicePage() {
  return (
    <LegalDocumentViewer
      documentType="terms_of_service"
      title="Terms of Service"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
