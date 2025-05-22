<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionSlot extends Model
{
    use HasFactory;
    const STATUS_BOOKED = 'booked';
    const STATUS_COLLECTED = 'collected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'slot_id',
        'order_id',
        'date',
        'start_time',
        'end_time',
        'customer_name',
        'customer_phone',
        'special_instructions',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}