<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        // Only admin can access all users
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:admin,retailer,trader',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $perPage = $request->per_page ?? 15;
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'has_next' => $users->hasMorePages(),
                'has_previous' => $users->currentPage() > 1,
            ]
        ]);
    }

    public function blockUser($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->status = 'blocked';
        $user->save();

        return response()->json(['message' => 'User blocked successfully']);
    }

    public function unblockUser($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->status = 'verified';
        $user->save();

        return response()->json(['message' => 'User unblocked successfully']);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Users can only update their own profile unless they're admin
        if (auth()->user()->id != $id && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'phone_number' => 'sometimes|string|max:20|unique:users,phone_number,'.$id,
            'address' => 'sometimes|string|max:255',
            'business_name' => 'sometimes|string|max:255',
            'tax_id' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->update($validator->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function deactivateAccount()
    {
        $user = auth()->user();
        $user->status = 'deactivated';
        $user->save();

        auth()->logout();

        return response()->json(['message' => 'Account deactivated successfully']);
    }
}