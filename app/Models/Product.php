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
        'ratings',
        'variants',
        'specifications',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'ratings' => 'array',
        'variants' => 'array',
        'specifications' => 'array',
        'is_active' => 'boolean',
        'price' => 'float',
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
}