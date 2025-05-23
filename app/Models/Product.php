<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'shop_id', 
        'name',
        'description',
        'price',
        'category',
        'subcategory',
        'brand',
        'stock_quantity',
        'images',
        'variants',
        'specifications',
        'is_active',
        'is_featured',
        'is_discount',
        'discount_amount'
    ];

    protected $casts = [
        'images' => 'array',
        'variants' => 'array',
        'specifications' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_discount' => 'boolean',
        'price' => 'float',
        'discount_amount' => 'float',
        'stock_quantity' => 'integer',
    ];

    public function shops()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function wishlistedBy()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id', 'product_id');
    }

    // Accessor to calculate average rating
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating');
    }

    // Accessor to get ratings count
    public function getRatingsCountAttribute()
    {
        return $this->reviews()->count();
    }

    // Accessor to maintain backward compatibility
    public function getRatingsAttribute()
    {
        return [
            'average' => $this->average_rating,
            'count' => $this->ratings_count
        ];
    }
}