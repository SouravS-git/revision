<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'published', 'thumbnail'
    ];

    protected $casts = [
        'published' => 'boolean'
    ];

    public function author(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted(): void{
        static::saving(function (Article $article) {
            if($article->isDirty('title'))
                $article->slug = str()->slug($article->title);
        });
    }

    public function scopeSearch(Builder $query, string $search): Builder{
        return $query->where(function($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeStatus(Builder $query, string $status): Builder{
        return match ($status) {
            'published' => $query->where('published', true),
            'draft' => $query->where('published', false)
        };
    }

    public function scopeSort(Builder $query, ?string $sort): Builder
    {
        if (!$sort)
            return $query->latest();

        $order = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        return $query->orderBy($column, $order);
    }
}
