<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\V1\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * Get approved reviews for a specific product.
     */
    public function index(int $productId): AnonymousResourceCollection
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    /**
     * Submit a new customer review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_approved'] = false; // Requires admin moderation

        $review = Review::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your review has been submitted and is awaiting moderation.',
            'review' => new ReviewResource($review),
        ], 201);
    }
}
