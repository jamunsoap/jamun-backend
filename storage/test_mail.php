<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlaced;
use App\Models\Order;

try {
    echo "Attempting to send email via SMTP...\n";
    $order = Order::latest()->first();
    if (!$order) {
        throw new Exception("No order found to test with.");
    }
    
    Mail::to('vijaykatariya1825@gmail.com')->send(new OrderPlaced($order));
    echo "SUCCESS: Email sent successfully!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
