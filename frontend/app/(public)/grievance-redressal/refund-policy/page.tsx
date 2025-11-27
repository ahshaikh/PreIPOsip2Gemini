import LegalDocumentViewer from "@/components/legal/LegalDocumentViewer";

export const metadata = {
  title: 'Refund Policy | PreIPO SIP',
  description: 'Understand our refund policy and the terms for requesting refunds on PreIPO SIP platform.',
};

export default function RefundPolicyPage() {
  return (
    <LegalDocumentViewer
      documentType="refund_policy"
      title="Refund Policy"
      showAcceptance={false}
      requireAuth={false}
    />
  );
}
