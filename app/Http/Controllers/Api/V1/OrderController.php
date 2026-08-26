<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Handle guest checkout.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->processOrder($request->validated());

            // Send Real Email confirmation if email exists
            if ($order->customer_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderPlaced($order));
                } catch (Exception $me) {
                    \Illuminate\Support\Facades\Log::error("Failed to send order confirmation email for order {$order->id}: " . $me->getMessage());
                }
            }

            // Send Admin Email Alert
            $adminEmail = env('ADMIN_NOTIFICATION_EMAIL');
            if ($adminEmail) {
                try {
                    \Illuminate\Support\Facades\Mail::to($adminEmail)
                        ->send(new \App\Mail\AdminOrderAlert($order));
                } catch (Exception $ae) {
                    \Illuminate\Support\Facades\Log::error("Failed to send admin order alert email for order {$order->id}: " . $ae->getMessage());
                }
            }

            // Auto push COD orders to Shiprocket immediately
            if ($order->payment_method === 'COD') {
                try {
                    $service = app(\App\Services\ShiprocketService::class);
                    $service->createShipment($order);
                } catch (Exception $se) {
                    \Illuminate\Support\Facades\Log::error("Failed to auto-push COD order {$order->id} to Shiprocket: " . $se->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order' => new OrderResource($order),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get order details for confirmation page.
     */
    public function show(string $id): OrderResource|JsonResponse
    {
        $order = Order::where('order_number', $id)
            ->orWhere('id', is_numeric($id) ? $id : 0)
            ->with(['orderItems.product', 'statusHistories', 'latestPayment'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return new OrderResource($order);
    }

    /**
     * Live Track Order lookup by Order Number OR Customer Phone.
     */
    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:4'],
        ]);

        $searchTerm = trim($request->input('query'));

        $order = Order::where('order_number', $searchTerm)
            ->orWhere('customer_phone', $searchTerm)
            ->latest()
            ->with(['orderItems.product', 'statusHistories', 'latestPayment'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'No active order found with the provided Order ID or Phone Number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => new OrderResource($order),
        ]);
    }
}
