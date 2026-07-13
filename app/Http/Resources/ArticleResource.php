<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'published' => $this->published,
            'thumbnail' => $this->when(
                $this->thumbnail,
                Storage::disk(config('filesystems.public_uploads_disk'))
                    ->url($this->thumbnail)
            ),
            'author' => UserResource::make(
                $this->whenLoaded('author')
            ),
        ];
    }
}
