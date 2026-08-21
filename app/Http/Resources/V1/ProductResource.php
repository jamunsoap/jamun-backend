<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => (float)$this->price,
            'discount_price' => (float)$this->discount_price,
            'final_price' => ($this->discount_price > 0 && $this->discount_price < $this->price)
                ? (float)$this->discount_price
                : (float)$this->price,
            'category' => $this->category,
            'images' => $this->images ?? [],
            'stock' => (int)$this->stock,
            'in_stock' => $this->stock > 0,
            'weight' => $this->weight,
            'ingredients' => $this->ingredients ?? [],
            'benefits' => $this->benefits ?? [],
            'is_featured' => (bool)$this->is_featured,
            'average_rating' => (float)$this->average_rating,
            'reviews_count' => $this->reviews()->where('is_approved', true)->count(),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
