import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
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

describe('PaymentForm API Interactions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('sends correct data to API endpoint', async () => {
    // Setup mock response
    mockAxios.post.mockResolvedValueOnce({ data: { success: true } });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const providerSelect = screen.getByLabelText(/Payment Provider/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '250.50');
    await user.selectOptions(providerSelect, 'airtel');
    await user.click(submitButton);
    
    // Verify that axios was called with the correct data
    await waitFor(() => {
      expect(mockAxios.post).toHaveBeenCalledWith('/api/mobile-wallet/payment', {
        phone_number: '0977123456',
        amount: 250.5, // Note: parsed as float
        provider: 'airtel',
      });
    });
  });

  test('handles API request timeout', async () => {
    // Simulate a timeout error
    mockAxios.post.mockRejectedValueOnce({
      message: 'timeout of 30000ms exceeded',
      code: 'ECONNABORTED',
    });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form and submit
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Verify error message
    await waitFor(() => {
      expect(screen.getByText(/An error occurred while processing your payment/i)).toBeInTheDocument();
    });
  });

  test('handles API network error', async () => {
    // Simulate a network error
    mockAxios.post.mockRejectedValueOnce({
      message: 'Network Error',
      isAxiosError: true,
    });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form and submit
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Verify error message
    await waitFor(() => {
      expect(screen.getByText(/An error occurred while processing your payment/i)).toBeInTheDocument();
    });
  });

  test('handles API server error', async () => {
    // Simulate a server error
    mockAxios.post.mockRejectedValueOnce({
      response: {
        status: 500,
        data: {
          message: 'Internal Server Error',
        },
      },
    });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form and submit
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Verify error message
    await waitFor(() => {
      expect(screen.getByText(/Internal Server Error/i)).toBeInTheDocument();
    });
  });

  test('handles API validation error', async () => {
    // Simulate a validation error from the server
    mockAxios.post.mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            phone_number: ['The phone number format is invalid.'],
            amount: ['The amount must be at least 1.'],
          },
        },
      },
    });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form and submit
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Verify error message
    await waitFor(() => {
      expect(screen.getByText(/The given data was invalid/i)).toBeInTheDocument();
    });
  });

  test('handles API success response with callbacks', async () => {
    // Prepare mock response
    const successResponse = {
      data: {
        success: true,
        transaction_id: 'tx-123456',
        provider_transaction_id: 'prov-987654',
        status: 'pending',
      },
    };
    
    mockAxios.post.mockResolvedValueOnce(successResponse);
    
    // Create mock callback functions
    const onSuccessMock = vi.fn();
    const onErrorMock = vi.fn();
    
    const user = userEvent.setup();
    render(
      <PaymentForm 
        onSuccess={onSuccessMock} 
        onError={onErrorMock} 
      />
    );
    
    // Fill out the form and submit
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Verify success message and callback
    await waitFor(() => {
      expect(screen.getByText(/Payment initiated successfully/i)).toBeInTheDocument();
      expect(onSuccessMock).toHaveBeenCalledWith(successResponse.data);
      expect(onErrorMock).not.toHaveBeenCalled();
    });
  });
}); 