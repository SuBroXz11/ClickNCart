<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getUsers(Request $request)
    {
        $role = $request->query('role');
        $status = $request->query('status');
        
        $query = User::query();
        
        if ($role) {
            $query->where('role', $role);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $users = $query->get();
        
        return response()->json($users);
    }

    public function approveRetailer($id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->isRetailer()) {
            return response()->json(['error' => 'User is not a retailer'], 400);
        }
        
        $user->status = User::STATUS_ACTIVE;
        $user->save();
        
        return response()->json(['message' => 'Retailer approved successfully']);
    }

    // In AdminController.php
public function getUnapprovedRetailers()
{
    $retailers = User::where('role', User::ROLE_RETAILER)
                     ->where('status', User::STATUS_PENDING)
                     ->get();
    
    return response()->json($retailers);
}

    public function blockUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = User::STATUS_BLOCKED;
        $user->save();
        
        return response()->json(['message' => 'User blocked successfully']);
    }

    public function unblockUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = User::STATUS_ACTIVE;
        $user->save();
        
        return response()->json(['message' => 'User unblocked successfully']);
    }
}