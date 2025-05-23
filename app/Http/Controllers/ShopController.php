<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    /**
     * Create a new shop (for retailer)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isRetailer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only retailers can create shops'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'logo' => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'banner' => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'address' => 'required|string',
            'contact_number' => 'required|string',
            'email' => 'required|email',
            'website' => 'sometimes|url',
            'social_links' => 'sometimes|array',
            'social_links.*' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $shopData = $validator->validated();
        $shopData['shop_id'] = 'SHOP' . Str::random(6);
        $shopData['user_id'] = $request->user()->id;
        $shopData['is_active'] = true;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('shops/logos', 'public');
            $shopData['logo'] = Storage::url($logoPath);
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('shops/banners', 'public');
            $shopData['banner'] = Storage::url($bannerPath);
        }

        $shop = Shop::create($shopData);

        return response()->json([
            'success' => true,
            'message' => 'Shop created successfully',
            'data' => $shop
        ], 201);
    }

    /**
     * Get shop by ID
     */
    public function show(Request $request, $id)
    {
        $shop = Shop::where('shop_id', $id)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // Check authorization:
        // - Admin can view any shop
        // - Retailer can view only their own shop
        if ($request->user()->role === User::ROLE_RETAILER && $shop->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this shop'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $shop
        ]);
    }

    /**
     * Update a shop (for retailer and admin)
     */
    public function update(Request $request, $id)
    {
        $shop = Shop::where('shop_id', $id)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // Check if the user is the owner of the shop or an admin
        if ($request->user()->role !== User::ROLE_ADMIN && $shop->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this shop'
            ], 403);
        }

   

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'logo' => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'banner' => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'address' => 'sometimes|string',
            'contact_number' => 'sometimes|string',
            'email' => 'sometimes|email',
            'website' => 'sometimes|url',
            'social_links' => 'sometimes|array',
            'social_links.*' => 'url',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
        
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $validator->validated();

   

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($shop->logo) {
                $oldLogoPath = str_replace('/storage/', '', $shop->logo);
                if (Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
            }
            
            $logoPath = $request->file('logo')->store('shops/logos', 'public');
            $updateData['logo'] = Storage::url($logoPath);
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            // Delete old banner if exists
            if ($shop->banner) {
                $oldBannerPath = str_replace('/storage/', '', $shop->banner);
                if (Storage::disk('public')->exists($oldBannerPath)) {
                    Storage::disk('public')->delete($oldBannerPath);
                }
            }
            
            $bannerPath = $request->file('banner')->store('shops/banners', 'public');
            $updateData['banner'] = Storage::url($bannerPath);
        }

        // Remove any null values from updateData
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });



        try {
            $shop->update($updateData);
            // Refresh the shop data to get the updated values
            $shop->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Shop updated successfully',
                'data' => $shop
            ]);
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shop: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a shop (for retailer and admin)
     */
    public function destroy(Request $request, $id)
    {
        $shop = Shop::where('shop_id', $id)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // Check if the user is the owner of the shop or an admin
        if ($request->user()->role !== User::ROLE_ADMIN && $shop->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this shop'
            ], 403);
        }

        $shop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shop deleted successfully'
        ]);
    }

    /**
     * Get shops by user ID
     */
    public function getUserShops(Request $request, $userId = null)
    {
        // If userId is not provided, use the authenticated user's ID
        $userId = $userId ?? $request->user()->id;

        // Only admin can view other users' shops
        if ($request->user()->role !== User::ROLE_ADMIN && $request->user()->id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view these shops'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $shops = Shop::where('user_id', $userId)
                     ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shops->items(),
            'meta' => [
                'current_page' => $shops->currentPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
                'has_next_page' => $shops->hasMorePages(),
                'has_previous_page' => $shops->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Get all shops for admin (with pagination)
     */
    public function getAllShops(Request $request)
    {
        if ($request->user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $shops = Shop::withTrashed()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shops->items(),
            'meta' => [
                'current_page' => $shops->currentPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
                'has_next_page' => $shops->hasMorePages(),
                'has_previous_page' => $shops->currentPage() > 1,
            ]
        ]);
    }
}