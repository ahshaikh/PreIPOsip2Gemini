/**
 * V-DISPUTE-MGMT-2026: DisputeTable Component Tests
 */

import { render, screen, fireEvent } from '@testing-library/react';
import DisputeTable from '@/components/admin/disputes/DisputeTable';
import { DisputeWithPermissions } from '@/lib/api/disputes';

const mockDisputes: DisputeWithPermissions[] = [
  {
    dispute: {
      id: 1,
      title: 'Payment not received',
      description: 'Test description',
      type: 'payment',
      status: 'open',
      severity: 'medium',
      category: 'fund_transfer',
      user_id: 1,
      risk_score: 2,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
      user: { id: 1, name: 'John Doe', email: 'john@example.com' },
    },
    permissions: {
      can_transition: true,
      can_escalate: true,
      can_resolve: false,
      can_override_defensibility: true,
      can_refund: false,
      can_close: false,
      available_transitions: ['under_review', 'escalated'],
    },
  },
  {
    dispute: {
      id: 2,
      title: 'Allocation mismatch',
      description: 'Test description',
      type: 'allocation',
      status: 'escalated',
      severity: 'high',
      category: 'investment_processing',
      user_id: 2,
      risk_score: 4,
      created_at: '2024-01-02T00:00:00Z',
      updated_at: '2024-01-02T00:00:00Z',
      user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
    },
    permissions: {
      can_transition: true,
      can_escalate: false,
      can_resolve: true,
      can_override_defensibility: true,
      can_refund: false,
      can_close: false,
      available_transitions: ['resolved_approved', 'resolved_rejected'],
    },
  },
];

describe('DisputeTable', () => {
  const mockOnClick = jest.fn();

  beforeEach(() => {
    mockOnClick.mockClear();
  });

  it('renders loading state', () => {
    render(
      <DisputeTable disputes={[]} loading={true} onDisputeClick={mockOnClick} />
    );
    expect(screen.getByText(/loading disputes/i)).toBeInTheDocument();
  });

  it('renders empty state when no disputes', () => {
    render(
      <DisputeTable disputes={[]} loading={false} onDisputeClick={mockOnClick} />
    );
    expect(screen.getByText(/no disputes found/i)).toBeInTheDocument();
  });

  it('renders disputes correctly', () => {
    render(
      <DisputeTable disputes={mockDisputes} loading={false} onDisputeClick={mockOnClick} />
    );

    expect(screen.getByText('Payment not received')).toBeInTheDocument();
    expect(screen.getByText('Allocation mismatch')).toBeInTheDocument();
    expect(screen.getByText('John Doe')).toBeInTheDocument();
    expect(screen.getByText('Jane Doe')).toBeInTheDocument();
  });

  it('displays correct status badges', () => {
    render(
      <DisputeTable disputes={mockDisputes} loading={false} onDisputeClick={mockOnClick} />
    );

    expect(screen.getByText('open')).toBeInTheDocument();
    expect(screen.getByText('escalated')).toBeInTheDocument();
  });

  it('displays correct type badges', () => {
    render(
      <DisputeTable disputes={mockDisputes} loading={false} onDisputeClick={mockOnClick} />
    );

    expect(screen.getByText('payment')).toBeInTheDocument();
    expect(screen.getByText('allocation')).toBeInTheDocument();
  });

  it('calls onDisputeClick when row is clicked', () => {
    render(
      <DisputeTable disputes={mockDisputes} loading={false} onDisputeClick={mockOnClick} />
    );

    fireEvent.click(screen.getByText('Payment not received'));
    expect(mockOnClick).toHaveBeenCalledWith(1);
  });

  it('shows permission indicators', () => {
    render(
      <DisputeTable disputes={mockDisputes} loading={false} onDisputeClick={mockOnClick} />
    );

    // Check that permission dots are rendered (by title attribute)
    const transitionDots = document.querySelectorAll('[title="Can transition"]');
    expect(transitionDots.length).toBe(2);
  });
});
