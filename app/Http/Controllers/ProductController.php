<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Create a new product (for retailer and admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'subcategory' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'stock_quantity' => 'required|integer|min:0',
            'images' => 'required|array',
            'images.*' => 'url',
            'variants' => 'required|array',
            'variants.*.variant_id' => 'required|string',
            'variants.*.color' => 'required|string',
            'variants.*.stock' => 'required|integer|min:0',
            'specifications.battery_life' => 'sometimes|string',
            'specifications.connectivity' => 'sometimes|string',
            'specifications.weight' => 'sometimes|string',
            'is_featured' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $productData = $validator->validated();
        $productData['product_id'] = 'PROD' . Str::random(6);
        $productData['retailer_id'] = $request->user()->id;
        $productData['ratings'] = [
            'average' => 0,
            'count' => 0
        ];
        $productData['is_active'] = true;

        $product = Product::create($productData);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Get product by ID
     */
    public function show(Request $request, $id)
    {
        $product = Product::where('product_id', $id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check authorization:
        // - Admin can view any product
        // - Retailer can view only their own products
        // - Users can view any active product
        if ($request->user()->role === User::ROLE_RETAILER && $product->retailer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this product'
            ], 403);
        }

        // For regular users, only show active products
        if ($request->user()->role === User::ROLE_USER && !$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product not available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function getProductById($productId)
    {
        $product = Product::where('product_id', $productId)
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
            'data' => $product
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

        // Check if the user is the retailer who owns the product or an admin
        if ($request->user()->role !== User::ROLE_ADMIN && $product->retailer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this product'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|string|max:255',
            'subcategory' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'stock_quantity' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'url',
            'variants' => 'sometimes|array',
            'variants.*.variant_id' => 'sometimes|string',
            'variants.*.color' => 'sometimes|string',
            'variants.*.stock' => 'sometimes|integer|min:0',
            'specifications.battery_life' => 'sometimes|string',
            'specifications.connectivity' => 'sometimes|string',
            'specifications.weight' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
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

        // Check if the user is the retailer who owns the product or an admin
        if ($request->user()->role !== User::ROLE_ADMIN && $product->retailer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this product'
            ], 403);
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
     * Update product rating (for users only)
     */
    public function updateRating(Request $request, $id)
    {
        if ($request->user()->role !== User::ROLE_USER) {
            return response()->json([
                'success' => false,
                'message' => 'Only users can rate products'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::where('product_id', $id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $rating = $request->input('rating');
        $currentRatings = $product->ratings ?? ['average' => 0, 'count' => 0];
        $currentAverage = $currentRatings['average'];
        $currentCount = $currentRatings['count'];

        $newAverage = ($currentAverage * $currentCount + $rating) / ($currentCount + 1);

        $product->update([
            'ratings' => [
                'average' => round($newAverage, 1),
                'count' => $currentCount + 1
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product rating updated successfully',
            'data' => $product
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
}