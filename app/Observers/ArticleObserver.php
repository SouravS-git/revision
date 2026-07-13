<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ArticleObserver
{
    public function created(Article $article){
        Cache::tags(['articles'])->flush();     // We have to run - composer require predis/predis, and set REDIS_CLIENT=predis in .env to make it work
    }

    public function updated(Article $article){
        Cache::tags(['articles'])->flush();
    }

    public function deleted(Article $article){
        Cache::tags(['articles'])->flush();

        if($article->thumbnail)
            Storage::disk(config('filesystems.public_uploads_disk'))->delete($article->thumbnail);
    }
}
