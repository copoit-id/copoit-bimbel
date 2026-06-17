<?php

namespace App\Http\Controllers;

use App\Models\Article;

class GeneralPageController extends Controller
{
    public function landing()
    {
        return view('general.placeholder', [
            'title' => 'Landing Page',
        ]);
    }

    public function statistics()
    {
        return view('general.placeholder', [
            'title' => 'Statistik PTN',
        ]);
    }

    public function articles()
    {
        $articles = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->paginate(9);

        $featuredArticle = Article::query()
            ->with('author:id,name')
            ->published()
            ->latest('published_at')
            ->first();

        return view('general.articles.index', compact('articles', 'featuredArticle'));
    }

    public function showArticle(string $slug)
    {
        $article = Article::query()
            ->with('author:id,name')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedArticles = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('general.articles.show', compact('article', 'relatedArticles'));
    }
}
