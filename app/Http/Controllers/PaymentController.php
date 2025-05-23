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
use Illuminate\Support\Facades\Log;
use App\Models\CollectionSlot;
use Illuminate\Support\Facades\DB;


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
            'collection_slot_id' => 'sometimes|string|exists:collection_slots,slot_id',
            'customer_name' => 'required_if:collection_slot_id,!=,null|sometimes|string|max:255',
            'customer_phone' => 'required_if:collection_slot_id,!=,null|sometimes|string|max:20',
            'special_instructions' => 'sometimes|string|max:500',
            'date' => 'required_if:collection_slot_id,null|sometimes|date',
            'start_time' => 'required_if:collection_slot_id,null|sometimes|date_format:H:i',
            'end_time' => 'required_if:collection_slot_id,null|sometimes|date_format:H:i',
            'shipping_address' => 'required|string|max:500',
            'billing_address' => 'required|string|max:500',
            'notes' => 'sometimes|string|max:500'
        ]);

        // Get cart items
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

        // Convert NPR to USD (divide by 137)
        $subtotalUSD = round($subtotal / 137, 2);
        $taxUSD = round($tax / 137, 2);
        $shippingUSD = round($shipping / 137, 2);
        $totalUSD = round($total / 137, 2);

        // Calculate item total in USD for PayPal
        $paypalItemTotal = $cartItems->sum(function($item) {
            return round($item->product->price / 137, 2) * $item->quantity;
        });

        // Ensure total matches the sum of components
        $totalUSD = number_format($paypalItemTotal + $taxUSD + $shippingUSD, 2, '.', '');

        // Handle collection slot
        $collectionSlotId = $request->collection_slot_id;
        
        if (!$collectionSlotId && ($request->date && $request->start_time && $request->end_time)) {
            // Create a new collection slot
            $collectionSlot = CollectionSlot::create([
                'slot_id' => 'SLOT' . strtoupper(uniqid()),
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'special_instructions' => $request->special_instructions,
                'status' => CollectionSlot::STATUS_BOOKED,
            ]);
            
            $collectionSlotId = $collectionSlot->slot_id;
        }

        // Create order
        $order = Order::create([
            'order_id' => 'ORD' . strtoupper(uniqid()),
            'user_id' => $user->id,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'payment_status' => 'pending',
            'status' => 'pending',
            'collection_slot_id' => $collectionSlotId ?? null,
            'shipping_address' => $request->shipping_address,
            'billing_address' => $request->billing_address,
            'notes' => $request->notes
        ]);

        // Create order items
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_item_id' => 'ORDITM' . strtoupper(uniqid()),
                'order_id' => $order->id,
                'product_id' => $item->product->product_id,
                'shop_id' => $item->product->shop_id,
                'product_name' => $item->product->name,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'total' => $item->quantity * $item->product->price,
            ]);
        }

        // Update collection slot if one was selected or created
        if ($collectionSlotId) {
            CollectionSlot::where('slot_id', $collectionSlotId)
                ->update([
                    'order_id' => $order->id,
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'special_instructions' => $request->special_instructions,
                    'status' => CollectionSlot::STATUS_BOOKED,
                ]);
        }

        // Get PayPal access token
        $tokenResponse = Http::withOptions([
            'verify' => false
        ])->withBasicAuth($this->clientId, $this->secret)
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

        // Create PayPal order with USD amounts
        $paypalResponse = Http::withOptions([
            'verify' => false
        ])->withToken($accessToken)
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
                            'value' => $totalUSD,
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($paypalItemTotal, 2, '.', '')
                                ],
                                'tax_total' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($taxUSD, 2, '.', '')
                                ],
                                'shipping' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($shippingUSD, 2, '.', '')
                                ]
                            ]
                        ],
                        'items' => $cartItems->map(function($item) {
                            return [
                                'name' => $item->product->name,
                                'unit_amount' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format(round($item->product->price / 137, 2), 2, '.', '')
                                ],
                                'quantity' => $item->quantity,
                                'sku' => $item->product->product_id
                            ];
                        })->toArray()
                    ]
                ],
             'application_context' => [
    'brand_name' => config('app.name'),           // or hard-code this too if you like
    'return_url' => 'http://clickncart.test/payment/success',
    'cancel_url' => 'http://clickncart.test/payment/cancel',
    'user_action'=> 'PAY_NOW',
],

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
                'approve_url' => collect($paypalOrder['links'])->firstWhere('rel', 'approve')['href'],
                'collection_slot_id' => $collectionSlotId
            ]
        ]);
    }

    /**
     * Handle PayPal payment success
     */
    public function paymentSuccess(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'token' => 'required', // This should be the PayPal Order ID
            'PayerID' => 'required'
        ]);

        try {
            // 1. Get PayPal access token
            $tokenResponse = Http::withBasicAuth($this->clientId, $this->secret)
                ->withoutVerifying()
                ->asForm()
                ->post($this->paypalBaseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                Log::error('PayPal Token Error', [
                    'status' => $tokenResponse->status(),
                    'response' => $tokenResponse->json()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with PayPal',
                    'error' => $tokenResponse->json()
                ], 500);
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // 2. Capture the payment
            $captureResponse = Http::withToken($accessToken)
                ->withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation',
                    'PayPal-Request-Id' => uniqid()
                ])
                ->post($this->paypalBaseUrl . '/v2/checkout/orders/' . $request->token . '/capture', (object)[]);

            if (!$captureResponse->successful()) {
                Log::error('PayPal Capture Error', [
                    'order_id' => $request->token,
                    'status' => $captureResponse->status(),
                    'response' => $captureResponse->json(),
                    'endpoint' => $this->paypalBaseUrl . '/v2/checkout/orders/' . $request->token . '/capture'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed',
                    'error' => $captureResponse->json()
                ], 400);
            }

            $captureData = $captureResponse->json();

            // 3. Find and update the order
            $order = Order::where('transaction_id', $request->token)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // 4. Update order status
            $order->update([
                'payment_status' => 'completed',
                'status' => 'processing',
                'paypal_capture_id' => $captureData['id'],
                'payer_id' => $request->PayerID
            ]);

            // 5. Update order items
            $order->items()->update(['status' => 'processing']);

            // 6. Clear user's cart
            Cart::where('user_id', $order->user_id)->delete();

            // 7. Update product stock
            foreach ($order->items as $item) {
                Product::where('product_id', $item->product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }

            // 8. Send confirmation email (optional)
            // Mail::to($order->user->email)->send(new OrderConfirmation($order));

            return response()->json([
                'success' => true,
                'message' => 'Payment successful',
                'data' => [
                    'order_id' => $order->id,
                    'transaction_id' => $captureData['id'],
                    'status' => $order->status,
                    'amount' => $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ]);
        }
    }

    private function formatOrderResponse($order)
    {
        return [
            'order_id' => $order->order_id,
            'user_id' => $order->user_id,
            'transaction_id' => $order->transaction_id,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'tax' => $order->tax,
            'shipping' => $order->shipping,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'collection_details' => $order->collectionSlot ? [
                'slot_id' => $order->collectionSlot->slot_id,
                'date' => $order->collectionSlot->date,
                'start_time' => $order->collectionSlot->start_time,
                'end_time' => $order->collectionSlot->end_time,
                'shop' => $order->collectionSlot->shop->name,
                'customer_name' => $order->collectionSlot->customer_name,
                'customer_phone' => $order->collectionSlot->customer_phone,
                'special_instructions' => $order->collectionSlot->special_instructions,
                'status' => $order->collectionSlot->status,
            ] : null,
            'created_at' => $order->created_at,
            'products' => $order->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'shop_id' => $item->shop_id,
                    'status' => $item->status
                ];
            })
        ];
    }
}