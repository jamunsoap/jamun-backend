<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            background-color: #FAF8F5;
            color: #1F0F2E;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #FFFFFF;
            border: 1px solid #E8E1D7;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(35, 15, 56, 0.03);
        }
        .header {
            background-color: #391959;
            padding: 32px 24px;
            text-align: center;
            color: #FAF8F5;
        }
        .header h1 {
            font-family: Georgia, serif;
            font-size: 26px;
            margin: 0;
            font-weight: normal;
            font-style: italic;
        }
        .header p {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 8px 0 0 0;
            opacity: 0.8;
            font-weight: bold;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .order-meta {
            font-size: 13px;
            color: #5F5266;
            margin-bottom: 24px;
            background: #FDFBFA;
            border: 1px solid #E8E1D7;
            padding: 16px;
            border-radius: 12px;
        }
        .order-meta p {
            margin: 4px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5F5266;
            padding-bottom: 12px;
            border-bottom: 1px solid #E8E1D7;
        }
        .table td {
            padding: 12px 0;
            border-bottom: 1px solid #FAF8F5;
            font-size: 14px;
        }
        .total-section {
            border-top: 2px solid #E8E1D7;
            padding-top: 16px;
            margin-top: 16px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .total-row.grand {
            font-size: 18px;
            font-weight: bold;
            color: #391959;
            margin-top: 12px;
            border-top: 1px dashed #E8E1D7;
            padding-top: 12px;
        }
        .address-box {
            background-color: #FAF8F5;
            border: 1px solid #E8E1D7;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 32px;
            font-size: 13px;
            line-height: 1.6;
        }
        .address-box h4 {
            margin: 0 0 8px 0;
            color: #391959;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-container {
            text-align: center;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background-color: #391959;
            color: #FFFFFF !important;
            text-decoration: none;
            padding: 14px 28px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-radius: 30px;
            box-shadow: 0 4px 12px rgba(57, 25, 89, 0.2);
        }
        .footer {
            background-color: #FAF8F5;
            border-top: 1px solid #E8E1D7;
            padding: 24px;
            text-align: center;
            font-size: 11px;
            color: #5F5266;
            line-height: 1.5;
        }
        .footer a {
            color: #391959;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Jadui Jamun</h1>
            <p>Order Confirmed</p>
        </div>
        <div class="content">
            <p class="greeting">Hi {{ $order->customer_name }},</p>
            <p class="greeting">Thank you for your order! We are preparing your fresh batch of Jadui Jamun Soap. Here are your order details:</p>

            <div class="order-meta">
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Placed On:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</p>
                <p><strong>Payment Method:</strong> {{ $order->payment_method }}</p>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->name }}</strong>
                            </td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">₹{{ number_format($item->price * $item->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <span>Items Subtotal:</span>
                    <span>₹{{ number_format($order->items_price, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Shipping & Delivery:</span>
                    <span>{{ $order->shipping_price == 0 ? 'FREE' : '₹' . number_format($order->shipping_price, 2) }}</span>
                </div>
                <div class="total-row grand">
                    <span>Grand Total:</span>
                    <span>₹{{ number_format($order->total_price, 2) }}</span>
                </div>
            </div>

            <div style="margin-top: 32px;"></div>

            <div class="address-box">
                <h4>Delivery Address</h4>
                <strong>{{ $order->customer_name }}</strong><br>
                {{ $order->street }}<br>
                {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}<br>
                Phone: {{ $order->customer_phone }}
            </div>

            <div class="btn-container">
                <a href="{{ $frontendUrl }}/track-order?orderId={{ $order->order_number }}&phone={{ $order->customer_phone }}" class="btn">Track Your Shipment</a>
            </div>
        </div>
        <div class="footer">
            <p>Need help? Contact our support team at <a href="mailto:support.jamunsoap@gmail.com">support.jamunsoap@gmail.com</a></p>
            <p>© {{ date('Y') }} Jamun Soap. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
