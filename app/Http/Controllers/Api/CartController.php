<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\DeleteCartRequest;
use App\Http\Requests\GetCartRequest;
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

        // Check if product is already in cart
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->first();

        if ($cartItem) {
            // Update quantity if product already in cart
            $cartItem->quantity += $request->quantity ?? 1;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity ?? 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem->load('product'),
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
}