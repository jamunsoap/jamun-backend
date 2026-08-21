<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'street',
        'city',
        'state',
        'pincode',
        'country',
        'payment_method',
        'courier_name',
        'tracking_number',
        'awb_code',
        'shiprocket_order_id',
        'shiprocket_shipment_id',
        'items_price',
        'shipping_price',
        'total_price',
        'order_status',
        'is_paid',
        'paid_at',
        'is_delivered',
        'delivered_at',
    ];

    protected $casts = [
        'items_price' => 'decimal:2',
        'shipping_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'is_delivered' => 'boolean',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'JAMUN-' . strtoupper(Str::random(8));
            }
        });
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'asc');
    }
}
