import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi, describe, test, expect, beforeEach } from 'vitest';
import axios from 'axios';
import PaymentForm from '../payment-form';
import '@testing-library/jest-dom';

// Mock axios
vi.mock('axios');
const mockAxios = axios as unknown as {
  post: ReturnType<typeof vi.fn>;
};

describe('PaymentForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('renders payment form with default values', () => {
    render(<PaymentForm />);
    
    // Verify title is rendered
    expect(screen.getByText('Mobile Money Payment')).toBeInTheDocument();
    
    // Verify form elements are present
    expect(screen.getByLabelText(/Phone Number/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Amount/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Payment Provider/i)).toBeInTheDocument();
    
    // Verify submit button is present
    expect(screen.getByRole('button', { name: /Pay Now/i })).toBeInTheDocument();
    
    // Verify default provider selection (MTN)
    expect(screen.getByLabelText(/Payment Provider/i)).toHaveValue('mtn');
  });

  test('renders custom providers when provided', () => {
    const customProviders = [
      { id: "custom1", name: "Custom Provider 1" },
      { id: "custom2", name: "Custom Provider 2" }
    ];
    
    render(<PaymentForm providers={customProviders} />);
    
    // Check if custom providers are in the select box
    expect(screen.getByText('Custom Provider 1')).toBeInTheDocument();
    expect(screen.getByText('Custom Provider 2')).toBeInTheDocument();
    
    // Check that default providers aren't present
    expect(screen.queryByText('MTN Mobile Money')).not.toBeInTheDocument();
  });

  test('disables amount field when amount prop is provided', () => {
    render(<PaymentForm amount={100} />);
    
    const amountInput = screen.getByLabelText(/Amount/i) as HTMLInputElement;
    expect(amountInput.value).toBe('100');
    expect(amountInput).toBeDisabled();
  });

  test('allows input for phone number and amount', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '150');
    
    expect(phoneInput).toHaveValue('0977123456');
    expect(amountInput).toHaveValue(150);
  });

  test('shows loading state during form submission', async () => {
    // Mock axios to return a promise that never resolves
    mockAxios.post.mockImplementation(() => new Promise(() => {}));
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Button should show loading state
    expect(screen.getByRole('button', { name: /Processing/i })).toBeInTheDocument();
    expect(submitButton).toBeDisabled();
  });

  test('submits payment successfully', async () => {
    const successResponse = {
      data: {
        success: true,
        transaction_id: 'test-id',
        status: 'pending'
      }
    };
    
    mockAxios.post.mockResolvedValueOnce(successResponse);
    
    const onSuccessMock = vi.fn();
    const user = userEvent.setup();
    
    render(<PaymentForm onSuccess={onSuccessMock} />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Wait for success message
    await waitFor(() => {
      expect(screen.getByText(/Payment initiated successfully/i)).toBeInTheDocument();
    });
    
    // Check if onSuccess callback was called with correct data
    expect(onSuccessMock).toHaveBeenCalledWith(successResponse.data);
    
    // Check if axios was called with correct data
    expect(mockAxios.post).toHaveBeenCalledWith('/api/mobile-wallet/payment', {
      phone_number: '0977123456',
      amount: 100,
      provider: 'mtn'
    });
  });

  test('handles payment submission errors', async () => {
    const errorResponse = {
      response: {
        data: {
          message: 'Payment failed: Insufficient funds'
        }
      }
    };
    
    mockAxios.post.mockRejectedValueOnce(errorResponse);
    
    const onErrorMock = vi.fn();
    const user = userEvent.setup();
    
    render(<PaymentForm onError={onErrorMock} />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Wait for error message
    await waitFor(() => {
      expect(screen.getByText(/Payment failed: Insufficient funds/i)).toBeInTheDocument();
    });
    
    // Check if onError callback was called with correct data
    expect(onErrorMock).toHaveBeenCalledWith(errorResponse);
  });

  test('handles generic error when no specific error message is returned', async () => {
    mockAxios.post.mockRejectedValueOnce({});
    
    const user = userEvent.setup();
    
    render(<PaymentForm />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Wait for generic error message
    await waitFor(() => {
      expect(screen.getByText(/An error occurred while processing your payment/i)).toBeInTheDocument();
    });
  });

  test('changes provider selection', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    const providerSelect = screen.getByLabelText(/Payment Provider/i);
    
    // Select Airtel Money
    await user.selectOptions(providerSelect, 'airtel');
    expect(providerSelect).toHaveValue('airtel');
    
    // Select Zamtel Kwacha
    await user.selectOptions(providerSelect, 'zamtel');
    expect(providerSelect).toHaveValue('zamtel');
  });
}); 