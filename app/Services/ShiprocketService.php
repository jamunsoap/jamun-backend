<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketService
{
    protected string $baseUrl;
    protected ?string $email;
    protected ?string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.shiprocket.api_url', 'https://apiv2.shiprocket.in/v1/external');
        $this->email = config('services.shiprocket.email');
        $this->password = config('services.shiprocket.password');
    }

    /**
     * Check if live Shiprocket API credentials are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Retrieve Shiprocket authentication token (cached for 24 hours).
     */
    public function getToken(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        return Cache::remember('shiprocket_auth_token', 86000, function () {
            try {
                $response = Http::post("{$this->baseUrl}/auth/login", [
                    'email' => $this->email,
                    'password' => $this->password,
                ]);

                if ($response->successful()) {
                    return $response->json('token');
                }

                Log::error('Shiprocket login failed: ' . $response->body());
                return null;
            } catch (Exception $e) {
                Log::error('Shiprocket token exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Push an Order to Shiprocket and generate shipment.
     */
    public function createShipment(Order $order): array
    {
        $order->loadMissing('orderItems.product');

        // Sandbox / Mock simulation if no live credentials
        if (!$this->isConfigured() || !$this->getToken()) {
            $mockShipmentId = 'SR-SHIP-' . rand(100000, 999999);
            $mockOrderId = 'SR-ORD-' . rand(100000, 999999);
            $mockAwb = '141' . rand(100000000, 999999999);
            $mockCourier = 'BlueDart Express';

            $order->update([
                'courier_name' => $mockCourier,
                'tracking_number' => $mockAwb,
                'awb_code' => $mockAwb,
                'shiprocket_order_id' => $mockOrderId,
                'shiprocket_shipment_id' => $mockShipmentId,
                'order_status' => 'shipped',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'shipped',
                'location' => 'Fulfillment Center (Gandhinagar, GJ)',
                'comment' => "Order pushed to {$mockCourier} via Shiprocket (AWB: {$mockAwb}).",
            ]);

            return [
                'success' => true,
                'is_mock' => true,
                'order_id' => $mockOrderId,
                'shipment_id' => $mockShipmentId,
                'awb_code' => $mockAwb,
                'courier_name' => $mockCourier,
                'message' => 'Shipment successfully created in sandbox mode.',
            ];
        }

        // Live API integration
        try {
            $token = $this->getToken();
            $orderItems = [];
            $totalWeight = 0;

            foreach ($order->orderItems as $item) {
                $orderItems[] = [
                    'name' => $item->product?->name ?? 'Jadui Jamun Soap',
                    'sku' => $item->product?->slug ?? "jamun-soap-{$item->product_id}",
                    'units' => $item->quantity,
                    'selling_price' => (float)$item->price,
                    'discount' => 0,
                    'tax' => 0,
                ];
                $totalWeight += 0.125 * $item->quantity; // Approx 125g per soap
            }

            $payload = [
                'order_id' => $order->order_number,
                'order_date' => $order->created_at->format('Y-m-d H:i'),
                'pickup_location' => 'Primary Warehouse',
                'channel_id' => '',
                'comment' => 'Jadui Jamun Botanical Skincare Order',
                'billing_customer_name' => $order->customer_name,
                'billing_last_name' => '',
                'billing_address' => $order->street,
                'billing_city' => $order->city,
                'billing_pincode' => $order->pincode,
                'billing_state' => $order->state,
                'billing_country' => $order->country ?? 'India',
                'billing_email' => $order->customer_email ?? 'customer@jamunsoap.com',
                'billing_phone' => $order->customer_phone,
                'shipping_is_billing' => true,
                'order_items' => $orderItems,
                'payment_method' => $order->payment_method === 'COD' ? 'COD' : 'Prepaid',
                'sub_total' => (float)$order->total_price,
                'length' => 10,
                'breadth' => 10,
                'height' => 5,
                'weight' => max(0.1, round($totalWeight, 3)),
            ];

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/orders/create/adhoc", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $shiprocketOrderId = $data['order_id'] ?? null;
                $shipmentId = $data['shipment_id'] ?? null;
                $awbCode = $data['awb_code'] ?? null;
                $courierName = $data['courier_name'] ?? 'Shiprocket Assigned';

                $order->update([
                    'courier_name' => $courierName,
                    'tracking_number' => $awbCode,
                    'awb_code' => $awbCode,
                    'shiprocket_order_id' => (string)$shiprocketOrderId,
                    'shiprocket_shipment_id' => (string)$shipmentId,
                    'order_status' => 'shipped',
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'shipped',
                    'location' => 'Shiprocket Logistics Hub',
                    'comment' => "Order registered with Shiprocket (Order ID: {$shiprocketOrderId}, Shipment: {$shipmentId}).",
                ]);

                return [
                    'success' => true,
                    'is_mock' => false,
                    'order_id' => $shiprocketOrderId,
                    'shipment_id' => $shipmentId,
                    'awb_code' => $awbCode,
                    'courier_name' => $courierName,
                    'message' => 'Order successfully sent to Shiprocket!',
                ];
            }

            Log::error('Shiprocket order creation failed: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Shiprocket API Error: ' . ($response->json('message') ?? 'Could not push shipment.'),
            ];
        } catch (Exception $e) {
            Log::error('Shiprocket exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch Live Tracking events for an Order.
     */
    public function trackOrder(Order $order): array
    {
        if (empty($order->awb_code) && empty($order->shiprocket_shipment_id)) {
            return [
                'success' => false,
                'message' => 'No tracking number or shipment ID associated with this order.',
            ];
        }

        if (!$this->isConfigured() || !$this->getToken()) {
            return [
                'success' => true,
                'is_mock' => true,
                'courier_name' => $order->courier_name ?? 'Express Logistics',
                'awb_code' => $order->awb_code ?? $order->tracking_number,
                'current_status' => ucfirst(str_replace('_', ' ', $order->order_status)),
                'activities' => $order->statusHistories->map(fn($h) => [
                    'date' => $h->created_at->format('d M Y, h:i A'),
                    'status' => ucfirst($h->status),
                    'location' => $h->location,
                    'activity' => $h->comment,
                ])->toArray(),
            ];
        }

        try {
            $token = $this->getToken();
            $awb = $order->awb_code ?? $order->tracking_number;

            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/courier/track/awb/{$awb}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'is_mock' => false,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Unable to fetch live tracking at this time.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
