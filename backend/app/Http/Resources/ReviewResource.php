<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Mirrors `Review` in frontend/src/types.ts. Shows only the reviewer's first name.
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => strtok($this->user->name, ' '),
            'rating' => $this->rating,
            'body' => $this->body,
            // Demo review from the seed data, shown with a "Sample review" label.
            'is_sample' => (bool) $this->is_sample,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
