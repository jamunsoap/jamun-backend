<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of active products with search, filtering, and sorting.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hasFilters = $request->filled('category') || $request->filled('search') || $request->boolean('featured') || $request->filled('sort');

        if (!$hasFilters && (int)$request->input('page', 1) === 1) {
            $products = Cache::remember('products.all_active', 3600, function () use ($request) {
                return Product::where('is_active', true)
                    ->withCount(['reviews as reviews_count' => function ($q) {
                        $q->where('is_approved', true);
                    }])
                    ->latest()
                    ->paginate((int)$request->input('per_page', 12));
            });

            return ProductResource::collection($products);
        }

        $query = Product::where('is_active', true)->withCount(['reviews as reviews_count' => function ($q) {
            $q->where('is_approved', true);
        }]);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search by keyword in name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Sorting options
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('average_rating', 'desc'),
            default => $query->latest(),
        };

        $perPage = (int)$request->input('per_page', 12);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display single product by slug (or ID fallback) with caching for high concurrency.
     */
    public function show(string $slug): ProductResource|JsonResponse
    {
        $cacheKey = 'products.slug.' . $slug;

        $product = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Product::where('is_active', true)
                ->where(function ($q) use ($slug) {
                    $q->where('slug', $slug)
                      ->orWhere('id', is_numeric($slug) ? $slug : 0);
                })
                ->with(['reviews' => fn($q) => $q->where('is_approved', true)->latest()])
                ->first();
        });

        if (!$product) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return new ProductResource($product);
    }

    /**
     * Get featured hero products for landing page with caching.
     */
    public function featured(): AnonymousResourceCollection
    {
        $products = Cache::remember('products.featured', 3600, function () {
            return Product::where('is_active', true)
                ->where('is_featured', true)
                ->withCount(['reviews as reviews_count' => function ($q) {
                    $q->where('is_approved', true);
                }])
                ->latest()
                ->take(4)
                ->get();
        });

        return ProductResource::collection($products);
    }
}
