/**
 * V-DISPUTE-MGMT-2026: DisputeDetail Page Tests
 */

import { render, screen, waitFor } from '@testing-library/react';
import { useParams, useRouter } from 'next/navigation';

// Mock the next/navigation
jest.mock('next/navigation', () => ({
  useParams: jest.fn(),
  useRouter: jest.fn(),
}));

// Mock the API
jest.mock('@/lib/api/disputes', () => ({
  getAdminDisputeDetail: jest.fn(),
  transitionDispute: jest.fn(),
  escalateDispute: jest.fn(),
  closeDispute: jest.fn(),
}));

import AdminDisputeDetailPage from '@/app/admin/disputes/[id]/page';
import { getAdminDisputeDetail } from '@/lib/api/disputes';

const mockDisputeDetail = {
  dispute: {
    id: 1,
    title: 'Test Dispute',
    description: 'Test description for the dispute',
    type: 'payment',
    status: 'under_review',
    severity: 'medium',
    category: 'fund_transfer',
    user_id: 1,
    risk_score: 2,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    user: { id: 1, name: 'John Doe', email: 'john@example.com' },
    timeline: [],
  },
  permissions: {
    can_transition: true,
    can_escalate: true,
    can_resolve: true,
    can_override_defensibility: true,
    can_refund: false,
    can_close: false,
    available_transitions: ['awaiting_investor', 'escalated', 'resolved_approved', 'resolved_rejected'],
  },
  integrity: {
    valid: true,
    stored_hash: 'abc123',
    computed_hash: 'abc123',
    error: null,
  },
  recommended_settlement: {
    action: 'refund',
    reason: 'Payment issues typically require refund',
  },
  available_transitions: ['awaiting_investor', 'escalated'],
};

describe('AdminDisputeDetailPage', () => {
  const mockRouter = {
    push: jest.fn(),
    back: jest.fn(),
  };

  beforeEach(() => {
    (useParams as jest.Mock).mockReturnValue({ id: '1' });
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    (getAdminDisputeDetail as jest.Mock).mockResolvedValue({
      success: true,
      data: mockDisputeDetail,
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('renders loading state initially', () => {
    render(<AdminDisputeDetailPage />);
    // Check for loading skeleton
    expect(document.querySelector('.animate-pulse')).toBeInTheDocument();
  });

  it('renders dispute details after loading', async () => {
    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Test Dispute')).toBeInTheDocument();
    });

    expect(screen.getByText('Test description for the dispute')).toBeInTheDocument();
  });

  it('shows correct status badge', async () => {
    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('UNDER REVIEW')).toBeInTheDocument();
    });
  });

  it('shows user information', async () => {
    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('John Doe')).toBeInTheDocument();
      expect(screen.getByText('john@example.com')).toBeInTheDocument();
    });
  });

  it('shows integrity panel as verified', async () => {
    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Verified')).toBeInTheDocument();
    });
  });

  it('renders error state when API fails', async () => {
    (getAdminDisputeDetail as jest.Mock).mockResolvedValue({
      success: false,
      message: 'Dispute not found',
    });

    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText('Dispute not found')).toBeInTheDocument();
    });
  });

  it('shows back button that navigates to list', async () => {
    render(<AdminDisputeDetailPage />);

    await waitFor(() => {
      expect(screen.getByText(/back to disputes/i)).toBeInTheDocument();
    });
  });
});
