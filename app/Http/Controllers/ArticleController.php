<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleUpdateRequest;
use App\Http\Requests\ListArticleRequest;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Article::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ListArticleRequest $request)
    {
        $page = $request->validated('page', 1);
        $search = $request->validated('search');
        $status = $request->validated('status');
        $sort = $request->validated('sort');

        $cacheKey = "articles." . md5(
            json_encode([
                'page' => $page,
                'search' => $search,
                'status' => $status,
                'sort' => $sort
            ])
        );

        $articles = Cache::tags(['articles'])->remember($cacheKey, now()->addMinutes(10), function () use ($search, $status, $sort) {
            return Article::query()
                ->with('author')
                ->when($search, fn ($query) => $query->search($search))
                ->when($status, fn ($query) => $query->status($status))
                ->sort($sort)
                ->paginate(10);
        });

        return ArticleResource::collection($articles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        $user = $request->user();

        $file = $request->file('thumbnail');
        $path = $file?->store('articles', config('filesystems.public_uploads_disk'));

        $article = $user->articles()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'published' => $request->validated('published', false),
            'thumbnail' => $path,
        ]);

        return ArticleResource::make($article->load('author'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        return ArticleResource::make($article->load('author'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArticleUpdateRequest $request, Article $article)
    {
        //$this->authorize('update', $article);

        $data = [
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'published' => $request->validated('published', $article->published),
        ];

        $oldPath = $article->thumbnail;

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $path = $file->store('articles', config('filesystems.public_uploads_disk'));

            $data['thumbnail'] = $path;
        }

        $article->update($data);

        if(isset($data['thumbnail']) && $oldPath)
            Storage::disk(config('filesystems.public_uploads_disk'))->delete($oldPath);

        return ArticleResource::make($article->load('author'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        //$this->authorize('delete', $article);

        $article->delete();
        // The observer handles file deletion
        return response()->noContent();
    }
}
