<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'discount_price',
        'category',
        'images',
        'stock',
        'weight',
        'ingredients',
        'benefits',
        'is_featured',
        'is_active',
        'average_rating',
    ];

    protected $casts = [
        'images' => 'array',
        'ingredients' => 'array',
        'benefits' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::saved(function ($product) {
            self::clearCache($product);
        });

        static::deleted(function ($product) {
            self::clearCache($product);
        });
    }

    public static function clearCache(?Product $product = null): void
    {
        Cache::forget('products.all_active');
        Cache::forget('products.featured');
        if ($product && $product->slug) {
            Cache::forget('products.slug.' . $product->slug);
            Cache::forget('products.id.' . $product->id);
        }
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
