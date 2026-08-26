<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Process checkout and create complete order in a secure DB transaction.
     *
     * @param array $data Validated checkout payload
     * @return Order
     * @throws Exception
     */
    public function processOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $itemsPrice = 0;
            $itemsToCreate = [];

            // 1. Validate stock & calculate authentic prices from database
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product || !$product->is_active) {
                    throw new Exception("Product '{$item['product_id']}' is currently unavailable.");
                }

                if ($product->stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for '{$product->name}'. Available: {$product->stock}.");
                }

                // Use discount_price if active, else standard price
                $unitPrice = ($product->discount_price > 0 && $product->discount_price < $product->price)
                    ? (float)$product->discount_price
                    : (float)$product->price;

                $lineTotal = $unitPrice * $item['quantity'];
                $itemsPrice += $lineTotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_model' => $product,
                    'name' => $product->name,
                    'price' => $unitPrice,
                    'quantity' => $item['quantity'],
                ];
            }

            // Shipping calculation: Free shipping for online payments OR items total >= 449, else 49.00
            $isOnlinePayment = in_array(strtoupper($data['payment_method']), ['UPI', 'RAZORPAY', 'CARD']);
            $shippingPrice = ($isOnlinePayment || $itemsPrice >= 449 || $itemsPrice == 0) ? 0.00 : 49.00;
            $totalPrice = max(0.00, $itemsPrice + $shippingPrice);

            // 2. Create the master Order record
            $order = Order::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'street' => $data['street'],
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
                'country' => $data['country'] ?? 'India',
                'payment_method' => $data['payment_method'],
                'items_price' => $itemsPrice,
                'shipping_price' => $shippingPrice,
                'total_price' => $totalPrice,
                'order_status' => 'pending',
                'is_paid' => false,
                'is_delivered' => false,
            ]);

            // 3. Create items and decrement inventory
            foreach ($itemsToCreate as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'name' => $itemData['name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Decrement stock
                $itemData['product_model']->decrement('stock', $itemData['quantity']);
            }

            // 4. Create initial milestone in Order Status History (for Track Order page)
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'location' => 'Online Store',
                'comment' => 'Order placed successfully and awaiting confirmation.',
            ]);

            // 5. Create initial Payment record
            Payment::create([
                'order_id' => $order->id,
                'gateway' => $data['payment_method'],
                'amount' => $totalPrice,
                'currency' => 'INR',
                'status' => 'pending',
                'payload' => [
                    'payment_method' => $data['payment_method'],
                    'placed_at' => now()->toIso8601String(),
                ],
            ]);

            // 6. Send database notification to admin users
            try {
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    \Filament\Notifications\Notification::make()
                        ->title('New Order Received! 🍇')
                        ->body("Order #{$order->order_number} has been placed by {$order->customer_name} for ₹{$order->total_price}.")
                        ->icon('heroicon-o-shopping-bag')
                        ->iconColor('success')
                        ->actions([
                            \Filament\Actions\Action::make('view')
                                ->button()
                                ->url("/admin/orders/{$order->id}/edit"),
                        ])
                        ->sendToDatabase($admin);
                }
            } catch (Exception $ne) {
                \Illuminate\Support\Facades\Log::error("Failed to notify admins of new order {$order->id}: " . $ne->getMessage());
            }

            return $order->load(['orderItems', 'statusHistories', 'latestPayment']);
        });
    }
}
