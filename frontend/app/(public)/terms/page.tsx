import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'Terms of Service | PreIPO SIP',
  description: 'Terms and conditions governing your use of PreIPO SIP platform and services.',
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
