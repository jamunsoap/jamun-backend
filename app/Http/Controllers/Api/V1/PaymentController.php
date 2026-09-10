<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a Razorpay Order for online payment.
     */
    public function createRazorpayOrder(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required'],
        ]);

        $orderId = $request->input('order_id');
        $order = Order::where('id', is_numeric($orderId) ? $orderId : 0)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID', 'rzp_test_mockKey123'));
        $keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', 'mockSecret456'));
        $amountInPaise = (int)round($order->total_price * 100);

        if ($amountInPaise < 100) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum order amount must be at least ₹1.',
            ], 400);
        }
        try {
            // If real credentials are provided, call Razorpay API directly
            if ($keyId !== 'rzp_test_mockKey123' && !empty($keySecret)) {
                $response = Http::withBasicAuth($keyId, $keySecret)
                    ->post('https://api.razorpay.com/v1/orders', [
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'receipt' => $order->order_number,
                        'notes' => [
                            'customer_name' => $order->customer_name,
                            'customer_phone' => $order->customer_phone,
                        ],
                    ]);

                if ($response->successful()) {
                    $rzpOrder = $response->json();
                    $rzpOrderId = $rzpOrder['id'];

                    Payment::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'gateway' => 'Razorpay',
                            'amount' => $order->total_price,
                            'currency' => 'INR',
                            'status' => 'pending',
                            'payload' => [
                                'razorpay_order_id' => $rzpOrderId,
                                'initiated_at' => now()->toIso8601String(),
                            ],
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'key' => $keyId,
                        'razorpay_order_id' => $rzpOrderId,
                        'amount' => $amountInPaise,
                        'currency' => 'INR',
                        'order_number' => $order->order_number,
                        'customer' => [
                            'name' => $order->customer_name,
                            'email' => $order->customer_email,
                            'phone' => $order->customer_phone,
                        ],
                    ]);
                } else {
                    $status = $response->status();
                    return response()->json([
                        'success' => false,
                        'message' => $status === 401 ? 'Razorpay Authentication Failed' : 'Failed to create Razorpay order',
                    ], $status === 401 ? 401 : 500);
                }
            }

            // Fallback for local sandbox/test simulation
            $mockRzpOrderId = 'order_mock_' . bin2hex(random_bytes(6));
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'gateway' => 'Razorpay',
                    'amount' => $order->total_price,
                    'currency' => 'INR',
                    'status' => 'pending',
                    'payload' => [
                        'razorpay_order_id' => $mockRzpOrderId,
                        'initiated_at' => now()->toIso8601String(),
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'key' => $keyId,
                'razorpay_order_id' => $mockRzpOrderId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'order_number' => $order->order_number,
                'customer' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Razorpay order creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to initiate online payment. Please try COD or try again.',
            ], 500);
        }
    }

    /**
     * Verify payment signature and update order status.
     */
    public function verifyRazorpayPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['nullable', 'string'],
        ]);

        $orderId = $request->input('order_id');
        $paymentId = $request->input('razorpay_payment_id');
        $rzpOrderId = $request->input('razorpay_order_id');
        $signature = $request->input('razorpay_signature');

        $order = Order::where('id', is_numeric($orderId) ? $orderId : 0)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', 'mockSecret456'));

        // Cryptographic HMAC SHA256 Signature Verification
        $isVerified = true;
        if (!empty($signature) && $keySecret !== 'mockSecret456') {
            $expectedSignature = hash_hmac('sha256', $rzpOrderId . '|' . $paymentId, $keySecret);
            $isVerified = hash_equals($expectedSignature, $signature);
        }

        if (!$isVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed. Potential tampering detected.',
            ], 400);
        }

        $alreadyConfirmed = ($order->order_status === 'confirmed' || $order->is_paid);

        // Mark Order as Paid and Confirmed
        $order->update([
            'is_paid' => true,
            'paid_at' => now(),
            'order_status' => 'confirmed',
            'payment_method' => 'Razorpay',
        ]);

        if (!$alreadyConfirmed) {
            // Send Customer Email
            if ($order->customer_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderPlaced($order));
                } catch (Exception $me) {
                    Log::error("Failed to send order confirmation email for order {$order->id}: " . $me->getMessage());
                }
            }

            // Send Admin Email Alert
            $adminEmail = env('ADMIN_NOTIFICATION_EMAIL');
            if ($adminEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::to($adminEmail)
                        ->send(new \App\Mail\AdminOrderAlert($order));
                } catch (Exception $ae) {
                    Log::error("Failed to send admin order alert email for order {$order->id}: " . $ae->getMessage());
                }
            }
        }

        // Record or Update Payment
        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'gateway' => 'Razorpay',
                'transaction_id' => $paymentId,
                'amount' => $order->total_price,
                'currency' => 'INR',
                'status' => 'successful',
                'payload' => [
                    'razorpay_order_id' => $rzpOrderId,
                    'razorpay_payment_id' => $paymentId,
                    'verified_at' => now()->toIso8601String(),
                ],
            ]
        );

        // Add to tracking timeline
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'confirmed',
            'location' => 'Payment Gateway',
            'comment' => "Payment verified successfully via Razorpay (Txn ID: {$paymentId}).",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment successfully verified!',
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Handle Razorpay Webhooks (background verification).
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');

        if (!empty($webhookSecret)) {
            $signature = $request->header('X-Razorpay-Signature');
            $expected = hash_hmac('sha256', $payload, $webhookSecret);
            if (!hash_equals($expected, (string)$signature)) {
                return response()->json(['error' => 'Invalid webhook signature'], 400);
            }
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;

        if ($event === 'payment.captured') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $rzpOrderId = $paymentEntity['order_id'] ?? null;
            $paymentId = $paymentEntity['id'] ?? null;

            if ($rzpOrderId) {
                $payment = Payment::whereJsonContains('payload->razorpay_order_id', $rzpOrderId)->first();
                if ($payment && $payment->order) {
                    $order = $payment->order;
                    $alreadyConfirmed = ($order->order_status === 'confirmed' || $order->is_paid);

                    $order->update([
                        'is_paid' => true,
                        'paid_at' => now(),
                        'order_status' => 'confirmed',
                    ]);

                    $payment->update([
                        'status' => 'successful',
                        'transaction_id' => $paymentId,
                    ]);

                    if (!$alreadyConfirmed) {
                        // Send Customer Email
                        if ($order->customer_email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($order->customer_email)
                                    ->send(new \App\Mail\OrderPlaced($order));
                            } catch (Exception $me) {
                                Log::error("Failed to send webhook order confirmation email for order {$order->id}: " . $me->getMessage());
                            }
                        }

                        // Send Admin Email Alert
                        $adminEmail = env('ADMIN_NOTIFICATION_EMAIL');
                        if ($adminEmail) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($adminEmail)
                                    ->send(new \App\Mail\AdminOrderAlert($order));
                            } catch (Exception $ae) {
                                Log::error("Failed to send webhook admin order alert email for order {$order->id}: " . $ae->getMessage());
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Mark payment as failed/cancelled.
     */
    public function failRazorpayPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required'],
            'error_message' => ['nullable', 'string'],
        ]);

        $orderId = $request->input('order_id');
        $errorMsg = $request->input('error_message', 'Payment failed or was cancelled by user.');

        $order = Order::where('id', is_numeric($orderId) ? $orderId : 0)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Update latest payment status to 'failed'
        $payment = Payment::where('order_id', $order->id)->latest()->first();
        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'payload' => array_merge($payment->payload ?? [], [
                    'failed_at' => now()->toIso8601String(),
                    'error_message' => $errorMsg,
                ]),
            ]);
        }

        // Add to tracking timeline
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'location' => 'Payment Gateway',
            'comment' => "Payment attempt failed: {$errorMsg}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated to failed.',
        ]);
    }
}
