<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FAF8F5;
            color: #1F0F2E;
            margin: 0;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #E8E1D7;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(35, 15, 56, 0.03);
            max-width: 600px;
            margin: 20px auto;
        }
        h1 {
            color: #391959;
            font-size: 22px;
            margin-top: 0;
            border-bottom: 2px solid #391959;
            padding-bottom: 10px;
        }
        .details {
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .details p {
            margin: 8px 0;
        }
        .btn-container {
            text-align: center;
            margin-top: 25px;
        }
        .btn {
            display: inline-block;
            background-color: #391959;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 30px;
            box-shadow: 0 4px 12px rgba(57, 25, 89, 0.2);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔔 New Order Received!</h1>
        <p>Hello Admin, a new order has been successfully placed on the Jamun Soap online store.</p>
        
        <div class="details">
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Customer Name:</strong> {{ $order->customer_name }}</p>
            <p><strong>Phone Number:</strong> {{ $order->customer_phone }}</p>
            <p><strong>Email Address:</strong> {{ $order->customer_email ?? 'N/A' }}</p>
            <p><strong>Payment Method:</strong> {{ $order->payment_method }}</p>
            <p><strong>Grand Total:</strong> ₹{{ number_format($order->total_price, 2) }}</p>
            <p><strong>Delivery Address:</strong> {{ $order->street }}, {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}</p>
        </div>

        <div class="btn-container">
            <a href="{{ url('/admin/orders/' . $order->id . '/edit') }}" class="btn">View Order in Filament Panel</a>
        </div>
    </div>
</body>
</html>
