<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\DeleteCartRequest;
use App\Http\Requests\GetCartRequest;
use App\Http\Requests\UpdateCartQuantityRequest;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    /**
     * Add product to cart
     */
    public function addToCart(AddToCartRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $product = Product::findOrFail($request->product_id);

        // Check if requested quantity exceeds 20
        $requestedQuantity = $request->quantity ?? 1;
        if ($requestedQuantity > 20) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum quantity per item is 20'
            ], 422);
        }

        // Check if product is already in cart
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->first();

        // Calculate total quantity after adding new items
        $currentTotalQuantity = Cart::where('user_id', $user->id)->sum('quantity');
        $newTotalQuantity = $currentTotalQuantity + $requestedQuantity;

        if ($cartItem) {
            // If updating existing item, calculate new total
            $newTotalQuantity = $currentTotalQuantity - $cartItem->quantity + $requestedQuantity;
        }

        // Check if total quantity would exceed 20
        if ($newTotalQuantity > 20) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum total items in cart cannot exceed 20',
                'current_total' => $currentTotalQuantity,
                'available_slots' => 20 - $currentTotalQuantity
            ], 422);
        }

        if ($cartItem) {
            // Update quantity if product already in cart
            $cartItem->quantity = $requestedQuantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $requestedQuantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem->load('product'),
            'total_items' => $newTotalQuantity
        ]);
    }

    /**
     * Get user's cart items
     */
    public function getCart(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cartItems = Cart::with('product')
                        ->where('user_id', $user->id)
                        ->get();

        return response()->json([
            'success' => true,
            'cart_items' => $cartItems,
            'total_items' => $cartItems->sum('quantity'),
            'total_price' => $cartItems->sum(function ($item) {
                return $item->quantity * $item->product->price;
            }),
        ]);
    }

    public function cartCount(): JsonResponse
    {
        try {
            // Authenticate the user using JWT token
            $user = JWTAuth::parseToken()->authenticate();

            // Get cart items with product details
            $cartItems = Cart::with('product')
                            ->where('user_id', $user->id)
                            ->get();

            // Calculate totals
            $totalItems = $cartItems->sum('quantity');
           
            return response()->json([
                'success' => true,
                'cart_items_count' => $cartItems->count(), // Number of distinct products
                'total_quantity' => $totalItems, // Total quantity of all items
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart count: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteCart(DeleteCartRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $deleted = Cart::where('user_id', $user->id)
                      ->where('product_id', $request->product_id)
                      ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from cart successfully',
                'remaining_items' => Cart::where('user_id', $user->id)->count()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found in cart'
        ], 404);
    }

    /**
     * Update product quantity in cart
     */
    public function updateCartQuantity(UpdateCartQuantityRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cartItem = Cart::where('user_id', $user->id)
                       ->where('product_id', $request->product_id)
                       ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart'
            ], 404);
        }

        // Validate the new quantity (minimum 1)
        $validated = $request->validated();
        if ($validated['quantity'] < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Quantity must be at least 1'
            ], 422);
        }

        // Check product stock if needed
        $product = Product::find($request->product_id);
        if ($product && $validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Requested quantity exceeds available stock',
                'max_available' => $product->stock_quantity
            ], 422);
        }

        // Update the quantity
        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart quantity updated successfully',
            'cart_item' => $cartItem->load('product'),
            'new_quantity' => $cartItem->quantity
        ]);
    }
}