'use client';

/**
 * V-DISPUTE-MGMT-2026: File New Dispute Page
 *
 * Form for investors to file a new dispute.
 */

import { useRouter } from 'next/navigation';
import DisputeForm from '@/components/user/disputes/DisputeForm';

export default function NewDisputePage() {
  const router = useRouter();

  const handleSuccess = (disputeId: number) => {
    router.push(`/disputes/${disputeId}`);
  };

  const handleCancel = () => {
    router.push('/disputes');
  };

  return (
    <div className="container mx-auto px-4 py-6">
      <div className="mb-6">
        <button
          onClick={handleCancel}
          className="text-sm text-gray-500 hover:text-gray-700 mb-2"
        >
          &larr; Back to my disputes
        </button>
        <h1 className="text-2xl font-bold text-gray-900">File a Dispute</h1>
        <p className="text-gray-600 mt-1">
          Submit a dispute for review by our team.
        </p>
      </div>

      <div className="max-w-2xl">
        <DisputeForm onSuccess={handleSuccess} onCancel={handleCancel} />
      </div>
    </div>
  );
}
