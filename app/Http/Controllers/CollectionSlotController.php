<?php

namespace App\Http\Controllers;

use App\Models\CollectionSlot;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollectionSlotController extends Controller
{
    /**
     * Check availability and create a new collection slot
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   

    /**
     * Check slot availability without creating
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkAvailability(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for existing slots in the same time period
        $existingSlotsCount = CollectionSlot::where('date', $request->date)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->count();

        // Maximum 20 slots allowed for the same time period
        $maxSlotsPerTime = 20;
        $availableSlots = $maxSlotsPerTime - $existingSlotsCount;

        return response()->json([
            'success' => true,
            'message' => 'Slot availability checked',
            'data' => [
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'available_slots' => $availableSlots,
                'is_available' => $availableSlots > 0
            ]
        ]);
    }

    /**
     * Get all collection slots with filters (Admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAllSlots(Request $request)
    {
        $query = CollectionSlot::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $slots = $query->orderBy('date', 'asc')
                      ->orderBy('start_time', 'asc')
                      ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Collection slots retrieved successfully',
            'data' => $slots
        ]);
    }

    /**
     * Update collection slot status (Admin only)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slotId
     * @return \Illuminate\Http\Response
     */
    public function updateSlotStatus(Request $request, $slotId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:booked,collected,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $slot = CollectionSlot::where('slot_id', $slotId)->first();

        if (!$slot) {
            return response()->json([
                'success' => false,
                'message' => 'Collection slot not found'
            ], 404);
        }

        $slot->status = $request->status;
        $slot->save();

        return response()->json([
            'success' => true,
            'message' => 'Collection slot status updated successfully',
            'data' => $slot
        ]);
    }

    /**
     * Get collection slot by order ID (User)
     *
     * @param  string  $orderId
     * @return \Illuminate\Http\Response
     */
    public function getSlotByOrder($orderId)
    {
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $slot = CollectionSlot::where('order_id', $order->id)->first();

        if (!$slot) {
            return response()->json([
                'success' => false,
                'message' => 'No collection slot found for this order'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Collection slot retrieved successfully',
            'data' => $slot
        ]);
    }
}