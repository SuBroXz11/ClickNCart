<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CollectionSlot extends Model
{
    use HasFactory;

    // If you're rolling your own PK (slot_id):
    public $incrementing = false;
    protected $keyType = 'string';

    const STATUS_BOOKED    = 'booked';
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
        'status',
    ];

    // Don’t cast date → datetime automatically; we’ll handle formatting in the setter
    protected $casts = [
        'order_id'       => 'integer',
        // leave date, start_time, end_time *out* of casts so no auto-formatting
    ];

    /**
     * Mutator: always format date strictly as YYYY-MM-DD
     */
    public function setDateAttribute($value)
    {
        $this->attributes['date'] = Carbon::parse($value)
                                           ->format('Y-m-d');
    }

    /**
     * If these are VARCHAR2 columns, just store the raw string.
     * If they’re Oracle DATEs, you could do:
     *    ->format('H:i')
     * or combine with date if needed.
     */
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = $value;
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = $value;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
