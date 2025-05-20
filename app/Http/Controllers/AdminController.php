<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    /**
     * GET /api/users?role=&status=&q=
     * Returns all users matching optional role, status, and search query (name or email).
     */
    public function getUsers(Request $request)
    {
        $role   = $request->query('role');   // e.g. "user", "retailer", "admin"
        $status = $request->query('status'); // e.g. "active", "blocked", "pending"
        $search = $request->query('q');      // free‐text search term

        $query = User::query();

        // Filter by role
        if ($role) {
            $query->where('role', $role);
        }

        // Filter by status, with special mapping for "pending"
        if ($status) {
            if ($status === 'pending') {
                // Map your "pending" dropdown value to the DB constant
                $query->where('status', User::STATUS_PENDING);
            } else {
                $query->where('status', $status);
            }
        }

        // Search by name OR email
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Fetch all matching users, newest first
        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json($users);
    }

    /**
     * POST /api/retailers/{id}/approve
     */
    public function approveRetailer($id)
    {
        $user = User::findOrFail($id);

        if ($user->role !== User::ROLE_RETAILER) {
            return response()->json(['error' => 'User is not a retailer'], 400);
        }

        $user->status = User::STATUS_ACTIVE;
        $user->save();

        return response()->json(['message' => 'Retailer approved successfully']);
    }

    /**
     * POST /api/users/{id}/block
     */
    public function blockUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = User::STATUS_BLOCKED;
        $user->save();

        return response()->json(['message' => 'User blocked successfully']);
    }

    /**
     * POST /api/users/{id}/unblock
     */
    public function unblockUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        return response()->json(['message' => 'User unblocked successfully']);
    }
}
