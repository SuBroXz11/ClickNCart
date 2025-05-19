<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
{
    private $paypalBaseUrl;
    private $clientId;
    private $secret;

    public function __construct()
    {
        $this->paypalBaseUrl = config('services.paypal.sandbox') ? 
            'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
        $this->clientId = config('services.paypal.client_id');
        $this->secret = config('services.paypal.secret');
    }

    /**
     * Create PayPal payment
     */
    public function createPayment(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Validate request
        $request->validate([
            'total' => 'required|numeric|min:0.01',
            'tax' => 'sometimes|numeric|min:0',
            'shipping' => 'sometimes|numeric|min:0',
        ]);

        // Get cart items to include in order
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        
        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty'
            ], 400);
        }

        // Calculate totals
        $subtotal = $cartItems->sum(function($item) {
            return $item->quantity * $item->product->price;
        });
        
        $tax = $request->tax ?? 0;
        $shipping = $request->shipping ?? 0;
        $total = $subtotal + $tax + $shipping;

        // Create order first
        $order = Order::create([
            'order_id' => 'ORD' . strtoupper(uniqid()),
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        // Create order items
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_item_id' => 'ORDITM' . strtoupper(uniqid()),
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'shop_id' => $item->product->shop_id,
                'product_name' => $item->product->name,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'total' => $item->quantity * $item->product->price,
            ]);
        }

        // Get PayPal access token
        $tokenResponse = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post($this->paypalBaseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with PayPal'
            ], 500);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Create PayPal order
        $paypalResponse = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ])
            ->post($this->paypalBaseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $order->order_id,
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => $total,
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'USD',
                                    'value' => $subtotal
                                ],
                                'tax_total' => [
                                    'currency_code' => 'USD',
                                    'value' => $tax
                                ],
                                'shipping' => [
                                    'currency_code' => 'USD',
                                    'value' => $shipping
                                ]
                            ]
                        ],
                        'items' => $cartItems->map(function($item) {
                            return [
                                'name' => $item->product->name,
                                'unit_amount' => [
                                    'currency_code' => 'USD',
                                    'value' => $item->product->price
                                ],
                                'quantity' => $item->quantity,
                                'sku' => $item->product->product_id
                            ];
                        })->toArray()
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'return_url' => config('app.frontend_url') . '/payment/success',
                    'cancel_url' => config('app.frontend_url') . '/payment/cancel',
                    'user_action' => 'PAY_NOW'
                ]
            ]);

        if (!$paypalResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create PayPal order',
                'errors' => $paypalResponse->json()
            ], 500);
        }

        $paypalOrder = $paypalResponse->json();

        // Update order with PayPal ID
        $order->update([
            'transaction_id' => $paypalOrder['id'],
            'payment_method' => 'paypal'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated',
            'data' => [
                'order_id' => $order->order_id,
                'paypal_order_id' => $paypalOrder['id'],
                'approve_url' => collect($paypalOrder['links'])->firstWhere('rel', 'approve')['href']
            ]
        ]);
    }

    /**
     * Handle PayPal payment success
     */
    public function paymentSuccess(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'PayerID' => 'required'
        ]);

        // Get PayPal access token
        $tokenResponse = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post($this->paypalBaseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with PayPal'
            ], 500);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Capture PayPal payment
        $captureResponse = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->post($this->paypalBaseUrl . '/v2/checkout/orders/' . $request->token . '/capture');

        if (!$captureResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment capture failed',
                'errors' => $captureResponse->json()
            ], 500);
        }

        $captureData = $captureResponse->json();

        // Update order status
        $order = Order::where('transaction_id', $request->token)->firstOrFail();
        
        $order->update([
            'payment_status' => 'completed',
            'status' => 'processing',
            'transaction_id' => $captureData['id']
        ]);

        // Update order items status
        $order->items()->update(['status' => 'processing']);

        // Clear user's cart
        Cart::where('user_id', $order->user_id)->delete();

        // Update product stock
        foreach ($order->items as $item) {
            Product::where('product_id', $item->product_id)
                ->decrement('stock_quantity', $item->quantity);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment successful',
            'data' => $this->formatOrderResponse($order)
        ]);
    }

    /**
     * Format order response
     */
    private function formatOrderResponse($order)
    {
        return [
            'order_id' => $order->order_id,
            'user_id' => $order->user_id,
            'transaction_id' => $order->transaction_id,
            'totalAmount' => $order->subtotal,
            'totalAmountWithTax' => $order->total,
            'tax' => $order->tax,
            'shipping' => $order->shipping,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'products' => $order->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'shop_id' => $item->shop_id,
                    'status' => $item->status
                ];
            })
        ];
    }
}