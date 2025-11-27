import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'Privacy Policy | PreIPO SIP',
  description: 'Learn how PreIPO SIP collects, uses, and protects your personal information.',
};

export default function PrivacyPolicyPage() {
  return (
    <LegalDocumentViewer
      documentType="privacy_policy"
      title="Privacy Policy"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
