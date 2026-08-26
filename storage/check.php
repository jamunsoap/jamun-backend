<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$order = \App\Models\Order::latest()->first();
if ($order) {
    echo "ID: " . $order->id . "\n";
    echo "Number: " . $order->order_number . "\n";
    echo "Email: '" . $order->customer_email . "'\n";
    echo "Phone: " . $order->customer_phone . "\n";
    echo "Status: " . $order->order_status . "\n";
    echo "Payment: " . $order->payment_method . "\n";
} else {
    echo "No orders found.\n";
}
