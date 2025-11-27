import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'Cookie Policy | PreIPO SIP',
  description: 'Learn about how we use cookies and similar technologies on PreIPO SIP platform.',
};

export default function CookiePolicyPage() {
  return (
    <LegalDocumentViewer
      documentType="cookie_policy"
      title="Cookie Policy"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
