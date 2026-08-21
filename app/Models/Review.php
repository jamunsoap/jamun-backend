<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'user_name',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($review) {
            $review->recalculateProductRating();
            Product::clearCache($review->product);
        });

        static::deleted(function ($review) {
            $review->recalculateProductRating();
            Product::clearCache($review->product);
        });
    }

    public function recalculateProductRating(): void
    {
        $product = $this->product;
        if ($product) {
            $avg = self::where('product_id', $product->id)
                ->where('is_approved', true)
                ->avg('rating');

            $product->update([
                'average_rating' => round($avg ?? 4.90, 2),
            ]);
        }
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
