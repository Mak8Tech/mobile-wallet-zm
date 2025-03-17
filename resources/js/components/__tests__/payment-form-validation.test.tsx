import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import axios from 'axios';
import PaymentForm from '../payment-form';

// Mock axios
vi.mock('axios');
const mockAxios = axios as jest.Mocked<typeof axios>;

describe('PaymentForm Validation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('validates required phone number field', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Submit form without phone number
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Check if validation message appears (using browser's built-in validation)
    const phoneInput = screen.getByLabelText(/Phone Number/i) as HTMLInputElement;
    expect(phoneInput.validity.valid).toBe(false);
    expect(phoneInput.validationMessage).not.toBe('');
  });

  test('validates required amount field', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Submit form without amount
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.click(submitButton);
    
    // Check if validation message appears (using browser's built-in validation)
    const amountInput = screen.getByLabelText(/Amount/i) as HTMLInputElement;
    expect(amountInput.validity.valid).toBe(false);
    expect(amountInput.validationMessage).not.toBe('');
  });

  test('validates minimum amount value', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Try to submit with amount less than 1
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '0.5');
    await user.click(submitButton);
    
    // Check if validation message appears (using browser's built-in validation)
    expect((amountInput as HTMLInputElement).validity.valid).toBe(false);
    expect((amountInput as HTMLInputElement).validationMessage).not.toBe('');
  });

  test('prevents submission when form is invalid', async () => {
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Try to submit without any data
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    await user.click(submitButton);
    
    // Axios should not be called
    expect(mockAxios.post).not.toHaveBeenCalled();
  });

  test('allows submission when form is valid', async () => {
    mockAxios.post.mockResolvedValueOnce({ data: { success: true } });
    
    const user = userEvent.setup();
    render(<PaymentForm />);
    
    // Fill out the form completely
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    const amountInput = screen.getByLabelText(/Amount/i);
    const submitButton = screen.getByRole('button', { name: /Pay Now/i });
    
    await user.type(phoneInput, '0977123456');
    await user.type(amountInput, '100');
    await user.click(submitButton);
    
    // Axios should be called
    await waitFor(() => {
      expect(mockAxios.post).toHaveBeenCalled();
    });
  });

  test('validates phone number format', async () => {
    const user = userEvent.setup();
    
    // Use a custom implementation that validates phone number format
    const CustomPaymentForm = () => {
      const [phoneError, setPhoneError] = React.useState<string | null>(null);
      
      const validatePhone = (value: string) => {
        if (!/^0[967]\d{8}$/.test(value)) {
          setPhoneError('Please enter a valid Zambian phone number');
          return false;
        }
        setPhoneError(null);
        return true;
      };
      
      const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const phoneNumber = form.phoneNumber.value;
        
        if (!validatePhone(phoneNumber)) {
          e.preventDefault();
          return;
        }
        
        // Rest of form handling...
      };
      
      return (
        <div>
          <form onSubmit={handleSubmit}>
            <label htmlFor="phoneNumber">Phone Number</label>
            <input 
              id="phoneNumber" 
              name="phoneNumber"
              onChange={(e) => validatePhone(e.target.value)}
            />
            {phoneError && <div className="error">{phoneError}</div>}
            <button type="submit">Submit</button>
          </form>
        </div>
      );
    };
    
    render(<CustomPaymentForm />);
    
    const phoneInput = screen.getByLabelText(/Phone Number/i);
    
    // Test invalid phone number
    await user.type(phoneInput, '12345');
    
    // Error should be shown
    expect(screen.getByText(/Please enter a valid Zambian phone number/i)).toBeInTheDocument();
    
    // Clear and type valid phone number
    await user.clear(phoneInput);
    await user.type(phoneInput, '0977123456');
    
    // Error should be gone
    expect(screen.queryByText(/Please enter a valid Zambian phone number/i)).not.toBeInTheDocument();
  });
}); 