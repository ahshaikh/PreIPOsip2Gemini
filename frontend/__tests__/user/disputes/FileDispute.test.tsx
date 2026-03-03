/**
 * V-DISPUTE-MGMT-2026: File Dispute Tests
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DisputeForm from '@/components/user/disputes/DisputeForm';
import { fileDispute } from '@/lib/api/disputes';

// Mock the API
jest.mock('@/lib/api/disputes', () => ({
  fileDispute: jest.fn(),
}));

describe('DisputeForm', () => {
  const mockOnSuccess = jest.fn();
  const mockOnCancel = jest.fn();

  beforeEach(() => {
    mockOnSuccess.mockClear();
    mockOnCancel.mockClear();
    (fileDispute as jest.Mock).mockClear();
  });

  it('renders form fields correctly', () => {
    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    expect(screen.getByLabelText(/category/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
    expect(screen.getByText(/submit dispute/i)).toBeInTheDocument();
    expect(screen.getByText(/cancel/i)).toBeInTheDocument();
  });

  it('shows error when submitting without required fields', async () => {
    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    fireEvent.click(screen.getByText(/submit dispute/i));

    await waitFor(() => {
      expect(screen.getByText(/please fill in all required fields/i)).toBeInTheDocument();
    });
  });

  it('calls fileDispute API on valid submission', async () => {
    (fileDispute as jest.Mock).mockResolvedValue({
      success: true,
      data: { id: 123 },
    });

    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    // Fill in form
    await userEvent.selectOptions(screen.getByLabelText(/category/i), 'fund_transfer');
    await userEvent.type(screen.getByLabelText(/title/i), 'Test Dispute Title');
    await userEvent.type(screen.getByLabelText(/description/i), 'This is a test description for the dispute.');

    fireEvent.click(screen.getByText(/submit dispute/i));

    await waitFor(() => {
      expect(fileDispute).toHaveBeenCalledWith({
        title: 'Test Dispute Title',
        description: 'This is a test description for the dispute.',
        category: 'fund_transfer',
      });
    });

    await waitFor(() => {
      expect(mockOnSuccess).toHaveBeenCalledWith(123);
    });
  });

  it('shows error message on API failure', async () => {
    (fileDispute as jest.Mock).mockResolvedValue({
      success: false,
      message: 'Server error occurred',
    });

    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    // Fill in form
    await userEvent.selectOptions(screen.getByLabelText(/category/i), 'fund_transfer');
    await userEvent.type(screen.getByLabelText(/title/i), 'Test');
    await userEvent.type(screen.getByLabelText(/description/i), 'Test description');

    fireEvent.click(screen.getByText(/submit dispute/i));

    await waitFor(() => {
      expect(screen.getByText(/server error occurred/i)).toBeInTheDocument();
    });

    expect(mockOnSuccess).not.toHaveBeenCalled();
  });

  it('calls onCancel when cancel button is clicked', () => {
    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    fireEvent.click(screen.getByText(/cancel/i));

    expect(mockOnCancel).toHaveBeenCalled();
  });

  it('shows loading state during submission', async () => {
    (fileDispute as jest.Mock).mockImplementation(
      () => new Promise((resolve) => setTimeout(() => resolve({ success: true, data: { id: 1 } }), 100))
    );

    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    // Fill in form
    await userEvent.selectOptions(screen.getByLabelText(/category/i), 'fund_transfer');
    await userEvent.type(screen.getByLabelText(/title/i), 'Test');
    await userEvent.type(screen.getByLabelText(/description/i), 'Test description');

    fireEvent.click(screen.getByText(/submit dispute/i));

    expect(screen.getByText(/submitting/i)).toBeInTheDocument();
  });

  it('renders all category options', () => {
    render(<DisputeForm onSuccess={mockOnSuccess} onCancel={mockOnCancel} />);

    const select = screen.getByLabelText(/category/i);
    expect(select).toBeInTheDocument();

    // Check that options exist
    expect(screen.getByText('Fund Transfer Issues')).toBeInTheDocument();
    expect(screen.getByText('Investment Processing')).toBeInTheDocument();
    expect(screen.getByText('Financial Disclosure')).toBeInTheDocument();
    expect(screen.getByText('Platform Service')).toBeInTheDocument();
    expect(screen.getByText('Other')).toBeInTheDocument();
  });
});
