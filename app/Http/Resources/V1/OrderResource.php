<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
                'email' => $this->customer_email,
            ],
            'shipping_address' => [
                'street' => $this->street,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
                'country' => $this->country,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'is_paid' => (bool)$this->is_paid,
                'paid_at' => $this->paid_at?->toIso8601String(),
                'status' => $this->latestPayment?->status ?? ($this->is_paid ? 'successful' : 'pending'),
            ],
            'pricing' => [
                'items_price' => (float)$this->items_price,
                'discount' => (float)($this->orderItems ?? collect())->reduce(function ($carry, $item) {
                    $originalPrice = $item->product?->price ?? $item->price;
                    $discountPerUnit = max(0.00, (float)$originalPrice - (float)$item->price);
                    return $carry + ($discountPerUnit * $item->quantity);
                }, 0.00),
                'shipping_price' => (float)$this->shipping_price,
                'total_price' => (float)$this->total_price,
            ],
            'status' => [
                'current' => $this->order_status,
                'is_delivered' => (bool)$this->is_delivered,
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'courier_name' => $this->courier_name,
                'tracking_number' => $this->tracking_number,
                'awb_code' => $this->awb_code,
            ],
            'courier_name' => $this->courier_name,
            'tracking_number' => $this->tracking_number ?? $this->awb_code,
            'items' => ($this->orderItems ?? collect())->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'price' => (float)$item->price,
                'quantity' => (int)$item->quantity,
                'subtotal' => (float)($item->price * $item->quantity),
            ]),
            'timeline' => ($this->statusHistories ?? collect())->map(fn($history) => [
                'status' => $history->status,
                'location' => $history->location,
                'comment' => $history->comment,
                'timestamp' => $history->created_at?->toIso8601String(),
                'formatted_time' => $history->created_at?->format('d M Y, h:i A'),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_date' => $this->created_at?->format('d M Y, h:i A'),
        ];
    }
}
