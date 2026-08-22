<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log the incoming payload for debugging
        Log::info('Shiprocket Webhook Received', $request->all());

        // Extract key identifiers and status
        $awb = $request->input('awb');
        $shiprocketOrderId = $request->input('order_id');
        $channelOrderId = $request->input('channel_order_id'); // This is our order_number
        $currentStatus = strtoupper($request->input('current_status', ''));

        // If no identifying info, return 200 OK (this allows Shiprocket's connection test to pass)
        if (!$awb && !$shiprocketOrderId && !$channelOrderId) {
            return response()->json(['success' => true, 'message' => 'Webhook active'], 200);
        }

        // Find the corresponding order in our DB
        $order = null;

        if ($channelOrderId) {
            $order = Order::where('order_number', $channelOrderId)->first();
        }

        if (!$order && $shiprocketOrderId) {
            $order = Order::where('shiprocket_order_id', (string)$shiprocketOrderId)->first();
        }

        if (!$order && $awb) {
            $order = Order::where('awb_code', $awb)->orWhere('tracking_number', $awb)->first();
        }

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Map Shiprocket Status to Local Status
        $localStatus = $this->mapStatus($currentStatus);

        if (!$localStatus) {
            // Unrecognized status, we just return 200 OK so Shiprocket doesn't retry
            return response()->json(['success' => true, 'message' => 'Status ignored']);
        }

        // Check if we need to update
        if ($order->order_status !== $localStatus) {
            $updateData = ['order_status' => $localStatus];

            if ($localStatus === 'delivered') {
                $updateData['is_delivered'] = true;
                $updateData['delivered_at'] = now();
                $updateData['is_paid'] = true;
            }

            $order->update($updateData);

            // Log history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $localStatus,
                'location' => $request->input('scans.0.location', 'Shiprocket Event'),
                'comment' => "Status auto-synced from Shiprocket: " . ucfirst(strtolower($currentStatus)),
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function mapStatus(string $shiprocketStatus): ?string
    {
        return match ($shiprocketStatus) {
            'CANCELED', 'CANCELLED' => 'cancelled',
            'SHIPPED' => 'shipped',
            'OUT FOR DELIVERY' => 'out_for_delivery',
            'DELIVERED' => 'delivered',
            'RTO ACKNOWLEDGED', 'RTO DELIVERED' => 'cancelled', // Or a separate 'returned' status
            default => null,
        };
    }
}
