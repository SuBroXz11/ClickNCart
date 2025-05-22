<?php

namespace App\Http\Controllers;

use App\Models\CollectionSlot;
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
}