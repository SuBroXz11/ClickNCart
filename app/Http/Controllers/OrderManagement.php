<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Models\Product;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;


class OrderManagement extends Controller
{
    /**
     * Get user's orders
     */
    public function getUserOrders(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        $orders = Order::with(['items' => function($query) {
                $query->with(['product', 'shop']);
            }])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get shop's orders for the authenticated retailer
     */
public function getShopOrders(Request $request)
{
    $user = JWTAuth::parseToken()->authenticate();
    
    // Get all shops belonging to the user
    $shops = Shop::where('user_id', $user->id)->get();
    Log::info($shops);

    if ($shops->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No shops found for this user'
        ], 404);
    }

    // Get shop IDs for the whereIn clause
    $shopIds = $shops->pluck('shop_id');
    
    $query = OrderItem::with(['order.user', 'product'])
        ->whereIn('shop_id', $shopIds);

    // Add status filter if provided
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    // Add shop_id filter if provided (to filter orders for a specific shop)
    if ($request->has('shop_id')) {
        // Validate that the requested shop_id belongs to the user
        if (!$shopIds->contains($request->shop_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found or does not belong to you'
            ], 403);
        }
        $query->where('shop_id', $request->shop_id);
    }

    $orders = $query->orderBy('created_at', 'desc')
        ->paginate($request->per_page ?? 10);

    return response()->json([
        'success' => true,
        'data' => $orders
    ]);
}

    /**
     * Get orders by shop ID (for admin or the shop owner)
     */
    public function getOrdersByShop(Request $request, $shopId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $shop = Shop::find($shopId);
        
        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // Check if user is admin or the shop owner
        if (!$user->isAdmin() && $user->id != $shop->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view these orders'
            ], 403);
        }

        $query = OrderItem::with(['order.user', 'product'])
            ->where('shop_id', $shopId);

        // Add status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get all orders (for admin)
     */
    public function getAllOrders(Request $request)
    {
        $query = Order::with(['items.product', 'user']);

        // Add status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Add payment status filter if provided
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get order details
     */
    public function getOrderDetails($orderId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        Log::info("doing");
        
        $order = Order::with(['items' => function($query) use ($user) {
                $query->with(['product', 'shop']);
                
                if ($user->isRetailer()) {
                    $shop = Shop::where('user_id', $user->id)->first();
                    $query->where('shop_id', $shop->shop_id);
                }
            }])
            ->where('order_id', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Check authorization
        if ($user->id != $order->user_id && !$user->isAdmin() && !$user->isRetailer()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this order'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Request order cancellation
     */
    public function requestCancellation(Request $request, $orderItemId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        $orderItem = OrderItem::with('order')
            ->where('order_item_id', $orderItemId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        // Check if user owns the order
        if ($user->id != $orderItem->order->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this order'
            ], 403);
        }

        // Check if cancellation is possible
        if (!in_array($orderItem->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled at this stage'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $orderItem->update([
            'cancel_requested' => 'pending',
            'cancel_reason' => $request->reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation requested successfully'
        ]);
    }

    /**
     * Process cancellation request (for shop owner)
     */
    public function processCancellation(Request $request, $orderItemId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user->isRetailer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only shop owners can process cancellations'
            ], 403);
        }

        $orderItem = OrderItem::with('order')
            ->where('order_item_id', $orderItemId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        $shop = Shop::where('user_id', $user->id)->first();
        
        if ($orderItem->shop_id != $shop->shop_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to process this cancellation'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject|nullable|string|max:500'
        ]);

        if ($orderItem->cancel_requested != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'No pending cancellation request'
            ], 400);
        }

        if ($request->action == 'approve') {
            $orderItem->update([
                'cancel_requested' => 'approved',
                'status' => 'cancelled'
            ]);

            // Restore product stock
            Product::where('product_id', $orderItem->product_id)
                ->increment('stock_quantity', $orderItem->quantity);

            // Check if all items are cancelled
            $allCancelled = OrderItem::where('order_id', $orderItem->order_id)
                ->where('status', '!=', 'cancelled')
                ->doesntExist();

            if ($allCancelled) {
                $orderItem->order->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cancellation approved successfully'
            ]);
        } else {
            $orderItem->update([
                'cancel_requested' => 'rejected',
                'cancel_reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancellation rejected'
            ]);
        }
    }

    /**
     * Update order item status (for shop owner)
     */
    public function updateOrderStatus(Request $request, $orderItemId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user->isRetailer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only shop owners can update order status'
            ], 403);
        }

        $orderItem = OrderItem::where('order_item_id', $orderItemId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found'
            ], 404);
        }

        $shop = Shop::where('user_id', $user->id)->first();
        
        if ($orderItem->shop_id != $shop->shop_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this order'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled'
        ]);

        $orderItem->update(['status' => $request->status]);

        // Check if all items are delivered
        if ($request->status == 'delivered') {
            $allDelivered = OrderItem::where('order_id', $orderItem->order_id)
                ->where('status', '!=', 'delivered')
                ->doesntExist();

            if ($allDelivered) {
                $orderItem->order->update(['status' => 'completed']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    }
}