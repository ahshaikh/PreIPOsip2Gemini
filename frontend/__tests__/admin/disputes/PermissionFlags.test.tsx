/**
 * V-DISPUTE-MGMT-2026: Permission Flags Tests
 *
 * CRITICAL: These tests verify that frontend correctly uses backend permission flags
 * and does NOT derive permissions from status.
 */

import { render, screen } from '@testing-library/react';
import ActionButtons from '@/components/admin/disputes/ActionButtons';
import { DisputePermissions, Dispute } from '@/lib/api/disputes';

const createMockDispute = (status: string): Dispute => ({
  id: 1,
  title: 'Test Dispute',
  description: 'Test',
  type: 'payment',
  status,
  severity: 'medium',
  category: 'fund_transfer',
  user_id: 1,
  risk_score: 2,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
});

describe('PermissionFlags', () => {
  const mockHandlers = {
    onTransition: jest.fn(),
    onEscalate: jest.fn(),
    onClose: jest.fn(),
    onResolve: jest.fn(),
    onOverride: jest.fn(),
  };

  beforeEach(() => {
    Object.values(mockHandlers).forEach((fn) => fn.mockClear());
  });

  it('shows Transition button when can_transition is true', () => {
    const permissions: DisputePermissions = {
      can_transition: true,
      can_escalate: false,
      can_resolve: false,
      can_override_defensibility: false,
      can_refund: false,
      can_close: false,
      available_transitions: ['under_review'],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('open')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.getByText('Transition')).toBeInTheDocument();
  });

  it('hides Transition button when can_transition is false', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: false,
      can_resolve: false,
      can_override_defensibility: false,
      can_refund: false,
      can_close: false,
      available_transitions: [],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('closed')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.queryByText('Transition')).not.toBeInTheDocument();
  });

  it('shows Escalate button when can_escalate is true', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: true,
      can_resolve: false,
      can_override_defensibility: false,
      can_refund: false,
      can_close: false,
      available_transitions: [],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('open')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.getByText('Escalate')).toBeInTheDocument();
  });

  it('shows Resolve button when can_resolve is true', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: false,
      can_resolve: true,
      can_override_defensibility: false,
      can_refund: false,
      can_close: false,
      available_transitions: [],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('under_review')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.getByText('Resolve')).toBeInTheDocument();
  });

  it('shows Close button when can_close is true', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: false,
      can_resolve: false,
      can_override_defensibility: false,
      can_refund: false,
      can_close: true,
      available_transitions: [],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('resolved_approved')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.getByText('Close')).toBeInTheDocument();
  });

  it('shows Override button when can_override_defensibility is true', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: false,
      can_resolve: false,
      can_override_defensibility: true,
      can_refund: false,
      can_close: false,
      available_transitions: [],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('under_review')}
        loading={false}
        {...mockHandlers}
      />
    );

    expect(screen.getByText('Override')).toBeInTheDocument();
  });

  it('uses backend permissions, not status-derived permissions', () => {
    // Backend says closed dispute CAN transition (hypothetical edge case)
    const permissions: DisputePermissions = {
      can_transition: true,
      can_escalate: true,
      can_resolve: true,
      can_override_defensibility: true,
      can_refund: false,
      can_close: false,
      available_transitions: ['under_review'],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('closed')}
        loading={false}
        {...mockHandlers}
      />
    );

    // Should show buttons based on permissions, NOT based on status
    expect(screen.getByText('Transition')).toBeInTheDocument();
    expect(screen.getByText('Escalate')).toBeInTheDocument();
    expect(screen.getByText('Resolve')).toBeInTheDocument();
    expect(screen.getByText('Override')).toBeInTheDocument();
  });

  it('disables buttons when loading is true', () => {
    const permissions: DisputePermissions = {
      can_transition: true,
      can_escalate: true,
      can_resolve: true,
      can_override_defensibility: true,
      can_refund: false,
      can_close: false,
      available_transitions: ['under_review'],
    };

    render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('open')}
        loading={true}
        {...mockHandlers}
      />
    );

    const transitionButton = screen.getByText('Transition');
    expect(transitionButton).toBeDisabled();
  });

  it('shows no buttons when all permissions are false', () => {
    const permissions: DisputePermissions = {
      can_transition: false,
      can_escalate: false,
      can_resolve: false,
      can_override_defensibility: false,
      can_refund: false,
      can_close: false,
      available_transitions: [],
    };

    const { container } = render(
      <ActionButtons
        permissions={permissions}
        dispute={createMockDispute('closed')}
        loading={false}
        {...mockHandlers}
      />
    );

    // Container should have no buttons
    expect(container.querySelectorAll('button').length).toBe(0);
  });
});
