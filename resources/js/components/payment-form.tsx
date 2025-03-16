import React, { useState } from "react";
import axios from "axios";

interface PaymentFormProps {
    amount?: number;
    onSuccess?: (data: any) => void;
    onError?: (error: any) => void;
    providers?: Array<{
        id: string;
        name: string;
    }>;
}

const PaymentForm: React.FC<PaymentFormProps> = ({
    amount,
    onSuccess,
    onError,
    providers = [
        { id: "mtn", name: "MTN Mobile Money" },
        { id: "airtel", name: "Airtel Money" },
        { id: "zamtel", name: "Zamtel Kwacha" },
    ],
}) => {
    const [phoneNumber, setPhoneNumber] = useState("");
    const [paymentAmount, setPaymentAmount] = useState(amount?.toString() || "");
    const [provider, setProvider] = useState(providers[0]?.id || "mtn");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await axios.post("/api/mobile-wallet/payment", {
                phone_number: phoneNumber,
                amount: parseFloat(paymentAmount),
                provider: provider,
            });

            setSuccess("Payment initiated successfully!");
            setLoading(false);

            if (onSuccess) {
                onSuccess(response.data);
            }
        } catch (err: any) {
            setError(
                err.response?.data?.message ||
                    "An error occurred while processing your payment."
            );
            setLoading(false);

            if (onError) {
                onError(err);
            }
        }
    };

    return (
        <div className="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-2xl font-bold mb-6 text-center">
                Mobile Money Payment
            </h2>

            {error && (
                <div className="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {error}
                </div>
            )}

            {success && (
                <div className="bg-green-100 text-green-700 p-3 rounded mb-4">
                    {success}
                </div>
            )}

            <form onSubmit={handleSubmit}>
                <div className="mb-4">
                    <label
                        htmlFor="provider"
                        className="block text-gray-700 text-sm font-bold mb-2"
                    >
                        Payment Provider
                    </label>
                    <select
                        id="provider"
                        value={provider}
                        onChange={(e) => setProvider(e.target.value)}
                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required
                    >
                        {providers.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="mb-4">
                    <label
                        htmlFor="phoneNumber"
                        className="block text-gray-700 text-sm font-bold mb-2"
                    >
                        Phone Number
                    </label>
                    <input
                        id="phoneNumber"
                        type="text"
                        value={phoneNumber}
                        onChange={(e) => setPhoneNumber(e.target.value)}
                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="e.g. 0977123456"
                        required
                    />
                </div>

                <div className="mb-6">
                    <label
                        htmlFor="amount"
                        className="block text-gray-700 text-sm font-bold mb-2"
                    >
                        Amount (ZMW)
                    </label>
                    <input
                        id="amount"
                        type="number"
                        value={paymentAmount}
                        onChange={(e) => setPaymentAmount(e.target.value)}
                        className="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        placeholder="Enter amount"
                        required
                        min="1"
                        step="0.01"
                        disabled={amount !== undefined}
                    />
                </div>

                <div className="flex items-center justify-center">
                    <button
                        type="submit"
                        disabled={loading}
                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full"
                    >
                        {loading ? "Processing..." : "Pay Now"}
                    </button>
                </div>
            </form>
        </div>
    );
};

export default PaymentForm;