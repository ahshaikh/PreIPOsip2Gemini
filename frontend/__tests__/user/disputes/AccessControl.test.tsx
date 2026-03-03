/**
 * V-DISPUTE-MGMT-2026: Access Control Tests
 *
 * Tests that investors can only access their own disputes.
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
  getUserDisputeDetail: jest.fn(),
  getUserDisputes: jest.fn(),
  addComment: jest.fn(),
}));

import UserDisputeDetailPage from '@/app/(user)/disputes/[id]/page';
import UserDisputesPage from '@/app/(user)/disputes/page';
import { getUserDisputeDetail, getUserDisputes } from '@/lib/api/disputes';

describe('User Dispute Access Control', () => {
  const mockRouter = {
    push: jest.fn(),
    back: jest.fn(),
  };

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    mockRouter.push.mockClear();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('UserDisputesPage', () => {
    it('only shows disputes belonging to the current user', async () => {
      (getUserDisputes as jest.Mock).mockResolvedValue({
        success: true,
        data: [
          {
            id: 1,
            title: 'My Dispute',
            status: 'open',
            category: 'fund_transfer',
            created_at: '2024-01-01T00:00:00Z',
          },
        ],
      });

      render(<UserDisputesPage />);

      await waitFor(() => {
        expect(screen.getByText('My Dispute')).toBeInTheDocument();
      });

      // API should be called (backend filters by authenticated user)
      expect(getUserDisputes).toHaveBeenCalled();
    });

    it('shows empty state when user has no disputes', async () => {
      (getUserDisputes as jest.Mock).mockResolvedValue({
        success: true,
        data: [],
      });

      render(<UserDisputesPage />);

      await waitFor(() => {
        expect(screen.getByText(/you have not filed any disputes/i)).toBeInTheDocument();
      });
    });
  });

  describe('UserDisputeDetailPage', () => {
    beforeEach(() => {
      (useParams as jest.Mock).mockReturnValue({ id: '1' });
    });

    it('shows dispute details when user owns the dispute', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: true,
        data: {
          id: 1,
          title: 'My Dispute',
          description: 'Test description',
          status: 'open',
          category: 'fund_transfer',
          created_at: '2024-01-01T00:00:00Z',
          timeline: [],
        },
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText('My Dispute')).toBeInTheDocument();
      });
    });

    it('shows error when user does not own the dispute (403)', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: false,
        message: 'You do not have permission to view this dispute',
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText(/you do not have permission/i)).toBeInTheDocument();
      });
    });

    it('shows error when dispute not found (404)', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: false,
        message: 'Dispute not found',
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText(/dispute not found/i)).toBeInTheDocument();
      });
    });

    it('provides link back to disputes list on error', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: false,
        message: 'Dispute not found',
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText(/back to my disputes/i)).toBeInTheDocument();
      });
    });
  });

  describe('Timeline Visibility', () => {
    beforeEach(() => {
      (useParams as jest.Mock).mockReturnValue({ id: '1' });
    });

    it('only shows investor-visible timeline entries', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: true,
        data: {
          id: 1,
          title: 'My Dispute',
          description: 'Test',
          status: 'under_review',
          category: 'fund_transfer',
          created_at: '2024-01-01T00:00:00Z',
          timeline: [
            {
              id: 1,
              title: 'Visible Update',
              event_type: 'status_change',
              actor_role: 'admin',
              visible_to_investor: true,
              created_at: '2024-01-02T00:00:00Z',
            },
            {
              id: 2,
              title: 'Internal Note',
              event_type: 'comment',
              actor_role: 'admin',
              visible_to_investor: false,
              created_at: '2024-01-02T00:00:00Z',
            },
          ],
        },
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText('Visible Update')).toBeInTheDocument();
      });

      // Internal note should not be visible
      expect(screen.queryByText('Internal Note')).not.toBeInTheDocument();
    });
  });

  describe('Evidence Submission Access', () => {
    beforeEach(() => {
      (useParams as jest.Mock).mockReturnValue({ id: '1' });
    });

    it('shows evidence upload when status is awaiting_investor', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: true,
        data: {
          id: 1,
          title: 'My Dispute',
          description: 'Test',
          status: 'awaiting_investor',
          category: 'fund_transfer',
          created_at: '2024-01-01T00:00:00Z',
          timeline: [],
        },
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText(/add evidence/i)).toBeInTheDocument();
      });
    });

    it('hides evidence upload when status is not awaiting_investor', async () => {
      (getUserDisputeDetail as jest.Mock).mockResolvedValue({
        success: true,
        data: {
          id: 1,
          title: 'My Dispute',
          description: 'Test',
          status: 'under_review',
          category: 'fund_transfer',
          created_at: '2024-01-01T00:00:00Z',
          timeline: [],
        },
      });

      render(<UserDisputeDetailPage />);

      await waitFor(() => {
        expect(screen.getByText('My Dispute')).toBeInTheDocument();
      });

      expect(screen.queryByText(/add evidence/i)).not.toBeInTheDocument();
    });
  });
});
