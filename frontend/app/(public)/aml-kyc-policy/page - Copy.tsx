import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'AML & KYC Policy | PreIPO SIP',
  description: 'Learn about our Anti-Money Laundering and Know Your Customer policies and procedures.',
};

export default function AMLKYCPolicyPage() {
  return (
    <LegalDocumentViewer
      documentType="aml_kyc_policy"
      title="AML & KYC Policy"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
