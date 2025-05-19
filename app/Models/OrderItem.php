<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const CANCELLATION_PENDING = 'pending';
    const CANCELLATION_APPROVED = 'approved';
    const CANCELLATION_REJECTED = 'rejected';

    protected $fillable = [
        'order_item_id',
        'order_id',
        'product_id',
        'shop_id',
        'product_name',
        'price',
        'quantity',
        'total',
        'status',
        'cancel_requested',
        'cancel_reason',
    ];

    protected $casts = [
        'price' => 'float',
        'total' => 'float',
        'quantity' => 'integer',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
     return $this->belongsTo(Product::class, 'product_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}