<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Review;

class ProductController extends Controller
{
    /**
     * Create a new product (for retailer and admin)
     */
    public function store(Request $request)
    {
        // 1) Validate everything, images must be real files
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'description'      => 'required|string',
            'price'            => 'required|numeric|min:0',
            'category'         => 'required|string|max:255',
            'subcategory'      => 'required|string|max:255',
            'brand'            => 'required|string|max:255',
            'stock_quantity'   => 'required|integer|min:0',
            'images'           => 'required|array',
            'images.*'         => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'variants'         => 'array',
            'variants.*.variant_id' => 'string',
            'variants.*.color'      => 'string',
            'variants.*.stock'      => 'integer|min:0',
            'specifications.battery_life'  => 'sometimes|string',
            'specifications.connectivity'  => 'sometimes|string',
            'specifications.weight'        => 'sometimes|string',
            'is_featured'      => 'sometimes|boolean',
            'shop_id'          => 'required|exists:shops,shop_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2) Pull out validated data, except images
        $productData = $validator->validated();
        unset($productData['images']);

        // 3) Add generated fields
        $productData['product_id'] = 'PROD' . Str::upper(Str::random(6));
        $productData['ratings']    = ['average' => 0, 'count' => 0];
        $productData['is_active']  = true;

        // 4) Process each uploaded image
        $storedImageUrls = [];
        foreach ($request->file('images') as $file) {
            // Save under storage/app/public/products
            $path = $file->store('products', 'public');
            // Build a public URL: /storage/products/filename.jpg
            $storedImageUrls[] = Storage::url($path);
        }
        $productData['images'] = $storedImageUrls;

        // 5) Create the product
        $product = Product::create($productData);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $product,
        ], 201);
    }

    /**
     * Get product by ID
     */
   public function show(Request $request, $id)
{
    $product = Product::with(['reviews' => function($query) {
        $query->latest()->limit(5)->with('user:id,name');
    }])->where('product_id', $id)->first();

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ], 404);
    }

    if ($request->user()->role === User::ROLE_RETAILER
        && $product->retailer_id !== $request->user()->id) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized to view this product'
        ], 403);
    }

    if ($request->user()->role === User::ROLE_USER && !$product->is_active) {
        return response()->json([
            'success' => false,
            'message' => 'Product not available'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data'    => $product
    ]);
}

   public function getProductById($productId)
{
    $product = Product::with(['reviews' => function($query) {
        $query->latest()->limit(5)->with('user:id,name');
    }])->where('product_id', $productId)
      ->where('is_active', true)
      ->first();

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found or not available'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data'    => $product
    ]);
}

    /**
     * Update a product (for retailer and admin)
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('product_id', $id)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if the user owns the product's shop
        $shop = Shop::where('shop_id', $product->shop_id)
                   ->where('user_id', $request->user()->id)
                   ->first();

        if (!$shop && $request->user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this product'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|string|max:255',
            'description'    => 'sometimes|string',
            'price'          => 'sometimes|numeric|min:0',
            'category'       => 'sometimes|string|max:255',
            'subcategory'    => 'sometimes|string|max:255',
            'brand'          => 'sometimes|string|max:255',
            'stock_quantity' => 'sometimes|integer|min:0',
            'images'         => 'sometimes|array',
            'images.*'       => 'sometimes|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            'variants'       => 'sometimes|array',
            'variants.*.variant_id' => 'sometimes|string',
            'variants.*.color'      => 'sometimes|string',
            'variants.*.stock'      => 'sometimes|integer|min:0',
            'specifications.battery_life'  => 'sometimes|string',
            'specifications.connectivity'  => 'sometimes|string',
            'specifications.weight'        => 'sometimes|string',
            'is_active'      => 'sometimes|boolean',
            'is_featured'    => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $updateData = $validator->validated();

        // If new images were uploaded, process them
        if (isset($updateData['images'])) {
            // Delete old images
            if ($product->images) {
                foreach ($product->images as $oldImage) {
                    $oldImagePath = str_replace('/storage/', '', $oldImage);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                    }
                }
            }

            $storedImageUrls = [];
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                $storedImageUrls[] = Storage::url($path);
            }
            $updateData['images'] = $storedImageUrls;
        }

        // Handle variants as array
        if (isset($updateData['variants'])) {
            // Ensure variants is an array
            if (is_string($updateData['variants'])) {
                $updateData['variants'] = json_decode($updateData['variants'], true);
            }
            // Convert stock values to integers
            foreach ($updateData['variants'] as &$variant) {
                if (isset($variant['stock'])) {
                    $variant['stock'] = (int) $variant['stock'];
                }
            }
        }

        // Handle specifications as array
        if (isset($updateData['specifications'])) {
            // Ensure specifications is an array
            if (is_string($updateData['specifications'])) {
                $updateData['specifications'] = json_decode($updateData['specifications'], true);
            }
        }

        // Remove any null values
        $updateData = array_filter($updateData, function($value) {
            return $value !== null;
        });

        // Update the product
        $product->update($updateData);

        // Refresh the product to get the updated data
        $product->refresh();

        // Ensure variants and specifications are returned as arrays
        if ($product->variants && is_string($product->variants)) {
            $product->variants = json_decode($product->variants, true);
        }
        if ($product->specifications && is_string($product->specifications)) {
            $product->specifications = json_decode($product->specifications, true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => $product
        ]);
    }

    /**
     * Delete a product (for retailer and admin)
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::where('product_id', $id)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get all products for a specific retailer (with pagination)
     */
    public function getRetailerProducts(Request $request, $retailerId = null)
    {
        // If retailerId is not provided, use the authenticated user's ID
        $retailerId = $retailerId ?? $request->user()->id;

        // Only admin can view other retailers' products
        if ($request->user()->role !== User::ROLE_ADMIN && $request->user()->id != $retailerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view these products'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $products = Product::where('retailer_id', $retailerId)
                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Get all products for admin (with pagination)
     */
    public function getAllProducts(Request $request)
    {
        if ($request->user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $products = Product::withTrashed()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Search products (for admin and retailer)
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->input('query');
        $perPage = $request->input('per_page', 10);

        $productsQuery = Product::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%")
              ->orWhere('brand', 'like', "%{$query}%")
              ->orWhere('category', 'like', "%{$query}%")
              ->orWhere('subcategory', 'like', "%{$query}%")
              ->orWhereJsonContains('specifications', ['connectivity' => $query]);
        });

        // If user is retailer, only show their products
        if ($request->user()->role === User::ROLE_RETAILER) {
            $productsQuery->where('retailer_id', $request->user()->id);
        }

        // For regular users, only show active products
        if ($request->user()->role === User::ROLE_USER) {
            $productsQuery->where('is_active', true);
        }

        $products = $productsQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }


    public function normalSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->input('query');
        $perPage = $request->input('per_page', 10);

        $productsQuery = Product::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%")
              ->orWhere('brand', 'like', "%{$query}%")
              ->orWhere('category', 'like', "%{$query}%")
              ->orWhere('subcategory', 'like', "%{$query}%")
              ->orWhereJsonContains('specifications', ['connectivity' => $query]);
        });

        $products = $productsQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }


    /**
     * Get products by category
     */
    public function getByCategory(Request $request, $category)
    {
        
        $validator = Validator::make(['category' => $category], [
            'category' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->input('per_page', 10);
        $subcategory = $request->input('subcategory');

        $query = Product::where('category', $category)
                       ->where('is_active', true);

        if ($subcategory) {
            $query->where('subcategory', $subcategory);
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Homepage products with multiple filters
     */
    public function homepageProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:255',
            'subcategory' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0',
            'min_rating' => 'sometimes|numeric|min:0|max:5',
            'sort_by' => 'sometimes|string|in:price_asc,price_desc,newest,rating,featured',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'featured' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->input('per_page', 12);
        $query = Product::where('is_active', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('min_rating')) {
            $query->where('ratings->average', '>=', $request->min_rating);
        }

        if ($request->has('featured')) {
            $query->where('is_featured', $request->featured);
        }

        switch ($request->input('sort_by')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'rating':
                $query->orderBy('ratings->average', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ],
            'filters' => $request->all()
        ]);
    }

    /**
     * Get available filters for homepage
     */
    public function getAvailableFilters()
    {
        $categories = Product::where('is_active', true)
            ->distinct('category')
            ->pluck('category');

        $subcategories = Product::where('is_active', true)
            ->distinct('subcategory')
            ->pluck('subcategory');

        $brands = Product::where('is_active', true)
            ->distinct('brand')
            ->pluck('brand');

        $priceRange = [
            'min' => Product::where('is_active', true)->min('price'),
            'max' => Product::where('is_active', true)->max('price')
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'subcategories' => $subcategories,
                'brands' => $brands,
                'price_range' => $priceRange,
                'max_rating' => 5
            ]
        ]);
    }

    // Add a new method to get products by shop
    public function getByShop(Request $request, $shopId)
    {
        $shop = Shop::where('shop_id', $shopId)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // For unauthenticated users, only show active products
        if (!$request->user()) {
            $products = Product::where('shop_id', $shopId)
                             ->where('is_active', true)
                             ->paginate($request->input('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_next_page' => $products->hasMorePages(),
                    'has_previous_page' => $products->currentPage() > 1,
                ]
            ]);
        }

        // For authenticated users, check authorization
        if ($request->user()->role === User::ROLE_RETAILER && $shop->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this shop\'s products'
            ], 403);
        }

        $perPage = $request->input('per_page', 10);
        $products = Product::where('shop_id', $shopId)
                          ->where('is_active', $request->user()->role !== User::ROLE_USER ? true : null)
                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_next_page' => $products->hasMorePages(),
                'has_previous_page' => $products->currentPage() > 1,
            ]
        ]);
    }

    // Add these methods to ProductController

    /**
     * Add or update a product review
     */
    public function addReview(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $productId)
                               ->where('user_id', $request->user()->id)
                               ->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->update([
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);
            
            $message = 'Review updated successfully';
        } else {
            // Create new review
            Review::create([
                'review_id' => 'REV' . Str::upper(Str::random(6)),
                'product_id' => $productId,
                'user_id' => $request->user()->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_approved' => true
            ]);
            
            $message = 'Review added successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $product->fresh() // Return product with updated ratings
        ]);
    }

    /**
     * Delete a product review
     */
    public function deleteReview(Request $request, $productId)
    {
        $product = Product::where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $review = Review::where('product_id', $productId)
                       ->where('user_id', $request->user()->id)
                       ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
            'data' => $product->fresh() // Return product with updated ratings
        ]);
    }

    /**
     * Get product reviews
     */
    public function getReviews($productId, Request $request)
    {
        $product = Product::where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $perPage = $request->input('per_page', 10);
        $reviews = $product->reviews()
                          ->with('user:id,name')
                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'has_next_page' => $reviews->hasMorePages(),
                'has_previous_page' => $reviews->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Add or update product discount
     */
    public function setDiscount(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'discount_amount' => 'required|numeric|min:0',
            'is_discount' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if the user owns the product's shop
        $shop = Shop::where('shop_id', $product->shop_id)
                   ->where('user_id', $request->user()->id)
                   ->first();

        if (!$shop && $request->user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this product'
            ], 403);
        }

        $product->update([
            'is_discount' => $request->is_discount,
            'discount_amount' => $request->discount_amount
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove product discount
     */
    public function removeDiscount(Request $request, $productId)
    {
        $product = Product::where('product_id', $productId)->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if the user owns the product's shop
        $shop = Shop::where('shop_id', $product->shop_id)
                   ->where('user_id', $request->user()->id)
                   ->first();

        if (!$shop && $request->user()->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this product'
            ], 403);
        }

        $product->update([
            'is_discount' => false,
            'discount_amount' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount removed successfully',
            'data' => $product
        ]);
    }
}